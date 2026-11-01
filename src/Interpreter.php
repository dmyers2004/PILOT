<?php
declare(strict_types=1);

namespace Pilot;

/**
 * Runs a Program.
 *
 * The loop is deliberately plain: fetch the statement at the program counter,
 * advance, decide whether its conditioners let it run, then do it. Jumps and
 * subroutine calls just move the counter.
 */
final class Interpreter
{
    private Program $program;
    private Store $store;
    private Expression $expr;
    private Turtle $turtle;
    private Sound $sound;

    private int $pc = 0;
    /** @var array<int,int> return addresses pushed by U: */
    private array $callStack = [];
    private bool $halted = false;
    private int $steps = 0;
    private int $links = 0;

    /** @var resource */
    private $out;
    /** @var resource */
    private $in;

    private const MAX_CALL_DEPTH = 256;
    private const MAX_LINKS = 64;

    public function __construct(private readonly Options $opt)
    {
        $this->store = new Store();
        $this->expr = new Expression($this->store);
        $this->turtle = new Turtle();
        $this->sound = new Sound();
        $this->out = fopen('php://stdout', 'wb') ?: throw new PilotError('cannot open stdout');
        $this->in = fopen('php://stdin', 'rb') ?: throw new PilotError('cannot open stdin');

        if ($this->opt->seed !== null) {
            mt_srand($this->opt->seed);
        }
    }

    public function run(Program $program): void
    {
        $this->program = $program;
        $this->pc = 0;

        while (!$this->halted && $this->pc < count($this->program->stmts)) {
            $stmt = $this->program->stmts[$this->pc];
            $this->pc++;

            if ($this->opt->maxSteps > 0 && ++$this->steps > $this->opt->maxSteps) {
                throw new PilotError('step limit reached; the program may be looping', $stmt->srcLine);
            }
            if ($this->opt->trace) {
                fwrite(STDERR, sprintf("[%4d] %s\n", $stmt->srcLine, $stmt->source));
            }
            if ($this->allowed($stmt)) {
                $this->exec($stmt);
            }
        }

        $this->finish();
    }

    /** Y / N / (expression) conditioners. */
    private function allowed(Statement $s): bool
    {
        if ($s->flag === 'Y' && !$this->store->match()) {
            return false;
        }
        if ($s->flag === 'N' && $this->store->match()) {
            return false;
        }
        if ($s->cond !== null && !Expression::truthy($this->expr->eval($s->cond, $s->srcLine))) {
            return false;
        }
        return true;
    }

    private function exec(Statement $s): void
    {
        switch ($s->op) {
            case 'R':  return;
            case 'T':  $this->write($this->interp($s->arg, $s->srcLine) . "\n"); return;
            case 'TH': $this->write($this->interp($s->arg, $s->srcLine)); return;
            case 'A':  $this->accept($s); return;
            case 'M':  $this->doMatch($s); return;
            case 'J':  $this->pc = $this->target($s); return;
            case 'U':  $this->use($s); return;
            case 'E':  $this->return_(); return;
            case 'C':  $this->compute($s); return;
            case 'PA': $this->pause($s); return;
            case 'D':  $this->dim($s); return;
            case 'G':  $this->turtle->command($this->interp($s->arg, $s->srcLine), $s->srcLine); return;
            case 'S':  $this->sound->command($this->interp($s->arg, $s->srcLine), $s->srcLine); return;
            case 'L':  $this->link($s); return;
        }
        throw new PilotError("cannot run '$s->op'", $s->srcLine);
    }

    // ------------------------------------------------------------------ I/O

    private function write(string $text): void
    {
        fwrite($this->out, $text);
        fflush($this->out);
    }

    /**
     * A: reads a line into the answer buffer ($ANSWER, also readable as %B).
     * A:#X or A:$NAME also stores it; several targets split the reply on commas.
     * Running out of input ends the program, which is what a piped run wants.
     */
    private function accept(Statement $s): void
    {
        $line = fgets($this->in);
        if ($line === false) {
            $this->halted = true;
            return;
        }
        $text = rtrim($line, "\r\n");
        if ($this->opt->echoInput) {
            $this->write($text . "\n");
        }

        $this->store->setStr('ANSWER', $text);
        $this->store->setNum('ANSWER', Expression::toNum($text));

        $targets = trim($s->arg);
        if ($targets === '') {
            return;
        }

        $names = self::splitTop($targets, ',');
        $fields = count($names) > 1 ? self::splitTop($text, ',') : [$text];
        foreach ($names as $k => $name) {
            $this->assign(trim($name), trim($fields[$k] ?? ''), $s->srcLine);
        }
    }

    // --------------------------------------------------------------- matching

    /**
     * M: tries each comma-separated pattern against the answer buffer as a
     * case-insensitive substring; * and ? are wildcards. The first hit sets the
     * yes flag, #MATCH (which pattern, 1-based) and $MATCH / $LEFT / $RIGHT.
     */
    private function doMatch(Statement $s): void
    {
        $buffer = $this->store->getStr('ANSWER');
        $patterns = self::splitTop($s->arg, ',');

        $which = 0;
        $matched = $left = $right = '';

        foreach ($patterns as $k => $raw) {
            $pattern = trim($this->interp($raw, $s->srcLine));
            $pattern = str_replace(['\\,', '\\\\'], [',', '\\'], $pattern);
            if ($pattern === '') {
                continue;
            }
            $regex = strtr(preg_quote($pattern, '/'), ['\*' => '.*', '\?' => '.']);
            if (preg_match('/' . $regex . '/i', $buffer, $m, PREG_OFFSET_CAPTURE)) {
                $which = $k + 1;
                $matched = $m[0][0];
                $left = substr($buffer, 0, $m[0][1]);
                $right = substr($buffer, $m[0][1] + strlen($matched));
                break;
            }
        }

        $this->store->setMatch($which > 0);
        $this->store->setNum('MATCH', (float) $which);
        $this->store->setStr('MATCH', $matched);
        $this->store->setStr('LEFT', $left);
        $this->store->setStr('RIGHT', $right);
    }

    // ------------------------------------------------------- flow of control

    /**
     * Resolve a jump or call target. A list of labels is a computed jump: the
     * one chosen is numbered by #MATCH, so M: followed by J:*A,*B,*C branches
     * three ways. An out-of-range #MATCH falls through to the next statement.
     */
    private function target(Statement $s): int
    {
        $parts = self::splitTop($s->arg, ',');
        if (count($parts) > 1) {
            $k = (int) $this->store->getNum('MATCH');
            if ($k < 1 || $k > count($parts)) {
                return $this->pc;
            }
            $parts = [$parts[$k - 1]];
        }
        return $this->program->labelPc($this->interp(trim($parts[0]), $s->srcLine), $s->srcLine);
    }

    private function use(Statement $s): void
    {
        if (count($this->callStack) >= self::MAX_CALL_DEPTH) {
            throw new PilotError('subroutines nested too deeply', $s->srcLine);
        }
        $to = $this->target($s);
        $this->callStack[] = $this->pc;
        $this->pc = $to;
    }

    private function return_(): void
    {
        if ($this->callStack === []) {
            $this->halted = true;
            return;
        }
        $this->pc = (int) array_pop($this->callStack);
    }

    private function pause(Statement $s): void
    {
        $jiffies = $s->arg === '' ? 60.0 : $this->expr->evalNum($s->arg, $s->srcLine);
        if ($this->opt->fast || $jiffies <= 0) {
            return;
        }
        usleep((int) min(60_000_000, $jiffies / 60 * 1_000_000));
    }

    /** L: hands over to another PILOT file, keeping the variables. */
    private function link(Statement $s): void
    {
        if (++$this->links > self::MAX_LINKS) {
            throw new PilotError('too many L: links', $s->srcLine);
        }
        $name = trim($this->interp($s->arg, $s->srcLine));
        if ($name === '') {
            throw new PilotError('L: needs a file name', $s->srcLine);
        }
        $path = $name;
        if ($path[0] !== '/' && is_file(dirname($this->program->path) . '/' . $path)) {
            $path = dirname($this->program->path) . '/' . $path;
        }

        $this->program = Parser::parseFile($path);
        $this->pc = 0;
        $this->callStack = [];
    }

    // ------------------------------------------------------------ assignment

    /** C:#X=expr, several separated by semicolons. */
    private function compute(Statement $s): void
    {
        foreach (self::splitTop($s->arg, ';') as $piece) {
            $piece = trim($piece);
            if ($piece === '') {
                continue;
            }
            $at = self::findAssign($piece);
            if ($at < 0) {
                throw new PilotError("C: needs an '=' in \"$piece\"", $s->srcLine);
            }
            $target = substr($piece, 0, $at);
            $rhs = substr($piece, $at + 1);
            $this->assignExpr(trim($target), $rhs, $s->srcLine);
        }
    }

    /** Position of the assigning '=', skipping <=, >=, <> and ==. */
    private static function findAssign(string $s): int
    {
        $depth = 0;
        $quoted = false;
        for ($i = 0, $n = strlen($s); $i < $n; $i++) {
            $c = $s[$i];
            if ($c === '"') {
                $quoted = !$quoted;
                continue;
            }
            if ($quoted) {
                continue;
            }
            if ($c === '(') {
                $depth++;
            } elseif ($c === ')') {
                $depth--;
            } elseif ($c === '=' && $depth === 0) {
                $prev = $i > 0 ? $s[$i - 1] : '';
                if ($prev === '<' || $prev === '>' || $prev === '!' || $prev === '=') {
                    continue;
                }
                if (($s[$i + 1] ?? '') === '=') {
                    $i++;
                    continue;
                }
                return $i;
            }
        }
        return -1;
    }

    private function assignExpr(string $target, string $rhs, ?int $line): void
    {
        $isString = str_starts_with($target, '$') || str_starts_with($target, '%');

        if (!$isString) {
            $this->assign($target, $this->expr->eval($rhs, $line), $line);
            return;
        }

        // A string right-hand side is an expression when it can be read as
        // one, and plain text otherwise, so C:$NAME=HELLO works as written.
        try {
            $value = Expression::toStr($this->expr->eval($rhs, $line));
        } catch (PilotError) {
            $value = $this->interp(trim($rhs), $line);
        }
        $this->assign($target, $value, $line);
    }

    private function assign(string $target, float|string $value, ?int $line): void
    {
        if (!preg_match('/^([#$%])([A-Za-z_][A-Za-z0-9_]*)\s*(?:\(\s*(.+?)\s*\))?$/s', $target, $m)) {
            throw new PilotError("'$target' is not a variable", $line);
        }
        [, $sigil, $name] = $m;
        if ($sigil === '%') {
            $name = Store::alias($name);
        }
        $index = isset($m[3]) && $m[3] !== '' ? $this->expr->evalInt($m[3], $line) : null;

        if ($sigil === '#') {
            $number = Expression::toNum($value);
            $index === null
                ? $this->store->setNum($name, $number)
                : $this->store->setNumAt($name, $index, $number, $line);
            return;
        }

        $text = Expression::toStr($value);
        $index === null
            ? $this->store->setStr($name, $text)
            : $this->store->setStrAt($name, $index, $text, $line);
    }

    /** D:#A(20),$B(5) */
    private function dim(Statement $s): void
    {
        foreach (self::splitTop($s->arg, ',;') as $piece) {
            $piece = trim($piece);
            if ($piece === '') {
                continue;
            }
            if (!preg_match('/^([#$])([A-Za-z_][A-Za-z0-9_]*)\s*\(\s*(.+?)\s*\)$/s', $piece, $m)) {
                throw new PilotError("D: expected #NAME(size), got \"$piece\"", $s->srcLine);
            }
            $size = $this->expr->evalInt($m[3], $s->srcLine);
            $m[1] === '#'
                ? $this->store->dimNum($m[2], $size, $s->srcLine)
                : $this->store->dimStr($m[2], $size, $s->srcLine);
        }
    }

    // --------------------------------------------------------- interpolation

    /**
     * Expand #NUM, $STR, %B and #(expression) inside text. A backslash before
     * a sigil (or another backslash) makes it literal.
     */
    private function interp(string $s, ?int $line): string
    {
        if ($s === '' || !preg_match('/[#$%\\\\]/', $s)) {
            return $s;
        }

        $out = '';
        $i = 0;
        $n = strlen($s);

        while ($i < $n) {
            $c = $s[$i];

            if ($c === '\\' && isset($s[$i + 1]) && str_contains('#$%\\', $s[$i + 1])) {
                $out .= $s[$i + 1];
                $i += 2;
                continue;
            }

            $isSigil = $c === '#' || $c === '$'
                || ($c === '%' && isset($s[$i + 1]) && ctype_alpha($s[$i + 1]));

            if ($isSigil) {
                // #(expression) evaluates in place.
                if ($c !== '%' && ($s[$i + 1] ?? '') === '(') {
                    [$inner, $next] = $this->balanced($s, $i + 1, $line);
                    $out .= Expression::toStr($this->expr->eval($inner, $line));
                    $i = $next;
                    continue;
                }
                if (preg_match('/[A-Za-z_][A-Za-z0-9_]*/A', $s, $m, 0, $i + 1)) {
                    $name = $m[0];
                    $j = $i + 1 + strlen($name);
                    $index = null;
                    if (($s[$j] ?? '') === '(') {
                        [$inner, $j] = $this->balanced($s, $j, $line);
                        $index = $this->expr->evalInt($inner, $line);
                    }
                    if ($c === '%') {
                        $name = Store::alias($name);
                    }
                    $out .= $c === '#'
                        ? Expression::fmtNum($index === null
                            ? $this->store->getNum($name)
                            : $this->store->getNumAt($name, $index, $line))
                        : ($index === null
                            ? $this->store->getStr($name)
                            : $this->store->getStrAt($name, $index, $line));
                    $i = $j;
                    continue;
                }
            }

            $out .= $c;
            $i++;
        }

        return $out;
    }

    /** @return array{0:string,1:int} the text inside (...) and the index after it */
    private function balanced(string $s, int $open, ?int $line): array
    {
        $depth = 0;
        for ($j = $open, $n = strlen($s); $j < $n; $j++) {
            if ($s[$j] === '(') {
                $depth++;
            } elseif ($s[$j] === ')') {
                $depth--;
                if ($depth === 0) {
                    return [substr($s, $open + 1, $j - $open - 1), $j + 1];
                }
            }
        }
        throw new PilotError('unclosed ( in text', $line);
    }

    /** Split on any of $delims at paren depth 0 and outside quotes. */
    private static function splitTop(string $s, string $delims): array
    {
        $parts = [];
        $buf = '';
        $depth = 0;
        $quoted = false;

        for ($i = 0, $n = strlen($s); $i < $n; $i++) {
            $c = $s[$i];
            if ($c === '\\' && $i + 1 < $n) {
                $buf .= $c . $s[$i + 1];
                $i++;
                continue;
            }
            if ($c === '"') {
                $quoted = !$quoted;
                $buf .= $c;
                continue;
            }
            if (!$quoted) {
                if ($c === '(') {
                    $depth++;
                } elseif ($c === ')') {
                    $depth--;
                } elseif ($depth === 0 && str_contains($delims, $c)) {
                    $parts[] = $buf;
                    $buf = '';
                    continue;
                }
            }
            $buf .= $c;
        }
        $parts[] = $buf;

        return $parts;
    }

    // ------------------------------------------------------------- finishing

    private function finish(): void
    {
        if ($this->turtle->used() && $this->opt->graphicsOutput) {
            $path = $this->opt->graphicsFile ?? $this->defaultOutput('svg');
            if (@file_put_contents($path, $this->turtle->toSvg()) === false) {
                fwrite(STDERR, "pilot: cannot write graphics to $path\n");
            } else {
                fwrite(STDERR, "pilot: graphics written to $path\n");
            }
        }

        if ($this->sound->used() && $this->opt->soundFile !== null) {
            if (@file_put_contents($this->opt->soundFile, $this->sound->toWav()) === false) {
                fwrite(STDERR, "pilot: cannot write sound to {$this->opt->soundFile}\n");
            } else {
                fwrite(STDERR, sprintf(
                    "pilot: %d note%s (%.1fs) written to %s\n",
                    $this->sound->count(),
                    $this->sound->count() === 1 ? '' : 's',
                    $this->sound->seconds(),
                    $this->opt->soundFile,
                ));
            }
        }
    }

    private function defaultOutput(string $ext): string
    {
        $base = basename($this->program->path);
        $base = preg_replace('/\.[^.]*$/', '', $base) ?: 'pilot';
        if ($base === '' || $base[0] === '<') {
            $base = 'pilot';
        }
        return $base . '.' . $ext;
    }
}

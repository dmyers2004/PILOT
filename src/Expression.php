<?php
declare(strict_types=1);

namespace Pilot;

/**
 * The expression language used by C: and by (expression) conditioners.
 *
 * Values are either floats or strings; + concatenates when either side is
 * non-numeric text, and comparisons fall back to case-insensitive text
 * comparison when neither side looks like a number. That is deliberately
 * forgiving: PILOT expressions mostly exist to judge what a student typed.
 */
final class Expression
{
    /** @var array<string,array<int,array{0:string,1:mixed}>> compiled token cache */
    private static array $cache = [];

    /** @var array<int,array{0:string,1:mixed}> */
    private array $toks = [];
    private int $pos = 0;
    private ?int $line = null;

    private const WORD_OPS = ['AND' => '&', 'OR' => '|', 'NOT' => '!', 'MOD' => '%'];

    public function __construct(private readonly Store $store)
    {
    }

    public function eval(string $src, ?int $line = null): float|string
    {
        $src = trim($src);
        if ($src === '') {
            throw new PilotError('empty expression', $line);
        }
        $this->toks = self::$cache[$src] ??= self::tokenize($src, $line);
        $this->pos = 0;
        $this->line = $line;

        $value = $this->parseOr();
        if ($this->peek() !== null) {
            throw new PilotError('unexpected "' . self::render($this->toks[$this->pos]) . '" in expression', $line);
        }
        return $value;
    }

    public function evalNum(string $src, ?int $line = null): float
    {
        return self::toNum($this->eval($src, $line));
    }

    public function evalStr(string $src, ?int $line = null): string
    {
        return self::toStr($this->eval($src, $line));
    }

    public function evalInt(string $src, ?int $line = null): int
    {
        return (int) self::toNum($this->eval($src, $line));
    }

    // ---------------------------------------------------------------- values

    public static function toNum(float|string $v): float
    {
        if (is_float($v)) {
            return $v;
        }
        $t = trim($v);
        if (preg_match('/^[+-]?(\d+\.?\d*|\.\d+)([eE][+-]?\d+)?/', $t, $m)) {
            return (float) $m[0];
        }
        return 0.0;
    }

    public static function toStr(float|string $v): string
    {
        return is_string($v) ? $v : self::fmtNum($v);
    }

    /** Print numbers the way a BASIC would: no trailing ".0" on whole values. */
    public static function fmtNum(float $v): string
    {
        if (is_nan($v)) {
            return 'NAN';
        }
        if (is_infinite($v)) {
            return $v > 0 ? 'INF' : '-INF';
        }
        if ($v === floor($v) && abs($v) < 1.0e15) {
            return (string) (int) $v;
        }
        $s = rtrim(rtrim(sprintf('%.9F', $v), '0'), '.');
        return $s === '-0' ? '0' : $s;
    }

    public static function truthy(float|string $v): bool
    {
        if (is_float($v)) {
            return $v != 0.0;
        }
        $t = trim($v);
        return $t !== '' && $t !== '0';
    }

    private static function looksNumeric(float|string $v): bool
    {
        return is_float($v) || is_numeric(trim($v));
    }

    // ------------------------------------------------------------- tokenizer

    /** @return array<int,array{0:string,1:mixed}> */
    private static function tokenize(string $s, ?int $line): array
    {
        $toks = [];
        $i = 0;
        $n = strlen($s);

        while ($i < $n) {
            $c = $s[$i];

            if ($c === ' ' || $c === "\t" || $c === "\n" || $c === "\r") {
                $i++;
                continue;
            }

            if (ctype_digit($c) || ($c === '.' && isset($s[$i + 1]) && ctype_digit($s[$i + 1]))) {
                preg_match('/(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?/A', $s, $m, 0, $i);
                $toks[] = ['num', (float) $m[0]];
                $i += strlen($m[0]);
                continue;
            }

            if ($c === '"') {
                $i++;
                $buf = '';
                while ($i < $n) {
                    if ($s[$i] === '"') {
                        if (($s[$i + 1] ?? '') === '"') {   // "" is a literal quote
                            $buf .= '"';
                            $i += 2;
                            continue;
                        }
                        $i++;
                        break;
                    }
                    $buf .= $s[$i++];
                }
                // A quoted literal is literal text, so {HEART} means there
                // what it means in a T: line.
                $toks[] = ['str', Petscii::expand($buf)];
                continue;
            }

            // #name, $name, and the %B/%M/%L/%R buffers
            if ($c === '#' || $c === '$' || ($c === '%' && isset($s[$i + 1]) && ctype_alpha($s[$i + 1]))) {
                preg_match('/[A-Za-z_][A-Za-z0-9_]*/A', $s, $m, 0, $i + 1);
                if (!isset($m[0])) {
                    throw new PilotError("'$c' must be followed by a variable name", $line);
                }
                $name = strtoupper($m[0]);
                $toks[] = $c === '#'
                    ? ['nvar', $name]
                    : ['svar', $c === '%' ? Store::alias($name) : $name];
                $i += 1 + strlen($m[0]);
                continue;
            }

            if (ctype_alpha($c) || $c === '_') {
                preg_match('/[A-Za-z_][A-Za-z0-9_]*/A', $s, $m, 0, $i);
                $word = strtoupper($m[0]);
                $i += strlen($m[0]);
                $toks[] = isset(self::WORD_OPS[$word]) ? ['op', self::WORD_OPS[$word]] : ['ident', $word];
                continue;
            }

            $two = substr($s, $i, 2);
            if (in_array($two, ['<=', '>=', '<>', '!=', '==', '&&', '||'], true)) {
                $toks[] = ['op', match ($two) {
                    '!=' => '<>',
                    '==' => '=',
                    '&&' => '&',
                    '||' => '|',
                    default => $two,
                }];
                $i += 2;
                continue;
            }

            if (str_contains('+-*/%^()=<>&|!,', $c)) {
                $toks[] = ['op', $c];
                $i++;
                continue;
            }

            throw new PilotError("cannot make sense of '$c' in expression", $line);
        }

        return $toks;
    }

    private static function render(array $tok): string
    {
        return match ($tok[0]) {
            'num' => self::fmtNum($tok[1]),
            'str' => '"' . $tok[1] . '"',
            'nvar' => '#' . $tok[1],
            'svar' => '$' . $tok[1],
            default => (string) $tok[1],
        };
    }

    // ---------------------------------------------------------------- parser

    private function peek(): ?string
    {
        return $this->toks[$this->pos][0] ?? null;
    }

    private function atOp(string ...$ops): bool
    {
        $t = $this->toks[$this->pos] ?? null;
        return $t !== null && $t[0] === 'op' && in_array($t[1], $ops, true);
    }

    private function takeOp(): string
    {
        return (string) $this->toks[$this->pos++][1];
    }

    private function expectOp(string $op): void
    {
        if (!$this->atOp($op)) {
            throw new PilotError("expected '$op' in expression", $this->line);
        }
        $this->pos++;
    }

    private function parseOr(): float|string
    {
        $v = $this->parseAnd();
        while ($this->atOp('|')) {
            $this->pos++;
            $r = $this->parseAnd();
            $v = (self::truthy($v) || self::truthy($r)) ? 1.0 : 0.0;
        }
        return $v;
    }

    private function parseAnd(): float|string
    {
        $v = $this->parseNot();
        while ($this->atOp('&')) {
            $this->pos++;
            $r = $this->parseNot();
            $v = (self::truthy($v) && self::truthy($r)) ? 1.0 : 0.0;
        }
        return $v;
    }

    private function parseNot(): float|string
    {
        if ($this->atOp('!')) {
            $this->pos++;
            return self::truthy($this->parseNot()) ? 0.0 : 1.0;
        }
        return $this->parseCompare();
    }

    private function parseCompare(): float|string
    {
        $v = $this->parseAdd();
        while ($this->atOp('=', '<>', '<', '>', '<=', '>=')) {
            $op = $this->takeOp();
            $r = $this->parseAdd();
            $v = $this->compare($v, $op, $r) ? 1.0 : 0.0;
        }
        return $v;
    }

    private function compare(float|string $a, string $op, float|string $b): bool
    {
        if (self::looksNumeric($a) && self::looksNumeric($b)) {
            $x = self::toNum($a);
            $y = self::toNum($b);
        } else {
            $x = strtoupper(self::toStr($a));
            $y = strtoupper(self::toStr($b));
        }
        return match ($op) {
            '='  => $x == $y,
            '<>' => $x != $y,
            '<'  => $x < $y,
            '>'  => $x > $y,
            '<=' => $x <= $y,
            '>=' => $x >= $y,
            default => throw new PilotError("unknown comparison '$op'", $this->line),
        };
    }

    private function parseAdd(): float|string
    {
        $v = $this->parseMul();
        while ($this->atOp('+', '-')) {
            $op = $this->takeOp();
            $r = $this->parseMul();
            if ($op === '+') {
                // Adds only when both sides are numbers; anything with text
                // in it joins instead. Deciding by what a value LOOKS like
                // made "5 " + "4" come out as 9, which is no way to build a
                // string out of digits.
                $v = (is_float($v) && is_float($r))
                    ? $v + $r
                    : self::toStr($v) . self::toStr($r);
            } else {
                $v = self::toNum($v) - self::toNum($r);
            }
        }
        return $v;
    }

    private function parseMul(): float|string
    {
        $v = $this->parseUnary();
        while ($this->atOp('*', '/', '%')) {
            $op = $this->takeOp();
            $r = self::toNum($this->parseUnary());
            $l = self::toNum($v);
            if (($op === '/' || $op === '%') && $r == 0.0) {
                throw new PilotError('division by zero', $this->line);
            }
            $v = match ($op) {
                '*' => $l * $r,
                '/' => $l / $r,
                '%' => fmod($l, $r),
            };
        }
        return $v;
    }

    private function parseUnary(): float|string
    {
        if ($this->atOp('-')) {
            $this->pos++;
            return -self::toNum($this->parseUnary());
        }
        if ($this->atOp('+')) {
            $this->pos++;
            return $this->parseUnary();
        }
        return $this->parsePower();
    }

    private function parsePower(): float|string
    {
        $v = $this->parsePrimary();
        if ($this->atOp('^')) {
            $this->pos++;
            return self::toNum($v) ** self::toNum($this->parseUnary());
        }
        return $v;
    }

    private function parsePrimary(): float|string
    {
        $tok = $this->toks[$this->pos] ?? null;
        if ($tok === null) {
            throw new PilotError('expression ends too soon', $this->line);
        }
        $this->pos++;

        switch ($tok[0]) {
            case 'num':
            case 'str':
                return $tok[1];

            case 'nvar':
                if ($this->atOp('(')) {
                    return $this->store->getNumAt($tok[1], $this->index(), $this->line);
                }
                return $this->store->getNum($tok[1]);

            case 'svar':
                if ($this->atOp('(')) {
                    return $this->store->getStrAt($tok[1], $this->index(), $this->line);
                }
                return $this->store->getStr($tok[1]);

            case 'ident':
                return $this->call($tok[1]);

            case 'op':
                if ($tok[1] === '(') {
                    $v = $this->parseOr();
                    $this->expectOp(')');
                    return $v;
                }
        }

        throw new PilotError('unexpected "' . self::render($tok) . '" in expression', $this->line);
    }

    private function index(): int
    {
        $this->expectOp('(');
        $i = (int) self::toNum($this->parseOr());
        $this->expectOp(')');
        return $i;
    }

    /** @return float|string */
    private function call(string $name): float|string
    {
        $args = [];
        if ($this->atOp('(')) {
            $this->pos++;
            if (!$this->atOp(')')) {
                $args[] = $this->parseOr();
                while ($this->atOp(',')) {
                    $this->pos++;
                    $args[] = $this->parseOr();
                }
            }
            $this->expectOp(')');
        }

        $num = fn(int $i): float => self::toNum($args[$i] ?? 0.0);
        $str = fn(int $i): string => self::toStr($args[$i] ?? '');
        $need = function (int $k) use ($name, $args): void {
            if (count($args) < $k) {
                throw new PilotError("$name() needs $k argument" . ($k === 1 ? '' : 's'), $this->line);
            }
        };

        switch ($name) {
            case 'PI':    return M_PI;
            case 'TIME':  return (float) time();

            case 'ABS':   $need(1); return abs($num(0));
            case 'INT':   $need(1); return floor($num(0));
            case 'ROUND': $need(1); return round($num(0), (int) $num(1));
            case 'SGN':   $need(1); return (float) ($num(0) <=> 0.0);
            case 'SQR':   $need(1);
                if ($num(0) < 0) {
                    throw new PilotError('SQR of a negative number', $this->line);
                }
                return sqrt($num(0));
            case 'SIN':   $need(1); return sin($num(0));
            case 'COS':   $need(1); return cos($num(0));
            case 'TAN':   $need(1); return tan($num(0));
            case 'ATN':   $need(1); return atan($num(0));
            case 'EXP':   $need(1); return exp($num(0));
            case 'LOG':   $need(1);
                if ($num(0) <= 0) {
                    throw new PilotError('LOG of a non-positive number', $this->line);
                }
                return log($num(0));
            case 'MIN':   $need(2); return min(array_map(self::toNum(...), $args));
            case 'MAX':   $need(2); return max(array_map(self::toNum(...), $args));

            // RND() gives 0..1; RND(n) gives a whole number from 1 to n.
            case 'RND':
                if ($args === []) {
                    return mt_rand() / mt_getrandmax();
                }
                $top = (int) $num(0);
                if ($top < 1) {
                    return 0.0;
                }
                return (float) mt_rand(1, $top);

            case 'LEN':   $need(1); return (float) Petscii::length($str(0));
            case 'PET':   $need(1);
                $char = Petscii::char($str(0));
                if ($char === null) {
                    throw new PilotError("there is no PETSCII character called '{$str(0)}'", $this->line);
                }
                return $char;
            case 'VAL':   $need(1); return self::toNum($str(0));
            case 'STR':   $need(1); return self::toStr($args[0]);
            case 'ASC':   $need(1); return (float) ord($str(0) === '' ? "\0" : $str(0)[0]);
            case 'CHR':   $need(1); return chr(((int) $num(0)) & 0xFF);
            case 'UPPER': $need(1); return strtoupper($str(0));
            case 'LOWER': $need(1); return strtolower($str(0));
            case 'TRIM':  $need(1); return trim($str(0));
            case 'LEFT':  $need(2); return Petscii::slice($str(0), 0, max(0, (int) $num(1)));
            case 'RIGHT': $need(2);
                $n = max(0, (int) $num(1));
                return $n === 0 ? '' : Petscii::slice($str(0), -$n);
            case 'MID':   $need(2);
                $start = max(1, (int) $num(1)) - 1;
                return count($args) >= 3
                    ? Petscii::slice($str(0), $start, max(0, (int) $num(2)))
                    : Petscii::slice($str(0), $start);
            case 'INSTR': $need(2);
                $at = stripos($str(0), $str(1));
                return $at === false
                    ? 0.0
                    : (float) (Petscii::length(substr($str(0), 0, $at)) + 1);
        }

        throw new PilotError("unknown function $name()", $this->line);
    }
}

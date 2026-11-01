<?php
declare(strict_types=1);

namespace Pilot;

/**
 * Turns PILOT source text into a Program.
 *
 * A line looks like:   [*LABEL] COMMAND[Y|N][(expression)] : operand
 * Leading C64 line numbers are accepted and recorded as jump targets, so a
 * listing typed in from the Commodore PILOT editor runs unchanged.
 */
final class Parser
{
    private const COMMANDS = [
        'R' => 'R',  'REM' => 'R',  'REMARK' => 'R',
        'T' => 'T',  'TYPE' => 'T',
        'TH' => 'TH', 'TYPEH' => 'TH',
        'A' => 'A',  'ACC' => 'A', 'ACCEPT' => 'A',
        'M' => 'M',  'MAT' => 'M', 'MATCH' => 'M',
        'J' => 'J',  'JUMP' => 'J',
        'U' => 'U',  'USE' => 'U',
        'E' => 'E',  'END' => 'E',
        'C' => 'C',  'COMP' => 'C', 'COMPUTE' => 'C',
        'PA' => 'PA', 'PAUSE' => 'PA',
        'D' => 'D',  'DIM' => 'D',
        'G' => 'G',  'GR' => 'G', 'GRAPHIC' => 'G', 'GRAPHICS' => 'G',
        'S' => 'S',  'SO' => 'S', 'SOUND' => 'S',
        'SC' => 'SC', 'SCREEN' => 'SC',
        'L' => 'L',  'LINK' => 'L',
    ];

    /** Commands whose operand is literal text, where spacing matters. */
    private const VERBATIM = ['T' => true, 'TH' => true, 'R' => true];

    public static function parseFile(string $path): Program
    {
        $src = @file_get_contents($path);
        if ($src === false) {
            throw new PilotError("cannot read '$path'");
        }
        return self::parse($src, $path);
    }

    public static function parse(string $src, string $path = '<source>'): Program
    {
        $program = new Program($path);
        $lines = preg_split('/\r\n|\r|\n/', $src) ?: [];

        foreach ($lines as $i => $raw) {
            $no = $i + 1;
            $line = ltrim($raw, " \t");
            if (trim($line) === '') {
                continue;
            }

            // A leading line number is a label you can jump to, as on the C64.
            if (preg_match('/^(\d+)\s*(.*)$/s', $line, $m)) {
                self::addLabel($program, $m[1], $no);
                $line = ltrim($m[2]);
            }

            // One or more *LABEL marks, then (optionally) a statement.
            while ($line !== '' && $line[0] === '*') {
                if (!preg_match('/^\*([A-Za-z_][A-Za-z0-9_]*)\s*(.*)$/s', $line, $m)) {
                    throw new PilotError('a label must be *NAME', $no);
                }
                self::addLabel($program, $m[1], $no);
                $line = ltrim($m[2]);
            }

            if (trim($line) === '') {
                continue;
            }
            $program->stmts[] = self::statement($line, $no);
        }

        return $program;
    }

    private static function addLabel(Program $program, string $name, int $no): void
    {
        $key = strtoupper($name);
        if (isset($program->labels[$key])) {
            throw new PilotError("duplicate label *$key", $no);
        }
        $program->labels[$key] = count($program->stmts);
    }

    private static function statement(string $line, int $no): Statement
    {
        if (!preg_match('/^[A-Za-z]+/', $line, $m)) {
            throw new PilotError('a statement must start with a command letter', $no);
        }
        $word = strtoupper($m[0]);
        $i = strlen($m[0]);

        while (isset($line[$i]) && ($line[$i] === ' ' || $line[$i] === "\t")) {
            $i++;
        }

        // An (expression) conditioner, balanced so nested parens survive.
        $cond = null;
        if (($line[$i] ?? '') === '(') {
            $depth = 0;
            $start = $i + 1;
            for ($j = $i, $n = strlen($line); $j < $n; $j++) {
                if ($line[$j] === '(') {
                    $depth++;
                } elseif ($line[$j] === ')') {
                    $depth--;
                    if ($depth === 0) {
                        break;
                    }
                }
            }
            if ($depth !== 0) {
                throw new PilotError('unclosed ( in conditioner', $no);
            }
            $cond = substr($line, $start, $j - $start);
            $i = $j + 1;
            while (isset($line[$i]) && ($line[$i] === ' ' || $line[$i] === "\t")) {
                $i++;
            }
        }

        [$op, $flag] = self::resolve($word, $no);

        $arg = '';
        if (isset($line[$i])) {
            if ($line[$i] !== ':') {
                throw new PilotError("expected ':' after $word", $no);
            }
            $arg = substr($line, $i + 1);
        }
        if (!isset(self::VERBATIM[$op])) {
            $arg = trim($arg);
        }

        return new Statement($op, $flag, $cond, $arg, $no, $line);
    }

    /** @return array{0:string,1:?string} */
    private static function resolve(string $word, int $no): array
    {
        if (isset(self::COMMANDS[$word])) {
            return [self::COMMANDS[$word], null];
        }
        $flag = substr($word, -1);
        $stem = substr($word, 0, -1);
        if (($flag === 'Y' || $flag === 'N') && isset(self::COMMANDS[$stem])) {
            return [self::COMMANDS[$stem], $flag];
        }
        throw new PilotError("unknown command '$word'", $no);
    }
}

#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Runs every tests/cases/<name>.plt and compares stdout+stderr, plus the exit
 * status, against <name>.out.
 *
 *   <name>.in    fed to the program as standard input (optional)
 *   <name>.args  extra command-line flags (optional)
 *
 * Every case runs with --fast --seed 1 --no-graphics so that pauses, RND()
 * and stray output files cannot make a run differ from the one before.
 */

$root = dirname(__DIR__);
$bin = $root . '/bin/pilot';
$dir = __DIR__ . '/cases';
$update = in_array('--update', $argv, true);
$filter = null;
foreach (array_slice($argv, 1) as $arg) {
    if (!str_starts_with($arg, '-')) {
        $filter = $arg;
    }
}

$pass = 0;
$fail = 0;

foreach (glob($dir . '/*.plt') ?: [] as $case) {
    $name = basename($case, '.plt');
    if ($filter !== null && !str_contains($name, $filter)) {
        continue;
    }

    $args = is_file("$dir/$name.args") ? trim((string) file_get_contents("$dir/$name.args")) : '';
    $stdin = is_file("$dir/$name.in") ? "$dir/$name.in" : '/dev/null';

    $cmd = sprintf(
        'cd %s && %s %s --fast --seed 1 --no-graphics %s %s < %s 2>&1; echo "[exit $?]"',
        escapeshellarg($dir),
        escapeshellarg(PHP_BINARY),
        escapeshellarg($bin),
        $args,
        escapeshellarg($case),
        escapeshellarg($stdin),
    );
    $actual = (string) shell_exec($cmd);

    if ($update) {
        file_put_contents("$dir/$name.out", $actual);
        echo "updated $name\n";
        continue;
    }

    $expected = is_file("$dir/$name.out") ? (string) file_get_contents("$dir/$name.out") : '';
    if ($actual === $expected) {
        $pass++;
        echo "ok    $name\n";
        continue;
    }

    $fail++;
    echo "FAIL  $name\n";
    echo "  --- expected ---\n" . indent($expected);
    echo "  --- actual -----\n" . indent($actual);
}

// Things that are about a written file rather than about printed text.
if (!$update && $filter === null) {
    [$pass, $fail] = artefacts($bin, $dir, $pass, $fail);
}

if (!$update) {
    echo "\n$pass passed, $fail failed\n";
}
exit($fail === 0 ? 0 : 1);

function indent(string $text): string
{
    $lines = explode("\n", rtrim($text, "\n"));
    return '  | ' . implode("\n  | ", $lines) . "\n";
}

/** @return array{0:int,1:int} */
function artefacts(string $bin, string $dir, int $pass, int $fail): array
{
    $tmp = sys_get_temp_dir() . '/pilot-test-' . getmypid();
    @mkdir($tmp);

    $checks = [
        'graphics writes an svg' => function () use ($bin, $tmp): ?string {
            $svg = "$tmp/out.svg";
            run($bin, ['-e', 'G:CLEAR PEN RED DRAW 40 TURN 90 DRAW 40', '--graphics', $svg]);
            if (!is_file($svg)) {
                return 'no file written';
            }
            $text = (string) file_get_contents($svg);
            if (!str_contains($text, 'viewBox="0 0 320 200"')) {
                return 'wrong canvas';
            }
            if (substr_count($text, '<path') !== 1 || !str_contains($text, '#880000')) {
                return 'expected one red path';
            }
            // Two 40-unit sides from the middle, facing up then right.
            if (!str_contains($text, 'M160.00 100.00L160.00 60.00') || !str_contains($text, 'L200.00 60.00')) {
                return 'turtle went somewhere unexpected';
            }
            return null;
        },

        'no drawing means no file' => function () use ($bin, $tmp): ?string {
            $svg = "$tmp/none.svg";
            @unlink($svg);
            run($bin, ['-e', 'T:NOTHING DRAWN', '--graphics', $svg]);
            return is_file($svg) ? 'wrote a file anyway' : null;
        },

        'sound writes a playable wav' => function () use ($bin, $tmp): ?string {
            $wav = "$tmp/out.wav";
            run($bin, ['-e', 'S:A4 60 R 30 A5/60', '--sound', $wav]);
            if (!is_file($wav)) {
                return 'no file written';
            }
            $text = (string) file_get_contents($wav);
            if (!str_starts_with($text, 'RIFF') || substr($text, 8, 4) !== 'WAVE') {
                return 'not a RIFF/WAVE file';
            }
            $header = unpack('Vsize', substr($text, 4, 4));
            if ($header['size'] + 8 !== strlen($text)) {
                return 'RIFF size does not match the file';
            }
            // 1s + 0.5s + 1s at 22050Hz, 16-bit mono.
            $expected = (int) round(2.5 * 22050) * 2;
            $data = unpack('Vsize', substr($text, 40, 4));
            if (abs($data['size'] - $expected) > 64) {
                return "data chunk is {$data['size']} bytes, expected about $expected";
            }
            return null;
        },

        'the c64 screen draws a 40x25 frame' => function () use ($bin): ?string {
            // Fifty characters must break after exactly forty.
            [$out] = run($bin, ['--c64', '-e', 'T:' . str_repeat('X', 50)]);
            if (!str_contains($out, "\e[2J")) {
                return 'never cleared the screen';
            }
            if (!str_contains($out, "\e[48;2;0;136;255m")) {
                return 'no light blue border';
            }
            if (!str_contains($out, "\e[48;2;0;0;170m")) {
                return 'no blue paper';
            }
            // Between column 40 and column 1 of the next row there has to
            // be a cursor move, so no unbroken run may exceed forty.
            $longest = 0;
            foreach (preg_split('/\e\[[0-9;]*[A-Za-z]/', $out) ?: [] as $piece) {
                if (preg_match('/X+/', $piece, $run)) {
                    $longest = max($longest, strlen($run[0]));
                }
            }
            if ($longest !== 40) {
                return "longest unbroken run is $longest, expected 40";
            }
            // 25 screen rows plus one border row top and bottom is 27,
            // and on the way out the cursor parks on row 28, below it all.
            preg_match_all('/\e\[(\d+);1H/', $out, $m);
            $rows = array_unique(array_map('intval', $m[1]));
            sort($rows);
            if ($rows !== range(1, 28)) {
                return 'frame rows are ' . implode(',', $rows);
            }
            return null;
        },

        'sound stays silent without --sound' => function () use ($bin, $tmp): ?string {
            [$out, $code] = run($bin, ['-e', 'S:C4 10', '--graphics', "$tmp/x.svg"]);
            return ($out === '' && $code === 0) ? null : 'unexpected output: ' . $out;
        },
    ];

    foreach ($checks as $label => $check) {
        $problem = $check();
        if ($problem === null) {
            $pass++;
            echo "ok    $label\n";
        } else {
            $fail++;
            echo "FAIL  $label: $problem\n";
        }
    }

    array_map('unlink', glob("$tmp/*") ?: []);
    @rmdir($tmp);

    return [$pass, $fail];
}

/** @return array{0:string,1:int} */
function run(string $bin, array $args): array
{
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($bin) . ' '
        . implode(' ', array_map('escapeshellarg', $args)) . ' 2>&1';
    exec($cmd, $lines, $code);
    return [implode("\n", $lines), $code];
}

<?php
declare(strict_types=1);

namespace Pilot;

/**
 * The C64's graphic characters, by name.
 *
 * On the real machine you typed these by holding Shift or the Commodore key
 * and pressing a letter, which is no use in a file of ASCII, so each one has
 * a name instead: {HEART}, {UL}, {BLOCK}. The terminal has no PETSCII font,
 * so each is drawn with the nearest thing Unicode offers.
 */
final class Petscii
{
    public const CHARS = [
        // Lines and the corners and junctions that go with them.
        'HLINE' => '─', 'VLINE' => '│',
        'UL' => '┌', 'UR' => '┐', 'LL' => '└', 'LR' => '┘',
        'TLEFT' => '├', 'TRIGHT' => '┤', 'TTOP' => '┬', 'TBOT' => '┴',
        'CROSS' => '┼',

        // Solid and part-solid blocks.
        'BLOCK' => '█',
        'UPPER' => '▀', 'LOWER' => '▄', 'LEFTH' => '▌', 'RIGHTH' => '▐',
        'LTSHADE' => '░', 'MDSHADE' => '▒', 'DKSHADE' => '▓',
        'QUL' => '▘', 'QUR' => '▝', 'QLL' => '▖', 'QLR' => '▗',
        'DIAGA' => '▚', 'DIAGB' => '▞',

        // Bars that grow upwards, for charts: BAR1 to BAR8.
        'BAR1' => '▁', 'BAR2' => '▂', 'BAR3' => '▃', 'BAR4' => '▄',
        'BAR5' => '▅', 'BAR6' => '▆', 'BAR7' => '▇', 'BAR8' => '█',

        // And the same growing rightwards: COL1 to COL8.
        'COL1' => '▏', 'COL2' => '▎', 'COL3' => '▍', 'COL4' => '▌',
        'COL5' => '▋', 'COL6' => '▊', 'COL7' => '▉', 'COL8' => '█',

        // The oddments the character set was loved for.
        'HEART' => '♥', 'DIAMOND' => '♦', 'CLUB' => '♣', 'SPADE' => '♠',
        'BALL' => '●', 'CIRCLE' => '○',
        'DIAGUP' => '╱', 'DIAGDN' => '╲', 'DIAGX' => '╳',
        'PI' => 'π', 'CHECK' => '✓',
        'ARROWUP' => '↑', 'ARROWLEFT' => '←', 'DOT' => '·',
    ];

    public static function char(string $name): ?string
    {
        return self::CHARS[strtoupper(trim($name))] ?? null;
    }

    /**
     * Replace every {NAME} that names a graphic. Anything else inside
     * braces is left exactly as it was written.
     */
    public static function expand(string $s): string
    {
        if (!str_contains($s, '{')) {
            return $s;
        }
        return preg_replace_callback(
            '/\{([A-Za-z][A-Za-z0-9]*)\}/',
            static fn(array $m): string => self::char($m[1]) ?? $m[0],
            $s,
        ) ?? $s;
    }

    /** Split into characters rather than bytes; a graphic is several bytes. */
    public static function split(string $s): array
    {
        $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
        // Invalid UTF-8 makes preg_split fail; fall back to bytes.
        return $chars === false ? str_split($s === '' ? ' ' : $s) : $chars;
    }

    public static function length(string $s): int
    {
        return count(self::split($s));
    }

    public static function slice(string $s, int $start, ?int $length = null): string
    {
        $chars = self::split($s);
        $part = $length === null
            ? array_slice($chars, $start)
            : array_slice($chars, $start, $length);
        return implode('', $part);
    }
}

<?php
declare(strict_types=1);

namespace Pilot;

/**
 * The turtle behind G: (GRAPHICS).
 *
 * Commodore PILOT drew on the C64's 320x200 hi-res screen with a turtle that
 * started in the middle facing up, so that is what this is. The drawing is
 * collected as line segments and written out as an SVG when the program ends.
 */
final class Turtle
{
    /** The 16 C64 colours, in their usual order. */
    public const PALETTE = [
        '#000000', '#FFFFFF', '#880000', '#AAFFEE',
        '#CC44CC', '#00CC55', '#0000AA', '#EEEE77',
        '#DD8855', '#664400', '#FF7777', '#333333',
        '#777777', '#AAFF66', '#0088FF', '#BBBBBB',
    ];

    private const NAMES = [
        'BLACK' => 0, 'WHITE' => 1, 'RED' => 2, 'CYAN' => 3,
        'PURPLE' => 4, 'GREEN' => 5, 'BLUE' => 6, 'YELLOW' => 7,
        'ORANGE' => 8, 'BROWN' => 9, 'LTRED' => 10, 'DKGREY' => 11,
        'GREY' => 12, 'LTGREEN' => 13, 'LTBLUE' => 14, 'LTGREY' => 15,
    ];

    public const WIDTH = 320;
    public const HEIGHT = 200;

    private float $x;
    private float $y;
    private float $heading = 0.0;     // degrees clockwise from straight up
    private bool $down = true;
    private int $pen = 1;             // white
    private int $background = 6;      // C64 blue

    /** @var array<int,array{0:float,1:float,2:float,3:float,4:int}> */
    private array $segments = [];
    private bool $used = false;

    public function __construct()
    {
        $this->home();
    }

    public function used(): bool
    {
        return $this->used;
    }

    private function home(): void
    {
        $this->x = self::WIDTH / 2;
        $this->y = self::HEIGHT / 2;
        $this->heading = 0.0;
    }

    /**
     * Run a whole G: operand. Commas and semicolons are just whitespace, so
     * "G:CLEAR;GO 50;TURN 90" and "G:CLEAR, GO 50, TURN 90" are the same.
     */
    public function command(string $arg, ?int $line = null): void
    {
        $this->used = true;
        $words = preg_split('/[\s,;]+/', trim($arg), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $i = 0;
        $count = count($words);

        $number = function () use ($words, &$i, $count, $line): float {
            if ($i >= $count || !is_numeric($words[$i])) {
                throw new PilotError('G: expected a number', $line);
            }
            return (float) $words[$i++];
        };

        while ($i < $count) {
            $word = strtoupper($words[$i++]);
            switch ($word) {
                case 'CLEAR':
                case 'CLR':
                case 'CLEARSCREEN':
                    $this->segments = [];
                    $this->home();
                    break;

                case 'HOME':
                    $this->home();
                    break;

                case 'GO':
                    $this->forward($number(), $this->down);
                    break;

                case 'DRAW':
                    $this->forward($number(), true);
                    break;

                case 'MOVE':
                    $this->forward($number(), false);
                    break;

                case 'GOTO':
                    $this->to($number(), $number(), false);
                    break;

                case 'DRAWTO':
                    $this->to($number(), $number(), true);
                    break;

                case 'TURN':
                    $this->heading = fmod($this->heading + $number(), 360.0);
                    break;

                case 'TURNTO':
                    $this->heading = fmod($number(), 360.0);
                    break;

                case 'PEN':
                    $this->setPen($words[$i] ?? '', $line);
                    $i++;
                    break;

                case 'PAPER':
                    $this->background = $this->colour($words[$i] ?? '', $line);
                    $i++;
                    break;

                case 'QUIT':
                case 'TEXT':
                    break;   // back to text mode; the drawing stays as it is

                default:
                    throw new PilotError("G: does not know '$word'", $line);
            }
        }
    }

    private function setPen(string $word, ?int $line): void
    {
        switch (strtoupper($word)) {
            case 'UP':
                $this->down = false;
                return;
            case 'DOWN':
                $this->down = true;
                return;
            case 'ERASE':
                $this->pen = $this->background;
                return;
            default:
                // Only UP and DOWN move the pen; a colour is just a colour.
                $this->pen = $this->colour($word, $line);
        }
    }

    private function colour(string $word, ?int $line): int
    {
        $w = strtoupper($word);
        if ($w !== '' && is_numeric($w)) {
            return ((int) $w) & 15;
        }
        if (isset(self::NAMES[$w])) {
            return self::NAMES[$w];
        }
        throw new PilotError("G: PEN does not know '$word'", $line);
    }

    private function forward(float $distance, bool $draw): void
    {
        $rad = deg2rad($this->heading);
        $this->to(
            $this->x + sin($rad) * $distance,
            $this->y - cos($rad) * $distance,
            $draw,
        );
    }

    private function to(float $x, float $y, bool $draw): void
    {
        if ($draw) {
            $this->segments[] = [$this->x, $this->y, $x, $y, $this->pen];
        }
        $this->x = $x;
        $this->y = $y;
    }

    public function toSvg(int $scale = 2): string
    {
        $w = self::WIDTH * $scale;
        $h = self::HEIGHT * $scale;

        // One path per colour keeps the file small and readable.
        $byColour = [];
        foreach ($this->segments as [$x1, $y1, $x2, $y2, $pen]) {
            $byColour[$pen][] = sprintf('M%.2f %.2fL%.2f %.2f', $x1, $y1, $x2, $y2);
        }

        $out = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">' . "\n",
            $w, $h, self::WIDTH, self::HEIGHT,
        );
        $out .= sprintf('  <rect width="%d" height="%d" fill="%s"/>' . "\n",
            self::WIDTH, self::HEIGHT, self::PALETTE[$this->background]);

        foreach ($byColour as $pen => $parts) {
            $out .= sprintf(
                '  <path d="%s" fill="none" stroke="%s" stroke-width="1" stroke-linecap="round"/>' . "\n",
                implode('', $parts),
                self::PALETTE[$pen],
            );
        }

        return $out . '</svg>' . "\n";
    }
}

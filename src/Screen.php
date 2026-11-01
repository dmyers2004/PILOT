<?php
declare(strict_types=1);

namespace Pilot;

/**
 * A Commodore 64 screen, drawn in the terminal with ANSI escapes.
 *
 * Forty columns by twenty-five rows, in the same sixteen colours the turtle
 * draws with. Text wraps at column 40 and the screen scrolls at the bottom,
 * so a program written for the real thing lays out the way it was meant to.
 *
 * Colour works the way it did on the machine: the border and the background
 * are one colour each for the whole screen, while the ink belongs to each
 * character cell, as it did in colour RAM. So changing the ink colours what
 * you type next and leaves what is already up there alone.
 *
 * Output goes straight to the cursor while it is just moving along a line.
 * A scroll, or any change to the background or border, repaints the frame.
 */
final class Screen
{
    public const COLS = 40;
    public const ROWS = 25;

    private const BORDER_X = 2;   // columns of border down each side
    private const BORDER_Y = 1;   // rows of border top and bottom

    /** @var array<int,string> ROWS lines of exactly COLS characters */
    private array $buf = [];
    /** @var array<int,array<int,int>> the ink of every cell */
    private array $cellInk = [];
    /** @var array<int,array<int,bool>> which cells are in reverse video */
    private array $cellRev = [];

    private int $x = 0;
    private int $y = 0;
    private bool $open = false;

    private int $ink = 14;      // light blue
    private int $paper = 6;     // blue
    private int $border = 14;   // light blue
    private bool $reverse = false;

    private const NAMES = [
        'BLACK' => 0, 'WHITE' => 1, 'RED' => 2, 'CYAN' => 3,
        'PURPLE' => 4, 'GREEN' => 5, 'BLUE' => 6, 'YELLOW' => 7,
        'ORANGE' => 8, 'BROWN' => 9, 'LTRED' => 10, 'DKGREY' => 11,
        'GREY' => 12, 'LTGREEN' => 13, 'LTBLUE' => 14, 'LTGREY' => 15,
    ];

    /**
     * @param resource $out
     * @param bool $live false still tracks state and reports bad SC: words,
     *                   but puts nothing on the terminal -- which is what a
     *                   run without --c64 wants.
     */
    public function __construct(private $out, private readonly bool $live = true)
    {
        $this->blank();
    }

    private function blank(): void
    {
        $this->buf = array_fill(0, self::ROWS, str_repeat(' ', self::COLS));
        $this->cellInk = array_fill(0, self::ROWS, array_fill(0, self::COLS, $this->ink));
        $this->cellRev = array_fill(0, self::ROWS, array_fill(0, self::COLS, false));
        $this->x = 0;
        $this->y = 0;
    }

    // ------------------------------------------------------------- colours

    private static function parts(int $colour): array
    {
        $hex = ltrim(Turtle::PALETTE[$colour & 15], '#');
        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    private static function fg(int $colour): string
    {
        [$r, $g, $b] = self::parts($colour);
        return "\e[38;2;$r;$g;{$b}m";
    }

    private static function bg(int $colour): string
    {
        [$r, $g, $b] = self::parts($colour);
        return "\e[48;2;$r;$g;{$b}m";
    }

    /** Reverse video swaps the cell's ink with the background. */
    private function pen(int $ink, bool $reverse): string
    {
        return $reverse
            ? self::bg($ink) . self::fg($this->paper)
            : self::bg($this->paper) . self::fg($ink);
    }

    // ------------------------------------------------------- the SC: words

    public function command(string $arg, ?int $line = null): void
    {
        $words = preg_split('/[\s,;]+/', trim($arg), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $i = 0;
        $count = count($words);

        $number = function () use ($words, &$i, $count, $line): int {
            if ($i >= $count || !is_numeric($words[$i])) {
                throw new PilotError('SC: expected a number', $line);
            }
            return (int) $words[$i++];
        };
        $colour = function () use ($words, &$i, $count, $line): int {
            $word = strtoupper($words[$i++] ?? '');
            if ($word !== '' && is_numeric($word)) {
                return ((int) $word) & 15;
            }
            if (isset(self::NAMES[$word])) {
                return self::NAMES[$word];
            }
            throw new PilotError("SC: does not know the colour '$word'", $line);
        };

        $repaint = false;

        while ($i < $count) {
            $word = strtoupper($words[$i++]);
            switch ($word) {
                case 'INK':
                case 'PEN':
                    $this->ink = $colour();
                    break;

                case 'PAPER':
                    $this->paper = $colour();
                    $repaint = true;
                    break;

                case 'BORDER':
                    $this->border = $colour();
                    $repaint = true;
                    break;

                case 'REVERSE':
                case 'RVS':
                    $next = strtoupper($words[$i] ?? '');
                    if ($next === 'ON' || $next === 'OFF') {
                        $this->reverse = $next === 'ON';
                        $i++;
                    } else {
                        $this->reverse = !$this->reverse;
                    }
                    break;

                case 'CLEAR':
                case 'CLR':
                    $this->blank();
                    $repaint = true;
                    break;

                case 'HOME':
                    $this->x = 0;
                    $this->y = 0;
                    break;

                case 'AT':
                    $row = $number();
                    $col = $number();
                    $this->y = max(0, min(self::ROWS - 1, $row - 1));
                    $this->x = max(0, min(self::COLS - 1, $col - 1));
                    break;

                default:
                    throw new PilotError("SC: does not know '$word'", $line);
            }
        }

        if (!$this->live) {
            return;
        }
        $this->emit($repaint && $this->open ? $this->repaint() : $this->cursor());
    }

    // --------------------------------------------------------------- frame

    public function start(): void
    {
        if ($this->open || !$this->live) {
            return;
        }
        $this->open = true;
        $this->emit("\e[2J" . $this->repaint());
    }

    /** Everything, frame and all, with the cursor left where it belongs. */
    private function repaint(): string
    {
        $width = self::COLS + 2 * self::BORDER_X;
        $bar = self::bg($this->border) . str_repeat(' ', $width) . "\e[0m";
        $side = self::bg($this->border) . str_repeat(' ', self::BORDER_X);

        $s = '';
        $row = 1;
        for ($i = 0; $i < self::BORDER_Y; $i++) {
            $s .= $this->at($row++, 1) . $bar;
        }
        for ($y = 0; $y < self::ROWS; $y++) {
            $s .= $this->at($row++, 1) . $side . $this->line($y) . $side . "\e[0m";
        }
        for ($i = 0; $i < self::BORDER_Y; $i++) {
            $s .= $this->at($row++, 1) . $bar;
        }

        return $s . $this->cursor();
    }

    /** One screen row, as runs of a single ink so the escapes stay few. */
    private function line(int $y): string
    {
        $s = '';
        $run = '';
        $ink = null;
        $rev = null;

        for ($x = 0; $x < self::COLS; $x++) {
            $cellInk = $this->cellInk[$y][$x];
            $cellRev = $this->cellRev[$y][$x];
            if ($cellInk !== $ink || $cellRev !== $rev) {
                $s .= $run;
                $s .= $this->pen($cellInk, $cellRev);
                $run = '';
                $ink = $cellInk;
                $rev = $cellRev;
            }
            $run .= $this->buf[$y][$x];
        }

        return $s . $run;
    }

    private function at(int $row, int $col): string
    {
        return "\e[$row;{$col}H";
    }

    /** Put the real cursor where ours is, in the colours now in force. */
    private function cursor(): string
    {
        return $this->at(self::BORDER_Y + 1 + $this->y, self::BORDER_X + 1 + $this->x)
            . $this->pen($this->ink, $this->reverse);
    }

    // -------------------------------------------------------------- output

    public function write(string $text): void
    {
        $this->start();

        // The C64 came up in its uppercase character set.
        $text = strtoupper($text);

        $s = $this->cursor();
        $scrolled = false;

        for ($i = 0, $n = strlen($text); $i < $n; $i++) {
            $c = $text[$i];

            if ($c === "\n") {
                $scrolled = $this->newline() || $scrolled;
                $s .= $this->cursor();
                continue;
            }
            if ($c === "\r") {
                continue;
            }
            if ($c === "\t") {
                $c = ' ';
            }

            $this->put($c);
            $s .= $c;
            $this->x++;

            if ($this->x >= self::COLS) {
                $scrolled = $this->newline() || $scrolled;
                $s .= $this->cursor();
            }
        }

        $this->emit($scrolled ? $this->repaint() : $s);
    }

    private function put(string $c): void
    {
        $this->buf[$this->y][$this->x] = $c;
        $this->cellInk[$this->y][$this->x] = $this->ink;
        $this->cellRev[$this->y][$this->x] = $this->reverse;
    }

    /** @return bool whether the screen scrolled */
    private function newline(): bool
    {
        $this->x = 0;
        $this->y++;
        if ($this->y < self::ROWS) {
            return false;
        }
        array_shift($this->buf);
        array_shift($this->cellInk);
        array_shift($this->cellRev);
        $this->buf[] = str_repeat(' ', self::COLS);
        $this->cellInk[] = array_fill(0, self::COLS, $this->ink);
        $this->cellRev[] = array_fill(0, self::COLS, false);
        $this->y = self::ROWS - 1;
        return true;
    }

    /**
     * A line the person typed. The terminal has already echoed it at the
     * cursor, so this only has to agree with what is on the glass -- and a
     * repaint settles any disagreement about where the echo ended up.
     */
    public function accepted(string $text): void
    {
        $this->start();
        $text = strtoupper($text);

        for ($i = 0, $n = strlen($text); $i < $n && $this->x < self::COLS; $i++) {
            $this->put($text[$i]);
            $this->x++;
        }
        $this->newline();
        $this->emit($this->repaint());
    }

    public function close(): void
    {
        if (!$this->open) {
            return;
        }
        $this->open = false;
        $row = self::ROWS + 2 * self::BORDER_Y + 1;
        $this->emit("\e[0m" . $this->at($row, 1) . "\n");
    }

    private function emit(string $s): void
    {
        if (!$this->live) {
            return;
        }
        fwrite($this->out, $s);
        fflush($this->out);
    }
}

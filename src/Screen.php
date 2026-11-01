<?php
declare(strict_types=1);

namespace Pilot;

/**
 * A Commodore 64 screen, drawn in the terminal with ANSI escapes.
 *
 * Forty columns by twenty-five rows, light blue on blue inside a light blue
 * border, in the same sixteen colours the turtle draws with. Text wraps at
 * column 40 and the screen scrolls at the bottom, so a program written for
 * the real thing lays out the way it was meant to.
 *
 * Output goes straight to the cursor while it is just moving along a line;
 * a scroll repaints the whole frame, which is both simpler and rare enough
 * not to matter.
 */
final class Screen
{
    public const COLS = 40;
    public const ROWS = 25;

    private const BORDER_X = 2;   // columns of border down each side
    private const BORDER_Y = 1;   // rows of border top and bottom

    /** @var array<int,string> ROWS lines of exactly COLS characters */
    private array $buf = [];
    private int $x = 0;
    private int $y = 0;
    private bool $open = false;

    /** @param resource $out */
    public function __construct(
        private $out,
        private readonly int $paper = 6,    // blue
        private readonly int $ink = 14,     // light blue
        private readonly int $border = 14,  // light blue
    ) {
        $this->buf = array_fill(0, self::ROWS, str_repeat(' ', self::COLS));
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

    // --------------------------------------------------------------- frame

    public function open(): void
    {
        if ($this->open) {
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

        $s = '';
        $row = 1;
        for ($i = 0; $i < self::BORDER_Y; $i++) {
            $s .= $this->at($row++, 1) . $bar;
        }
        foreach ($this->buf as $line) {
            $s .= $this->at($row++, 1)
                . self::bg($this->border) . str_repeat(' ', self::BORDER_X)
                . self::bg($this->paper) . self::fg($this->ink) . $line
                . self::bg($this->border) . str_repeat(' ', self::BORDER_X)
                . "\e[0m";
        }
        for ($i = 0; $i < self::BORDER_Y; $i++) {
            $s .= $this->at($row++, 1) . $bar;
        }

        return $s . $this->home();
    }

    private function at(int $row, int $col): string
    {
        return "\e[$row;{$col}H";
    }

    /** Put the real cursor where our own cursor is, in the screen's colours. */
    private function home(): string
    {
        return $this->at(self::BORDER_Y + 1 + $this->y, self::BORDER_X + 1 + $this->x)
            . self::bg($this->paper) . self::fg($this->ink);
    }

    // -------------------------------------------------------------- output

    public function write(string $text): void
    {
        $this->open();

        // The C64 came up in its uppercase character set.
        $text = strtoupper($text);

        $s = $this->home();
        $scrolled = false;

        for ($i = 0, $n = strlen($text); $i < $n; $i++) {
            $c = $text[$i];

            if ($c === "\n") {
                $scrolled = $this->newline() || $scrolled;
                $s .= $this->home();
                continue;
            }
            if ($c === "\r" || $c === "\t") {
                $c = $c === "\t" ? ' ' : '';
                if ($c === '') {
                    continue;
                }
            }

            $this->buf[$this->y][$this->x] = $c;
            $s .= $c;
            $this->x++;

            if ($this->x >= self::COLS) {
                $scrolled = $this->newline() || $scrolled;
                $s .= $this->home();
            }
        }

        $this->emit($scrolled ? $this->repaint() : $s);
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
        $this->buf[] = str_repeat(' ', self::COLS);
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
        $this->open();
        $text = strtoupper($text);

        for ($i = 0, $n = strlen($text); $i < $n && $this->x < self::COLS; $i++) {
            $this->buf[$this->y][$this->x] = $text[$i];
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
        fwrite($this->out, $s);
        fflush($this->out);
    }
}

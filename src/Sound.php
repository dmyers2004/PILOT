<?php
declare(strict_types=1);

namespace Pilot;

/**
 * S: (SOUND). Notes are collected as they are played and, if the run was
 * given --sound=FILE, rendered as a square-wave WAV -- the closest a PHP
 * process gets to a SID chip.
 *
 * Operand is a list of pitch/duration pairs. A pitch is a note name (C, C#4,
 * BB3, with octave 4 assumed) a bare frequency in Hz, or R for a rest. The
 * duration that follows is in jiffies (1/60s), as on the C64; it may be
 * written after a slash (C4/30) or as the next word (C4 30).
 */
final class Sound
{
    private const SEMITONE = ['C' => 0, 'D' => 2, 'E' => 4, 'F' => 5, 'G' => 7, 'A' => 9, 'B' => 11];

    private const RATE = 22050;
    private const DEFAULT_JIFFIES = 10.0;

    /** @var array<int,array{0:float,1:float}> [frequency in Hz (0 = rest), seconds] */
    private array $events = [];
    private bool $used = false;

    public function used(): bool
    {
        return $this->used;
    }

    public function seconds(): float
    {
        return array_sum(array_column($this->events, 1));
    }

    public function count(): int
    {
        return count($this->events);
    }

    public function command(string $arg, ?int $line = null): void
    {
        $this->used = true;
        $words = preg_split('/[\s,;]+/', trim($arg), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        for ($i = 0, $n = count($words); $i < $n; $i++) {
            $word = $words[$i];
            $jiffies = null;

            if (str_contains($word, '/')) {
                [$word, $after] = explode('/', $word, 2);
                if (!is_numeric($after)) {
                    throw new PilotError("S: '$after' is not a duration", $line);
                }
                $jiffies = (float) $after;
            } elseif (isset($words[$i + 1]) && is_numeric($words[$i + 1])) {
                $jiffies = (float) $words[++$i];
            }

            $this->events[] = [
                $this->pitch($word, $line),
                max(0.0, $jiffies ?? self::DEFAULT_JIFFIES) / 60.0,
            ];
        }
    }

    private function pitch(string $word, ?int $line): float
    {
        $w = strtoupper($word);
        if ($w === 'R' || $w === 'REST' || $w === 'P') {
            return 0.0;
        }
        if (is_numeric($w)) {
            return (float) $w;   // a plain frequency
        }
        if (!preg_match('/^([A-G])(#|S|B|-)?(-?\d)?$/', $w, $m)) {
            throw new PilotError("S: '$word' is not a note or a frequency", $line);
        }
        $semi = self::SEMITONE[$m[1]];
        $semi += match ($m[2] ?? '') {
            '#', 'S' => 1,
            'B', '-' => -1,
            default => 0,
        };
        $octave = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : 4;
        $midi = 12 * ($octave + 1) + $semi;

        return 440.0 * 2 ** (($midi - 69) / 12);
    }

    /** A 16-bit mono WAV of everything played so far. */
    public function toWav(): string
    {
        $samples = [];
        foreach ($this->events as [$freq, $seconds]) {
            $total = (int) round($seconds * self::RATE);
            if ($total <= 0) {
                continue;
            }
            // A short fade at each end; square waves click badly without one.
            $fade = (int) min($total / 2, self::RATE * 0.004);
            for ($i = 0; $i < $total; $i++) {
                if ($freq <= 0.0) {
                    $samples[] = 0;
                    continue;
                }
                $phase = fmod($i * $freq / self::RATE, 1.0);
                $level = $phase < 0.5 ? 8000 : -8000;
                if ($fade > 0) {
                    $env = min(1.0, min($i, $total - 1 - $i) / $fade);
                    $level = (int) round($level * $env);
                }
                $samples[] = $level;
            }
        }

        // Packed in chunks: a long tune is far too many samples to spread
        // into a single pack() call.
        $data = '';
        foreach (array_chunk($samples, 4096) as $chunk) {
            $data .= pack('v*', ...array_map(static fn(int $s): int => $s & 0xFFFF, $chunk));
        }
        $bytes = strlen($data);

        return 'RIFF' . pack('V', 36 + $bytes) . 'WAVE'
            . 'fmt ' . pack('V', 16) . pack('vvVVvv', 1, 1, self::RATE, self::RATE * 2, 2, 16)
            . 'data' . pack('V', $bytes) . $data;
    }
}

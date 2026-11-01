<?php
declare(strict_types=1);

namespace Pilot;

/**
 * The variable space: numerics (#X), strings ($X), their arrays, and the
 * yes/no flag that M: sets and the Y/N conditioners read.
 *
 * %B %M %L %R are the traditional PILOT buffer names; they are aliases for
 * $ANSWER, $MATCH, $LEFT and $RIGHT.
 */
final class Store
{
    /** The traditional buffer names, reachable as %B %M %L %R. */
    private const ALIAS = ['B' => 'ANSWER', 'M' => 'MATCH', 'L' => 'LEFT', 'R' => 'RIGHT'];

    /** @var array<string,float> */
    private array $num = [];
    /** @var array<string,string> */
    private array $str = [];
    /** @var array<string,array<int,float>> */
    private array $numArr = [];
    /** @var array<string,array<int,string>> */
    private array $strArr = [];

    private bool $match = false;

    /** The name %X stands for; only the % sigil goes through here. */
    public static function alias(string $name): string
    {
        $n = strtoupper($name);
        return self::ALIAS[$n] ?? $n;
    }

    public function canonical(string $name): string
    {
        return strtoupper($name);
    }

    public function getNum(string $name): float
    {
        return $this->num[$this->canonical($name)] ?? 0.0;
    }

    public function setNum(string $name, float $value): void
    {
        $this->num[$this->canonical($name)] = $value;
    }

    public function getStr(string $name): string
    {
        return $this->str[$this->canonical($name)] ?? '';
    }

    public function setStr(string $name, string $value): void
    {
        $this->str[$this->canonical($name)] = $value;
    }

    public function dimNum(string $name, int $size, ?int $line = null): void
    {
        $this->numArr[$this->canonical($name)] = array_fill(0, $this->checkSize($size, $line) + 1, 0.0);
    }

    public function dimStr(string $name, int $size, ?int $line = null): void
    {
        $this->strArr[$this->canonical($name)] = array_fill(0, $this->checkSize($size, $line) + 1, '');
    }

    private function checkSize(int $size, ?int $line): int
    {
        if ($size < 0 || $size > 100000) {
            throw new PilotError("bad array size $size", $line);
        }
        return $size;
    }

    public function getNumAt(string $name, int $i, ?int $line = null): float
    {
        $n = $this->canonical($name);
        if (!isset($this->numArr[$n])) {
            throw new PilotError("#$n has no dimensions; use D:#$n(size)", $line);
        }
        if (!array_key_exists($i, $this->numArr[$n])) {
            throw new PilotError("#$n($i) is out of range", $line);
        }
        return $this->numArr[$n][$i];
    }

    public function setNumAt(string $name, int $i, float $v, ?int $line = null): void
    {
        $n = $this->canonical($name);
        if (!isset($this->numArr[$n])) {
            throw new PilotError("#$n has no dimensions; use D:#$n(size)", $line);
        }
        if (!array_key_exists($i, $this->numArr[$n])) {
            throw new PilotError("#$n($i) is out of range", $line);
        }
        $this->numArr[$n][$i] = $v;
    }

    public function getStrAt(string $name, int $i, ?int $line = null): string
    {
        $n = $this->canonical($name);
        if (!isset($this->strArr[$n])) {
            throw new PilotError("\$$n has no dimensions; use D:\$$n(size)", $line);
        }
        if (!array_key_exists($i, $this->strArr[$n])) {
            throw new PilotError("\$$n($i) is out of range", $line);
        }
        return $this->strArr[$n][$i];
    }

    public function setStrAt(string $name, int $i, string $v, ?int $line = null): void
    {
        $n = $this->canonical($name);
        if (!isset($this->strArr[$n])) {
            throw new PilotError("\$$n has no dimensions; use D:\$$n(size)", $line);
        }
        if (!array_key_exists($i, $this->strArr[$n])) {
            throw new PilotError("\$$n($i) is out of range", $line);
        }
        $this->strArr[$n][$i] = $v;
    }

    public function match(): bool
    {
        return $this->match;
    }

    public function setMatch(bool $yes): void
    {
        $this->match = $yes;
    }
}

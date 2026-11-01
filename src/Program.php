<?php
declare(strict_types=1);

namespace Pilot;

final class Program
{
    /** @var Statement[] */
    public array $stmts = [];

    /** @var array<string,int> label (or line number) => index into $stmts */
    public array $labels = [];

    public function __construct(public readonly string $path)
    {
    }

    /**
     * Resolve a jump target. Accepts *LABEL, LABEL, or a C64-style line number.
     */
    public function labelPc(string $target, ?int $srcLine): int
    {
        $name = strtoupper(ltrim(trim($target), '*'));
        if ($name === '') {
            throw new PilotError('empty jump target', $srcLine);
        }
        if (!isset($this->labels[$name])) {
            throw new PilotError("no such label *$name", $srcLine);
        }
        return $this->labels[$name];
    }
}

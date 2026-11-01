<?php
declare(strict_types=1);

namespace Pilot;

/**
 * One PILOT statement: a command, its optional conditioners, and the raw text
 * after the colon. The operand is kept verbatim because T: prints its spaces.
 */
final class Statement
{
    public function __construct(
        public readonly string $op,
        public readonly ?string $flag,   // 'Y', 'N', or null
        public readonly ?string $cond,   // source of a (expression) conditioner
        public readonly string $arg,
        public readonly int $srcLine,
        public readonly string $source,
    ) {
    }
}

<?php
declare(strict_types=1);

namespace Pilot;

/**
 * Every error the interpreter raises, with the source line attached when known.
 */
class PilotError extends \RuntimeException
{
    public function __construct(string $message, public readonly ?int $srcLine = null)
    {
        parent::__construct($message);
    }

    public function format(string $file): string
    {
        $where = basename($file);
        if ($this->srcLine !== null) {
            $where .= ':' . $this->srcLine;
        }
        return 'pilot: ' . $where . ': ' . $this->getMessage();
    }
}

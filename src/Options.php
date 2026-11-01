<?php
declare(strict_types=1);

namespace Pilot;

final class Options
{
    /** Echo each statement to stderr before running it. */
    public bool $trace = false;

    /** Skip the delays asked for by PA: (pause). */
    public bool $fast = false;

    /** Echo accepted input back, so piped runs read like interactive ones. */
    public bool $echoInput = false;

    /** Draw output on a 40x25 Commodore 64 screen, in ANSI colour. */
    public bool $c64 = false;

    /** Where G: turtle drawings are written; null means "next to the cwd, named after the program". */
    public ?string $graphicsFile = null;
    public bool $graphicsOutput = true;

    /** Where S: sound is rendered as a WAV; null means sound is silent. */
    public ?string $soundFile = null;

    public ?int $seed = null;

    /** Abort after this many statements; 0 means no limit. */
    public int $maxSteps = 0;
}

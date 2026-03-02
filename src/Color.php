<?php

declare(strict_types=1);

namespace Tetris;

enum Color
{
    case Cyan;
    case Yellow;
    case Purple;
    case Green;
    case Red;
    case Blue;
    case Orange;
    case Gray;
    case White;
    case Reset;

    public function fg(): string
    {
        return match ($this) {
            self::Gray  => "\e[90m",
            self::Reset => "\e[0m",
            default     => "\e[95m",
        };
    }

    public function bg(): string
    {
        return match ($this) {
            self::Gray  => "\e[100m",
            self::Reset => "\e[0m",
            default     => "\e[45m",
        };
    }
}

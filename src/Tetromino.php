<?php

declare(strict_types=1);

namespace Tetris;

enum Tetromino: int
{
    case I = 0;
    case O = 1;
    case T = 2;
    case S = 3;
    case Z = 4;
    case J = 5;
    case L = 6;

    /**
     * Returns 4 rotation states, each an array of 4 [row, col] offsets
     * relative to the piece's top-left bounding box corner.
     */
    public function shapes(): array
    {
        return match ($this) {
            self::I => [
                [[1, 0], [1, 1], [1, 2], [1, 3]],
                [[0, 2], [1, 2], [2, 2], [3, 2]],
                [[2, 0], [2, 1], [2, 2], [2, 3]],
                [[0, 1], [1, 1], [2, 1], [3, 1]],
            ],
            self::O => [
                [[0, 0], [0, 1], [1, 0], [1, 1]],
                [[0, 0], [0, 1], [1, 0], [1, 1]],
                [[0, 0], [0, 1], [1, 0], [1, 1]],
                [[0, 0], [0, 1], [1, 0], [1, 1]],
            ],
            self::T => [
                [[0, 1], [1, 0], [1, 1], [1, 2]],
                [[0, 1], [1, 1], [1, 2], [2, 1]],
                [[1, 0], [1, 1], [1, 2], [2, 1]],
                [[0, 1], [1, 0], [1, 1], [2, 1]],
            ],
            self::S => [
                [[0, 1], [0, 2], [1, 0], [1, 1]],
                [[0, 1], [1, 1], [1, 2], [2, 2]],
                [[1, 1], [1, 2], [2, 0], [2, 1]],
                [[0, 0], [1, 0], [1, 1], [2, 1]],
            ],
            self::Z => [
                [[0, 0], [0, 1], [1, 1], [1, 2]],
                [[0, 2], [1, 1], [1, 2], [2, 1]],
                [[1, 0], [1, 1], [2, 1], [2, 2]],
                [[0, 1], [1, 0], [1, 1], [2, 0]],
            ],
            self::J => [
                [[0, 0], [1, 0], [1, 1], [1, 2]],
                [[0, 1], [0, 2], [1, 1], [2, 1]],
                [[1, 0], [1, 1], [1, 2], [2, 2]],
                [[0, 1], [1, 1], [2, 0], [2, 1]],
            ],
            self::L => [
                [[0, 2], [1, 0], [1, 1], [1, 2]],
                [[0, 1], [1, 1], [2, 1], [2, 2]],
                [[1, 0], [1, 1], [1, 2], [2, 0]],
                [[0, 0], [0, 1], [1, 1], [2, 1]],
            ],
        };
    }

    public function color(): Color
    {
        return match ($this) {
            self::I => Color::Cyan,
            self::O => Color::Yellow,
            self::T => Color::Purple,
            self::S => Color::Green,
            self::Z => Color::Red,
            self::J => Color::Blue,
            self::L => Color::Orange,
        };
    }

    /** Spawn column (centers piece on 10-wide board). */
    public function spawnCol(): int
    {
        return match ($this) {
            self::I => 3,
            self::O => 4,
            default => 3,
        };
    }

    /** Spawn row (within buffer zone above visible area). */
    public function spawnRow(): int
    {
        return match ($this) {
            self::I => 0,
            default => 1,
        };
    }
}

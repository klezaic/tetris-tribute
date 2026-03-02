<?php

declare(strict_types=1);

namespace Tetris;

final class Board
{
    public const int WIDTH = 10;
    public const int HEIGHT = 20;
    public const int BUFFER = 4;

    /** @var array<int, array<int, ?Tetromino>> */
    private array $grid;

    public function __construct()
    {
        $this->clear();
    }

    public function clear(): void
    {
        $this->grid = [];
        for ($r = 0; $r < self::HEIGHT + self::BUFFER; $r++) {
            $this->grid[$r] = array_fill(0, self::WIDTH, null);
        }
    }

    /**
     * Check if a piece at given position/rotation collides with walls or locked cells.
     */
    public function collides(Tetromino $type, int $rotation, int $row, int $col): bool
    {
        foreach ($type->shapes()[$rotation] as [$dr, $dc]) {
            $r = $row + $dr;
            $c = $col + $dc;

            if ($c < 0 || $c >= self::WIDTH) {
                return true;
            }
            if ($r >= self::HEIGHT + self::BUFFER) {
                return true;
            }
            if ($r >= 0 && $this->grid[$r][$c] !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lock a piece into the grid.
     */
    public function lock(Tetromino $type, int $rotation, int $row, int $col): void
    {
        foreach ($type->shapes()[$rotation] as [$dr, $dc]) {
            $r = $row + $dr;
            $c = $col + $dc;
            if ($r >= 0 && $r < self::HEIGHT + self::BUFFER) {
                $this->grid[$r][$c] = $type;
            }
        }
    }

    /**
     * Clear completed lines. Returns number of lines cleared.
     */
    public function clearLines(): int
    {
        $cleared = 0;

        for ($r = self::HEIGHT + self::BUFFER - 1; $r >= 0; $r--) {
            if (array_all($this->grid[$r], fn(?Tetromino $cell) => $cell !== null)) {
                array_splice($this->grid, $r, 1);
                array_unshift($this->grid, array_fill(0, self::WIDTH, null));
                $cleared++;
                $r++; // recheck this index since rows shifted down
            }
        }

        return $cleared;
    }

    /**
     * Get a cell in the visible area (row 0 = top visible row).
     */
    public function getCell(int $visibleRow, int $col): ?Tetromino
    {
        return $this->grid[$visibleRow + self::BUFFER][$col] ?? null;
    }
}

<?php

declare(strict_types=1);

namespace Tetris;

final class Piece
{
    public int $row;
    public int $col;
    public int $rotation = 0;
    public int $ghostRow = 0;

    public function __construct(
        public readonly Tetromino $type,
    ) {
        $this->row = $type->spawnRow();
        $this->col = $type->spawnCol();
    }

    /**
     * Returns absolute board coordinates of the 4 cells.
     * @return array<int, array{int, int}>
     */
    public function cells(): array
    {
        return array_map(
            fn(array $offset) => [$this->row + $offset[0], $this->col + $offset[1]],
            $this->type->shapes()[$this->rotation],
        );
    }

    /**
     * Returns absolute board coordinates of the ghost (landing preview).
     * @return array<int, array{int, int}>
     */
    public function ghostCells(): array
    {
        return array_map(
            fn(array $offset) => [$this->ghostRow + $offset[0], $this->col + $offset[1]],
            $this->type->shapes()[$this->rotation],
        );
    }

    /**
     * Recalculate ghost row (lowest row piece can drop to).
     */
    public function updateGhost(Board $board): void
    {
        $testRow = $this->row;
        while (!$board->collides($this->type, $this->rotation, $testRow + 1, $this->col)) {
            $testRow++;
        }
        $this->ghostRow = $testRow;
    }
}

<?php

declare(strict_types=1);

namespace Tetris;

final class Rotation
{
    /**
     * SRS wall kick offsets for J, L, S, T, Z pieces.
     * Each entry: [col_offset, row_offset] (positive col = right, positive row = down).
     */
    private const array JLSTZ_KICKS = [
        '0->1' => [[0, 0], [-1, 0], [-1, -1], [0, 2], [-1, 2]],
        '1->0' => [[0, 0], [1, 0], [1, 1], [0, -2], [1, -2]],
        '1->2' => [[0, 0], [1, 0], [1, -1], [0, 2], [1, 2]],
        '2->1' => [[0, 0], [-1, 0], [-1, 1], [0, -2], [-1, -2]],
        '2->3' => [[0, 0], [1, 0], [1, 1], [0, -2], [1, -2]],
        '3->2' => [[0, 0], [-1, 0], [-1, -1], [0, 2], [-1, 2]],
        '3->0' => [[0, 0], [-1, 0], [-1, 1], [0, -2], [-1, -2]],
        '0->3' => [[0, 0], [1, 0], [1, -1], [0, 2], [1, 2]],
    ];

    /** SRS wall kick offsets for I piece. */
    private const array I_KICKS = [
        '0->1' => [[0, 0], [-2, 0], [1, 0], [-2, -1], [1, 2]],
        '1->0' => [[0, 0], [2, 0], [-1, 0], [2, 1], [-1, -2]],
        '1->2' => [[0, 0], [-1, 0], [2, 0], [-1, 2], [2, -1]],
        '2->1' => [[0, 0], [1, 0], [-2, 0], [1, -2], [-2, 1]],
        '2->3' => [[0, 0], [2, 0], [-1, 0], [2, 1], [-1, -2]],
        '3->2' => [[0, 0], [-2, 0], [1, 0], [-2, -1], [1, 2]],
        '3->0' => [[0, 0], [1, 0], [-2, 0], [1, -2], [-2, 1]],
        '0->3' => [[0, 0], [-1, 0], [2, 0], [-1, 2], [2, -1]],
    ];

    /**
     * Attempt rotation with SRS wall kicks.
     *
     * @param callable $collisionCheck fn(int $rotation, int $colOffset, int $rowOffset): bool
     * @return array{int, int, int}|null [newRotation, colOffset, rowOffset] or null if all kicks fail
     */
    public static function tryRotate(
        Tetromino $type,
        int $from,
        int $to,
        callable $collisionCheck,
    ): ?array {
        if ($type === Tetromino::O) {
            return [$to, 0, 0];
        }

        $kicks = match ($type) {
            Tetromino::I => self::I_KICKS["{$from}->{$to}"] ?? [],
            default => self::JLSTZ_KICKS["{$from}->{$to}"] ?? [],
        };

        foreach ($kicks as [$dc, $dr]) {
            if (!$collisionCheck($to, $dc, $dr)) {
                return [$to, $dc, $dr];
            }
        }

        return null;
    }
}

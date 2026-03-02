<?php

declare(strict_types=1);

namespace Tetris;

final class Scoring
{
    public int $score = 0;
    public int $level = 1;
    public int $lines = 0;

    private int $combo = -1;

    private const int LINES_PER_LEVEL = 10;
    private const array LINE_SCORES = [0 => 0, 1 => 100, 2 => 300, 3 => 500, 4 => 800];

    public function addLineClear(int $count): void
    {
        if ($count > 0) {
            $this->combo++;
            $this->lines += $count;
            $this->score += (self::LINE_SCORES[$count] ?? 0) * $this->level;
            $this->score += 50 * $this->combo * $this->level;
            $this->level = intdiv($this->lines, self::LINES_PER_LEVEL) + 1;
        } else {
            $this->combo = -1;
        }
    }

    public function addHardDrop(int $rows): void
    {
        $this->score += $rows * 2;
    }

    /**
     * Gravity interval in microseconds.
     */
    public function gravityInterval(): int
    {
        $seconds = pow(0.8 - ($this->level - 1) * 0.007, $this->level - 1);
        $seconds = max($seconds, 0.05);
        return (int) ($seconds * 1_000_000);
    }
}

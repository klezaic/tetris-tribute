<?php

declare(strict_types=1);

namespace Tetris;

final class Renderer
{
    private const string BLOCK = '[]';
    private const string EMPTY = '  ';
    private const string GHOST = '::';

    private const string H = "\u{2500}";  // ─
    private const string V = "\u{2502}";  // │
    private const string TL = "\u{250C}"; // ┌
    private const string TR = "\u{2510}"; // ┐
    private const string BL = "\u{2514}"; // └
    private const string BR = "\u{2518}"; // ┘

    private string $buffer = '';

    // Board rendering offset
    private const int BOARD_TOP = 2;
    private const int BOARD_LEFT = 12;

    public function render(
        Board $board,
        ?Piece $currentPiece,
        ?Tetromino $holdPiece,
        bool $holdUsed,
        Tetromino $nextPiece,
        Scoring $scoring,
    ): void {
        $this->buffer = '';
        $this->write("\e[?25l"); // hide cursor
        $this->moveCursor(1, 1);

        // Precompute current piece and ghost cells as lookup sets
        $pieceCells = [];
        $ghostCells = [];
        if ($currentPiece !== null) {
            foreach ($currentPiece->cells() as [$r, $c]) {
                $pieceCells["{$r},{$c}"] = true;
            }
            foreach ($currentPiece->ghostCells() as [$r, $c]) {
                $ghostCells["{$r},{$c}"] = true;
            }
        }

        $boardLeft = self::BOARD_LEFT;
        $boardTop = self::BOARD_TOP;
        $boardInnerWidth = Board::WIDTH * 2;

        // Draw board top border
        $this->moveCursor($boardTop, $boardLeft);
        $this->write(Color::White->fg() . self::TL . str_repeat(self::H, $boardInnerWidth) . self::TR . Color::Reset->fg());

        // Draw each visible row
        for ($row = 0; $row < Board::HEIGHT; $row++) {
            $screenRow = $boardTop + 1 + $row;
            $this->moveCursor($screenRow, $boardLeft);
            $this->write(Color::White->fg() . self::V . Color::Reset->fg());

            for ($col = 0; $col < Board::WIDTH; $col++) {
                $absRow = $row + Board::BUFFER;
                $key = "{$absRow},{$col}";

                if (isset($pieceCells[$key])) {
                    $color = $currentPiece->type->color();
                    $this->write($color->bg() . Color::White->fg() . self::BLOCK . Color::Reset->fg());
                } elseif (($cell = $board->getCell($row, $col)) !== null) {
                    $color = $cell->color();
                    $this->write($color->bg() . Color::White->fg() . self::BLOCK . Color::Reset->fg());
                } else {
                    $this->write(Color::Gray->fg() . '.' . Color::Reset->fg() . Color::Gray->fg() . '.' . Color::Reset->fg());
                }
            }

            $this->write(Color::White->fg() . self::V . Color::Reset->fg());
        }

        // Board bottom border
        $this->moveCursor($boardTop + 1 + Board::HEIGHT, $boardLeft);
        $this->write(Color::White->fg() . self::BL . str_repeat(self::H, $boardInnerWidth) . self::BR . Color::Reset->fg());

        // Draw hold piece (left side)
        $this->drawHoldBox($holdPiece, $holdUsed, $boardTop);

        // Draw next piece + stats (right side)
        $rightCol = $boardLeft + $boardInnerWidth + 4;
        $this->drawNextBox($nextPiece, $boardTop, $rightCol);
        $this->drawStats($scoring, $boardTop + 7, $rightCol);
        $this->drawControls($boardTop + 12, $rightCol);

        // Flush
        echo $this->buffer;
    }

    private function drawHoldBox(?Tetromino $hold, bool $used, int $top): void
    {
        $left = 1;
        $label = ' HOLD ';

        $this->moveCursor($top, $left);
        $this->write(Color::White->fg() . self::TL . str_repeat(self::H, 8) . self::TR . Color::Reset->fg());

        $this->moveCursor($top + 1, $left);
        $this->write(Color::White->fg() . self::V . Color::Reset->fg());
        $this->write($this->centerText($label, 8));
        $this->write(Color::White->fg() . self::V . Color::Reset->fg());

        // Draw 4 rows for the piece preview
        for ($r = 0; $r < 4; $r++) {
            $this->moveCursor($top + 2 + $r, $left);
            $this->write(Color::White->fg() . self::V . Color::Reset->fg());
            $this->write($this->renderMiniPiece($hold, $r, $used));
            $this->write(Color::White->fg() . self::V . Color::Reset->fg());
        }

        $this->moveCursor($top + 6, $left);
        $this->write(Color::White->fg() . self::BL . str_repeat(self::H, 8) . self::BR . Color::Reset->fg());
    }

    private function drawNextBox(Tetromino $next, int $top, int $left): void
    {
        $label = ' NEXT ';

        $this->moveCursor($top, $left);
        $this->write(Color::White->fg() . self::TL . str_repeat(self::H, 8) . self::TR . Color::Reset->fg());

        $this->moveCursor($top + 1, $left);
        $this->write(Color::White->fg() . self::V . Color::Reset->fg());
        $this->write($this->centerText($label, 8));
        $this->write(Color::White->fg() . self::V . Color::Reset->fg());

        for ($r = 0; $r < 4; $r++) {
            $this->moveCursor($top + 2 + $r, $left);
            $this->write(Color::White->fg() . self::V . Color::Reset->fg());
            $this->write($this->renderMiniPiece($next, $r, false));
            $this->write(Color::White->fg() . self::V . Color::Reset->fg());
        }

        $this->moveCursor($top + 6, $left);
        $this->write(Color::White->fg() . self::BL . str_repeat(self::H, 8) . self::BR . Color::Reset->fg());
    }

    private function renderMiniPiece(?Tetromino $type, int $row, bool $dimmed): string
    {
        if ($type === null) {
            return str_repeat(' ', 8);
        }

        $shape = $type->shapes()[0]; // Always show spawn rotation
        $color = $dimmed ? Color::Gray : $type->color();

        // Build a 4-col row
        $cells = [];
        foreach ($shape as [$r, $c]) {
            if ($r === $row) {
                $cells[$c] = true;
            }
        }

        $result = '';
        for ($c = 0; $c < 4; $c++) {
            if (isset($cells[$c])) {
                $result .= $color->bg() . Color::White->fg() . self::BLOCK . Color::Reset->fg();
            } else {
                $result .= '  ';
            }
        }

        return $result;
    }

    private function drawStats(Scoring $scoring, int $top, int $left): void
    {
        $stats = [
            'Score: ' . number_format($scoring->score),
            'Level: ' . $scoring->level,
            'Lines: ' . $scoring->lines,
        ];

        foreach ($stats as $i => $line) {
            $this->moveCursor($top + $i, $left);
            $this->write(Color::White->fg() . str_pad($line, 14) . Color::Reset->fg());
        }
    }

    private function drawControls(int $top, int $left): void
    {
        $controls = [
            "\u{2190}\u{2192}  Move",
            "\u{2193}   Drop",
            "\u{2191}   Rotate",
            "SPC Hold",
            "Q   Quit",
        ];

        foreach ($controls as $i => $line) {
            $this->moveCursor($top + $i, $left);
            $this->write(Color::Gray->fg() . $line . Color::Reset->fg());
        }
    }

    public function renderGameOver(Scoring $scoring): void
    {
        $boardLeft = self::BOARD_LEFT;
        $boardInnerWidth = Board::WIDTH * 2;
        $centerCol = $boardLeft + 1;
        $centerRow = self::BOARD_TOP + Board::HEIGHT / 2;

        // Draw game over overlay
        $this->buffer = '';

        $this->moveCursor((int) $centerRow - 1, $centerCol);
        $this->write(Color::Red->bg() . Color::White->fg() . str_pad('', $boardInnerWidth) . Color::Reset->fg());

        $this->moveCursor((int) $centerRow, $centerCol);
        $text = '  GAME OVER!  ';
        $pad = (int) (($boardInnerWidth - strlen($text)) / 2);
        $this->write(Color::Red->bg() . Color::White->fg() . str_repeat(' ', $pad) . $text . str_repeat(' ', $boardInnerWidth - $pad - strlen($text)) . Color::Reset->fg());

        $this->moveCursor((int) $centerRow + 1, $centerCol);
        $scoreText = 'Score: ' . number_format($scoring->score);
        $pad = (int) (($boardInnerWidth - strlen($scoreText)) / 2);
        $this->write(Color::Red->bg() . Color::White->fg() . str_repeat(' ', $pad) . $scoreText . str_repeat(' ', $boardInnerWidth - $pad - strlen($scoreText)) . Color::Reset->fg());

        $this->moveCursor((int) $centerRow + 2, $centerCol);
        $this->write(Color::Red->bg() . Color::White->fg() . str_pad('', $boardInnerWidth) . Color::Reset->fg());

        $this->moveCursor((int) $centerRow + 4, $centerCol);
        $exitText = 'Press any key...';
        $pad = (int) (($boardInnerWidth - strlen($exitText)) / 2);
        $this->write(str_repeat(' ', $pad) . Color::White->fg() . $exitText . Color::Reset->fg());

        echo $this->buffer;
    }

    public static function clearScreen(): void
    {
        echo "\e[2J\e[H";
    }

    private function write(string $s): void
    {
        $this->buffer .= $s;
    }

    private function moveCursor(int $row, int $col): void
    {
        $this->buffer .= "\e[{$row};{$col}H";
    }

    private function centerText(string $text, int $width): string
    {
        $textLen = mb_strlen($text);
        if ($textLen >= $width) {
            return mb_substr($text, 0, $width);
        }
        $pad = (int) (($width - $textLen) / 2);
        return str_repeat(' ', $pad) . $text . str_repeat(' ', $width - $pad - $textLen);
    }
}

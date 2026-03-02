<?php

declare(strict_types=1);

namespace Tetris;

final class Game
{
    private Board $board;
    private Scoring $scoring;
    private Renderer $renderer;
    private Music $music;

    private ?Piece $currentPiece = null;
    private ?Tetromino $holdPiece = null;
    private bool $holdUsedThisTurn = false;
    private Tetromino $nextPiece;

    /** @var Tetromino[] */
    private array $bag = [];

    private bool $running = true;
    private bool $gameOver = false;

    public static function run(): void
    {
        $game = new self();
        $game->start();
    }

    private function __construct()
    {
        $this->board = new Board();
        $this->scoring = new Scoring();
        $this->renderer = new Renderer();
        $this->music = new Music();
    }

    private function start(): void
    {
        Input::enableRawMode();
        Renderer::clearScreen();

        $this->music->play();

        $this->nextPiece = $this->pullFromBag();
        $this->spawnPiece();

        $this->gameLoop();

        $this->music->stop();
        $this->renderer->renderGameOver($this->scoring);

        // Wait for keypress to exit
        $deadline = hrtime(true) + 500_000_000; // 0.5s minimum wait
        while (hrtime(true) < $deadline) {
            usleep(50_000);
        }
        // Drain any buffered input
        while (Input::readKey() !== Key::None) {
        }
        // Wait for actual keypress
        while (Input::readKey() === Key::None) {
            usleep(50_000);
        }
    }

    private function gameLoop(): void
    {
        $lastGravity = hrtime(true);

        while ($this->running && !$this->gameOver) {
            $now = hrtime(true);

            // Process input
            $this->processInput();

            // Apply gravity
            $elapsedUs = (int) (($now - $lastGravity) / 1_000);
            if ($elapsedUs >= $this->scoring->gravityInterval()) {
                $this->applyGravity();
                $lastGravity = hrtime(true);
            }

            // Update ghost
            $this->currentPiece?->updateGhost($this->board);

            // Render
            $this->renderer->render(
                $this->board,
                $this->currentPiece,
                $this->holdPiece,
                $this->holdUsedThisTurn,
                $this->nextPiece,
                $this->scoring,
            );

            usleep(16_667); // ~60fps
        }
    }

    private function processInput(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $key = Input::readKey();
            if ($key === Key::None) {
                break;
            }

            match ($key) {
                Key::Left => $this->moveHorizontal(-1),
                Key::Right => $this->moveHorizontal(1),
                Key::Down => $this->hardDrop(),
                Key::Up => $this->rotateCW(),
                Key::Space => $this->holdSwap(),
                Key::Quit => $this->running = false,
                default => null,
            };
        }
    }

    private function moveHorizontal(int $dx): void
    {
        if ($this->currentPiece === null) {
            return;
        }
        $p = $this->currentPiece;
        if (!$this->board->collides($p->type, $p->rotation, $p->row, $p->col + $dx)) {
            $p->col += $dx;
        }
    }

    private function hardDrop(): void
    {
        if ($this->currentPiece === null) {
            return;
        }
        $p = $this->currentPiece;
        $dropDistance = 0;
        while (!$this->board->collides($p->type, $p->rotation, $p->row + 1, $p->col)) {
            $p->row++;
            $dropDistance++;
        }
        $this->scoring->addHardDrop($dropDistance);
        $this->lockAndAdvance();
    }

    private function holdSwap(): void
    {
        if ($this->holdUsedThisTurn || $this->currentPiece === null) {
            return;
        }

        $currentType = $this->currentPiece->type;

        if ($this->holdPiece === null) {
            $this->holdPiece = $currentType;
            $this->spawnPiece();
        } else {
            $swapType = $this->holdPiece;
            $this->holdPiece = $currentType;
            $this->currentPiece = new Piece($swapType);

            if ($this->board->collides(
                $swapType,
                $this->currentPiece->rotation,
                $this->currentPiece->row,
                $this->currentPiece->col,
            )) {
                $this->gameOver = true;
            }
        }

        $this->holdUsedThisTurn = true;
    }

    private function rotateCW(): void
    {
        if ($this->currentPiece === null) {
            return;
        }
        $p = $this->currentPiece;
        $targetRot = ($p->rotation + 1) % 4;

        $result = Rotation::tryRotate(
            $p->type,
            $p->rotation,
            $targetRot,
            fn(int $rot, int $dc, int $dr) => $this->board->collides(
                $p->type,
                $rot,
                $p->row + $dr,
                $p->col + $dc,
            ),
        );

        if ($result !== null) {
            [$newRot, $dc, $dr] = $result;
            $p->rotation = $newRot;
            $p->col += $dc;
            $p->row += $dr;
        }
    }

    private function applyGravity(): void
    {
        if ($this->currentPiece === null) {
            return;
        }
        $p = $this->currentPiece;
        if (!$this->board->collides($p->type, $p->rotation, $p->row + 1, $p->col)) {
            $p->row++;
        } else {
            $this->lockAndAdvance();
        }
    }

    private function lockAndAdvance(): void
    {
        if ($this->currentPiece === null) {
            return;
        }

        $p = $this->currentPiece;
        $this->board->lock($p->type, $p->rotation, $p->row, $p->col);
        $cleared = $this->board->clearLines();
        $this->scoring->addLineClear($cleared);
        $this->holdUsedThisTurn = false;
        $this->spawnPiece();
    }

    private function spawnPiece(): void
    {
        $this->currentPiece = new Piece($this->nextPiece);
        $this->nextPiece = $this->pullFromBag();

        $p = $this->currentPiece;
        if ($this->board->collides($p->type, $p->rotation, $p->row, $p->col)) {
            $this->gameOver = true;
        }
    }

    private function pullFromBag(): Tetromino
    {
        if (empty($this->bag)) {
            $this->bag = Tetromino::cases();
            shuffle($this->bag);
        }
        return array_pop($this->bag);
    }
}

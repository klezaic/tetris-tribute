<?php

declare(strict_types=1);

namespace Tetris;

enum Key
{
    case Left;
    case Right;
    case Down;
    case Up;
    case Space;
    case Quit;
    case None;
}

final class Input
{
    private static string $originalStty = '';

    public static function enableRawMode(): void
    {
        self::$originalStty = trim((string) shell_exec('stty -g'));

        system('stty -icanon -echo min 0 time 0');
        stream_set_blocking(STDIN, false);

        register_shutdown_function([self::class, 'restoreTerminal']);

        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, function () {
                exit(0);
            });
            pcntl_signal(SIGTERM, function () {
                exit(0);
            });
        }
    }

    public static function restoreTerminal(): void
    {
        if (self::$originalStty !== '') {
            system('stty ' . escapeshellarg(self::$originalStty));
            self::$originalStty = '';
        }
        // Show cursor and reset colors
        echo "\e[?25h\e[0m\n";
    }

    public static function readKey(): Key
    {
        $char = fread(STDIN, 1);
        if ($char === false || $char === '') {
            return Key::None;
        }

        return match ($char) {
            ' ' => Key::Space,
            'q', 'Q' => Key::Quit,
            "\e" => self::readEscapeSequence(),
            default => Key::None,
        };
    }

    private static function readEscapeSequence(): Key
    {
        $seq1 = fread(STDIN, 1);
        if ($seq1 === false || $seq1 === '') {
            return Key::Quit; // Bare escape = quit
        }

        if ($seq1 === '[') {
            $seq2 = fread(STDIN, 1);
            if ($seq2 === false || $seq2 === '') {
                return Key::None;
            }
            return match ($seq2) {
                'A' => Key::Up,
                'B' => Key::Down,
                'C' => Key::Right,
                'D' => Key::Left,
                default => Key::None,
            };
        }

        return Key::None;
    }
}

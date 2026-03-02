<?php

declare(strict_types=1);

namespace Tetris;

final class Music
{
    private const int SAMPLE_RATE = 44100;

    private string $wavPath;
    private string $pidFile;

    public function __construct()
    {
        $id = getmypid();
        $this->wavPath = sys_get_temp_dir() . '/tetris_theme_' . $id . '.wav';
        $this->pidFile = sys_get_temp_dir() . '/tetris_music_' . $id . '.pid';
    }

    public function play(): void
    {
        $this->generateWav();

        // Write a small shell script that loops afplay and tracks its own PID
        $escaped = escapeshellarg($this->wavPath);
        $pidFile = escapeshellarg($this->pidFile);

        $cmd = sprintf(
            'bash -c \'echo $$ > %s; while true; do afplay %s 2>/dev/null; done\' &>/dev/null &',
            $pidFile,
            $escaped,
        );
        exec($cmd);
        usleep(50_000);

        // Register cleanup on both shutdown and signals
        register_shutdown_function([$this, 'stop']);
        if (function_exists('pcntl_async_signals')) {
            pcntl_signal(SIGINT, function () {
                $this->stop();
                exit(0);
            });
            pcntl_signal(SIGTERM, function () {
                $this->stop();
                exit(0);
            });
        }
    }

    public function stop(): void
    {
        // 1. Kill afplay children first (before killing parent, so they don't orphan)
        if (file_exists($this->pidFile)) {
            $pid = (int) trim((string) file_get_contents($this->pidFile));
            if ($pid > 0) {
                @exec('pkill -P ' . $pid . ' 2>/dev/null');
                usleep(10_000);
                @posix_kill($pid, SIGKILL);
            }
            @unlink($this->pidFile);
        }

        // 2. Catch any orphaned afplay playing our specific wav file
        @exec('pkill -9 -f ' . escapeshellarg($this->wavPath) . ' 2>/dev/null');

        // 3. Clean up temp file
        if (file_exists($this->wavPath)) {
            @unlink($this->wavPath);
        }
    }

    private function generateWav(): void
    {
        // HARD BASS Korobeiniki -- 160 BPM, layered: kick + bass + lead
        $bpm = 160;
        $beat = 60.0 / $bpm; // seconds per beat
        $sr = self::SAMPLE_RATE;

        // Note frequencies
        $E3 = 164.81; $A3 = 220.00; $B3 = 246.94; $C4 = 261.63; $D4 = 293.66;
        $E4 = 329.63;
        $A4 = 440.00; $B4 = 493.88; $C5 = 523.25; $D5 = 587.33;
        $E5 = 659.25; $F5 = 698.46; $G5 = 783.99; $A5 = 880.00;
        $R = 0;

        // Lead melody: [freq, beats]
        $melody = [
            // Part A (8 bars)
            [$E5, 1], [$B4, 0.5], [$C5, 0.5], [$D5, 1], [$C5, 0.5], [$B4, 0.5],
            [$A4, 1], [$A4, 0.5], [$C5, 0.5], [$E5, 1], [$D5, 0.5], [$C5, 0.5],
            [$B4, 1], [$B4, 0.5], [$C5, 0.5], [$D5, 1], [$E5, 1],
            [$C5, 1], [$A4, 1], [$A4, 1], [$R, 1],
            [$R, 0.5], [$D5, 1], [$F5, 0.5], [$A5, 1], [$G5, 0.5], [$F5, 0.5],
            [$E5, 1.5], [$C5, 0.5], [$E5, 1], [$D5, 0.5], [$C5, 0.5],
            [$B4, 1], [$B4, 0.5], [$C5, 0.5], [$D5, 1], [$E5, 1],
            [$C5, 1], [$A4, 1], [$A4, 1], [$R, 1],
            // Part B - heavy drop section (8 bars)
            [$E5, 1], [$B4, 0.5], [$C5, 0.5], [$D5, 1], [$C5, 0.5], [$B4, 0.5],
            [$A4, 1], [$A4, 0.5], [$C5, 0.5], [$E5, 1], [$D5, 0.5], [$C5, 0.5],
            [$B4, 1], [$B4, 0.5], [$C5, 0.5], [$D5, 1], [$E5, 1],
            [$C5, 1], [$A4, 1], [$A4, 1], [$R, 1],
            [$R, 0.5], [$D5, 1], [$F5, 0.5], [$A5, 1], [$G5, 0.5], [$F5, 0.5],
            [$E5, 1.5], [$C5, 0.5], [$E5, 1], [$D5, 0.5], [$C5, 0.5],
            [$B4, 1], [$B4, 0.5], [$C5, 0.5], [$D5, 1], [$E5, 1],
            [$C5, 1], [$A4, 1], [$A4, 1], [$R, 1],
        ];

        // Bass line root notes per beat (follows chord progression, low octave)
        // 4 beats per bar, 16 bars = 64 beats
        $bassRoots = [
            // Bars 1-8 (Part A)
            $E3, $E3, $E3, $E3,   $A3, $A3, $A3, $A3,
            $B3, $B3, $B3, $B3,   $E3, $E3, $A3, $A3,
            $D4, $D4, $D4, $D4,   $E4, $E4, $C4, $C4,
            $B3, $B3, $B3, $B3,   $E3, $E3, $A3, $A3,
            // Bars 9-16 (Part B - same progression, heavier)
            $E3, $E3, $E3, $E3,   $A3, $A3, $A3, $A3,
            $B3, $B3, $B3, $B3,   $E3, $E3, $A3, $A3,
            $D4, $D4, $D4, $D4,   $E4, $E4, $C4, $C4,
            $B3, $B3, $B3, $B3,   $E3, $E3, $A3, $A3,
        ];

        // Calculate total duration from melody
        $totalBeats = 0.0;
        foreach ($melody as [, $b]) {
            $totalBeats += $b;
        }
        $totalSamples = (int) ($totalBeats * $beat * $sr);

        // Pre-render melody into a float buffer
        $melodyBuf = array_fill(0, $totalSamples, 0.0);
        $pos = 0;
        foreach ($melody as [$freq, $beats]) {
            $dur = (int) ($beats * $beat * $sr);
            $attack = (int) (0.005 * $sr);
            $release = (int) (0.02 * $sr);
            for ($i = 0; $i < $dur && ($pos + $i) < $totalSamples; $i++) {
                if ($freq > 0) {
                    $t = $i / $sr;
                    // Detuned saw wave for aggressive lead
                    $p1 = fmod($t * $freq, 1.0);
                    $p2 = fmod($t * $freq * 1.005, 1.0);
                    $val = (($p1 * 2 - 1) + ($p2 * 2 - 1)) * 0.5;
                    // Envelope
                    $env = 1.0;
                    if ($i < $attack) {
                        $env = $i / $attack;
                    } elseif ($i > $dur - $release) {
                        $env = ($dur - $i) / $release;
                    }
                    $melodyBuf[$pos + $i] = $val * $env;
                }
            }
            $pos += $dur;
        }

        // Pre-render kick + bass into a float buffer
        $kickBassBuf = array_fill(0, $totalSamples, 0.0);
        $samplesPerBeat = (int) ($beat * $sr);
        $totalBassBeats = count($bassRoots);

        for ($b = 0; $b < $totalBassBeats && ($b * $samplesPerBeat) < $totalSamples; $b++) {
            $beatStart = $b * $samplesPerBeat;
            $bassFreq = $bassRoots[$b];

            for ($i = 0; $i < $samplesPerBeat && ($beatStart + $i) < $totalSamples; $i++) {
                $t = $i / $sr;
                $idx = $beatStart + $i;
                $val = 0.0;

                // === KICK DRUM: every beat (four on the floor) ===
                $kickLen = 0.15;
                if ($t < $kickLen) {
                    $progress = $t / $kickLen;
                    // Pitch sweep from 150Hz down to 40Hz
                    $kickFreq = 150 - 110 * $progress;
                    $kickVal = sin(2 * M_PI * $kickFreq * $t);
                    // Sharp exponential decay
                    $kickEnv = exp(-$t * 30);
                    // Distortion/saturation for punch
                    $kickVal = tanh($kickVal * 3.0) * $kickEnv;
                    $val += $kickVal * 1.2;
                }

                // === HARD BASS synth: distorted square sub-bass ===
                if ($bassFreq > 0) {
                    $bassPhase = fmod($t * $bassFreq, 1.0);
                    // Pulse wave with slight width modulation
                    $pw = 0.4 + 0.1 * sin(2 * M_PI * 2.0 * $t);
                    $bassVal = $bassPhase < $pw ? 1.0 : -1.0;
                    // Add sub-octave sine for weight
                    $bassVal = $bassVal * 0.6 + sin(2 * M_PI * $bassFreq * 0.5 * $t) * 0.4;
                    // Hard clip distortion
                    $bassVal = max(-1.0, min(1.0, $bassVal * 1.5));
                    // Sidechain-style envelope: duck on kick hit
                    $scEnv = min(1.0, $t * 8.0); // ducks for ~125ms
                    $val += $bassVal * $scEnv * 0.7;
                }

                // === Off-beat noise hit (snare/clap on beats 2 and 4) ===
                if ($b % 2 === 1) {
                    $noiseLen = 0.08;
                    if ($t < $noiseLen) {
                        // Simple noise from deterministic "random"
                        $noiseVal = sin($i * 12345.6789 + $i * $i * 0.001);
                        $noiseVal = tanh($noiseVal * 2.0);
                        $noiseEnv = exp(-$t * 40);
                        $val += $noiseVal * $noiseEnv * 0.4;
                    }
                }

                $kickBassBuf[$idx] = $val;
            }
        }

        // Mix layers and write PCM
        $melodyVol = 0.25;
        $bassVol = 0.35;
        $masterVol = 0.85;

        $samples = '';
        for ($i = 0; $i < $totalSamples; $i++) {
            $mix = ($melodyBuf[$i] * $melodyVol) + ($kickBassBuf[$i] * $bassVol);
            // Master limiter
            $mix = tanh($mix * $masterVol) * 0.95;
            $sample = (int) ($mix * 32767);
            $sample = max(-32768, min(32767, $sample));
            $samples .= pack('v', $sample & 0xFFFF);
        }

        $dataSize = strlen($samples);
        $fileSize = 36 + $dataSize;
        $byteRate = $sr * 2;

        $header = 'RIFF'
            . pack('V', $fileSize)
            . 'WAVE'
            . 'fmt '
            . pack('V', 16)
            . pack('v', 1)     // PCM
            . pack('v', 1)     // mono
            . pack('V', $sr)
            . pack('V', $byteRate)
            . pack('v', 2)     // block align
            . pack('v', 16)    // bits per sample
            . 'data'
            . pack('V', $dataSize);

        file_put_contents($this->wavPath, $header . $samples);
    }
}

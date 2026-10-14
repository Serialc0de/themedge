<?php

namespace App\Services\Tts;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PiperTtsService
{
    protected string $piperBinary;
    protected string $piperModel;
    protected string $ffmpegBinary;
    protected int $timeout;
    protected string $disk;
    protected string $basePath;
    protected string $format;

    public function __construct()
    {
        $this->piperBinary = config('tts.piper.binary');
        $this->piperModel = config('tts.piper.model');
        $this->ffmpegBinary = config('tts.ffmpeg.binary');
        $this->timeout = config('tts.piper.timeout', 300);
        $this->disk = config('tts.storage.disk', 'public');
        $this->basePath = config('tts.storage.path', 'audio');
        $this->format = config('tts.storage.format', 'mp3');
    }

    /**
     * Genera audio da testo e salva sul disk.
     *
     * @param string $text Testo da sintetizzare (già pulito da HTML)
     * @param string $outputFilename Nome file senza estensione (es. "articolo-slug")
     * @return array{path: string, duration: int, size: int, model: string}
     * @throws RuntimeException
     */
    public function synthesize(string $text, string $outputFilename): array
    {
        // --- Validazione ---
        if (empty(trim($text))) {
            throw new RuntimeException('Testo vuoto');
        }
        if (!file_exists($this->piperBinary)) {
            throw new RuntimeException("Piper binary non trovato: {$this->piperBinary}");
        }
        if (!file_exists($this->piperModel)) {
            throw new RuntimeException("Modello Piper non trovato: {$this->piperModel}");
        }

        // --- Preparazione path temporanei ---
        $wavPath = sys_get_temp_dir() . '/' . $outputFilename . '.wav';
        $finalFilename = $outputFilename . '.' . $this->format;
        $finalPath = $this->basePath . '/' . $finalFilename;

        // --- 1. Genera WAV con Piper ---
        $this->runPiper($text, $wavPath);

        if (!file_exists($wavPath)) {
            throw new RuntimeException("Piper non ha generato il file WAV: {$wavPath}");
        }

        // --- 2. Converti WAV → MP3 con ffmpeg (se richiesto) ---
        if ($this->format === 'mp3') {
            $mp3Path = sys_get_temp_dir() . '/' . $outputFilename . '.mp3';
            $this->runFfmpeg($wavPath, $mp3Path);

            if (!file_exists($mp3Path)) {
                throw new RuntimeException("ffmpeg non ha generato il file MP3: {$mp3Path}");
            }

            // Sposta su storage
            Storage::disk($this->disk)->put($finalPath, file_get_contents($mp3Path));

            // Calcola size PRIMA di cancellare il file temporaneo
            $size = filesize($mp3Path) ?: 0;

            @unlink($mp3Path);
            @unlink($wavPath);

            $duration = $this->estimateDuration($text);
        } else {
            // WAV direttamente
            Storage::disk($this->disk)->put($finalPath, file_get_contents($wavPath));
            @unlink($wavPath);

            $size = Storage::disk($this->disk)->size($finalPath);
            $duration = $this->estimateDuration($text);
        }

        return [
            'path' => $finalPath,
            'duration' => $duration,
            'size' => $size,
            'model' => config('tts.default_voice_name', 'paola-medium'),
        ];
    }

    /**
     * Genera audio in un file specifico (per test).
     */
    public function synthesizeToFile(string $text, string $outputPath): void
    {
        if (!file_exists($this->piperBinary)) {
            throw new RuntimeException("Piper non trovato: {$this->piperBinary}");
        }

        $this->runPiper($text, $outputPath);
    }

    /**
     * Chiama Piper via proc_open.
     */
    protected function runPiper(string $text, string $outputPath): void
    {
        $cmd = [
            $this->piperBinary,
            '--model', $this->piperModel,
            '--output_file', $outputPath,
        ];

        // Aggiungi parametri opzionali se configurati
        if ($ls = config('tts.piper.length_scale')) {
            $cmd[] = '--length_scale';
            $cmd[] = (string) $ls;
        }
        if ($ns = config('tts.piper.noise_scale')) {
            $cmd[] = '--noise_scale';
            $cmd[] = (string) $ns;
        }
        if ($nw = config('tts.piper.noise_w')) {
            $cmd[] = '--noise_w';
            $cmd[] = (string) $nw;
        }
        if ($spk = config('tts.piper.speaker')) {
            $cmd[] = '--speaker';
            $cmd[] = (string) $spk;
        }

        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $process = proc_open($cmd, $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new RuntimeException('Impossibile avviare Piper');
        }

        // Scrivi il testo su stdin
        fwrite($pipes[0], $text);
        fclose($pipes[0]);

        // Leggi output (per debug)
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            Log::error('Piper error', [
                'exit_code' => $exitCode,
                'stdout' => $stdout,
                'stderr' => $stderr,
            ]);
            throw new RuntimeException("Piper ha fallito (exit {$exitCode}): {$stderr}");
        }
    }

    /**
     * Converti WAV → MP3 con ffmpeg.
     */
    protected function runFfmpeg(string $input, string $output): void
    {
        if (!file_exists($this->ffmpegBinary)) {
            throw new RuntimeException("ffmpeg non trovato: {$this->ffmpegBinary}");
        }

        $bitrate = config('tts.ffmpeg.bitrate', '128k');
        $sampleRate = config('tts.ffmpeg.sample_rate', 22050);

        $cmd = [
            $this->ffmpegBinary,
            '-y', // overwrite
            '-i', $input,
            '-b:a', $bitrate,
            '-ar', (string) $sampleRate,
            '-ac', '1', // mono
            $output,
        ];

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new RuntimeException('Impossibile avviare ffmpeg');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            Log::error('ffmpeg error', [
                'exit_code' => $exitCode,
                'stderr' => $stderr,
            ]);
            throw new RuntimeException("ffmpeg ha fallito (exit {$exitCode}): {$stderr}");
        }
    }

    /**
     * Stima durata in secondi (~150 parole/min).
     */
    protected function estimateDuration(string $text): int
    {
        $words = str_word_count($text);
        return max(1, (int) round(($words / 150) * 60));
    }

    /**
     * Pulisce HTML e ritorna testo puro.
     */
    public function cleanHtml(string $html): string
    {
        // Rimuovi script/style
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);

        // Converti <br>, </p>, </h*> in newline
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = preg_replace('/<\/(p|h[1-6]|li|blockquote|div)>/i', "\n", $html);

        // Rimuovi altri tag
        $text = strip_tags($html);

        // Decodifica entità HTML
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalizza spazi
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }
}

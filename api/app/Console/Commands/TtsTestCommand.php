<?php

namespace App\Console\Commands;

use App\Services\Tts\PiperTtsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TtsTestCommand extends Command
{
    protected $signature = 'tts:test
                            {text : Testo da sintetizzare}
                            {--output=tts-test : Nome file output (senza estensione)}
                            {--show : Mostra URL pubblico}';

    protected $description = 'Genera audio TTS da un testo usando Piper';

    public function handle(PiperTtsService $tts): int
    {
        $text = $this->argument('text');
        $filename = $this->option('output');

        $this->info("🎙️  Test TTS Piper");
        $this->line("Testo: " . mb_substr($text, 0, 100) . (mb_strlen($text) > 100 ? '...' : ''));
        $this->line("Modello: " . config('tts.piper.model'));
        $this->line("Output: {$filename}." . config('tts.storage.format'));
        $this->newLine();

        $start = microtime(true);

        try {
            $result = $tts->synthesize($text, $filename);

            $duration = round(microtime(true) - $start, 2);

            $this->info("✅ Audio generato in {$duration}s");
            $this->table(
                ['Campo', 'Valore'],
                [
                    ['Path', $result['path']],
                    ['Duration', $result['duration'] . 's'],
                    ['Size', number_format($result['size'] / 1024, 1) . ' KB'],
                    ['Model', $result['model']],
                ]
            );

            if ($this->option('show')) {
                $url = Storage::disk(config('tts.storage.disk'))->url($result['path']);
                $this->newLine();
                $this->info("🔗 URL: {$url}");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("❌ Errore: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}

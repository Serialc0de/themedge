<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Piper TTS
    |--------------------------------------------------------------------------
    |
    | Configurazione per il motore Text-to-Speech locale basato su Piper.
    | https://github.com/rhasspy/piper
    |
    */

    'enabled' => env('TTS_ENABLED', true),

    'piper' => [
        'binary' => env('PIPER_BINARY', '/root/.local/bin/piper'),
        'model' => env('PIPER_MODEL', '/opt/piper/voices/it_IT-paola-medium.onnx'),
        'length_scale' => (float) env('PIPER_LENGTH_SCALE', 1.0),
        'noise_scale' => (float) env('PIPER_NOISE_SCALE', 0.667),
        'noise_w' => (float) env('PIPER_NOISE_W', 0.8),
        'speaker' => env('PIPER_SPEAKER', null),
        'timeout' => (int) env('PIPER_TIMEOUT', 300),
    ],

    'ffmpeg' => [
        'binary' => env('FFMPEG_BINARY', '/usr/bin/ffmpeg'),
        'bitrate' => env('FFMPEG_BITRATE', '128k'),
        'sample_rate' => (int) env('FFMPEG_SAMPLE_RATE', 22050),
    ],

    'storage' => [
        'disk' => env('TTS_STORAGE_DISK', 'public'),
        'path' => env('TTS_STORAGE_PATH', 'audio'),
        'format' => env('TTS_FORMAT', 'mp3'), // 'mp3' o 'wav'
    ],

    'auto_generate' => [
        'enabled' => env('TTS_AUTO_GENERATE', true),
        'delay_seconds' => (int) env('TTS_AUTO_GENERATE_DELAY', 30),
        'min_length' => (int) env('TTS_AUTO_GENERATE_MIN_LENGTH', 200),
        'max_length' => (int) env('TTS_AUTO_GENERATE_MAX_LENGTH', 50000),
    ],

    'default_voice_name' => 'Paola (it-IT, medium)',

];

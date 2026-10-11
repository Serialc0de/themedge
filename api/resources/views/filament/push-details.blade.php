<div class="space-y-4">
    <div>
        <p class="text-xs uppercase text-gray-500 font-bold mb-1">Titolo</p>
        <p class="text-lg font-bold">{{ $log->title }}</p>
    </div>

    <div>
        <p class="text-xs uppercase text-gray-500 font-bold mb-1">Messaggio</p>
        <p>{{ $log->body }}</p>
    </div>

    @if ($log->url)
        <div>
            <p class="text-xs uppercase text-gray-500 font-bold mb-1">URL</p>
            <a href="{{ $log->url }}" target="_blank" class="text-primary-600 hover:underline break-all">{{ $log->url }}</a>
        </div>
    @endif

    <div class="grid grid-cols-3 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <div>
            <p class="text-xs uppercase text-gray-500 font-bold">Inviate</p>
            <p class="text-2xl font-bold text-green-600">{{ $log->sent_count }}</p>
        </div>
        <div>
            <p class="text-xs uppercase text-gray-500 font-bold">Fallite</p>
            <p class="text-2xl font-bold text-red-600">{{ $log->failed_count }}</p>
        </div>
        <div>
            <p class="text-xs uppercase text-gray-500 font-bold">Successo</p>
            <p class="text-2xl font-bold">{{ $log->success_rate }}%</p>
        </div>
    </div>

    <div class="pt-4 border-t border-gray-200 dark:border-gray-700 text-sm text-gray-500">
        <p>Inviata il {{ $log->created_at->format('d/m/Y H:i:s') }}</p>
        @if ($log->sentBy)
            <p>da <strong>{{ $log->sentBy->name }}</strong></p>
        @endif
    </div>
</div>

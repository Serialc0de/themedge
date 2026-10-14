<?php

namespace App\Console\Commands;

use App\Models\AskQuery;
use Illuminate\Console\Command;

class AskPruneCommand extends Command
{
    protected $signature = 'ask:prune
                            {--days=90 : Elimina le query più vecchie di N giorni}
                            {--dry-run : Mostra quante righe verrebbero eliminate senza eliminarle}';

    protected $description = 'Elimina le vecchie query della chat AI (default: 90 giorni)';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');

        if ($days < 1) {
            $this->error('Il parametro --days deve essere >= 1');
            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);

        $query = AskQuery::where('created_at', '<', $cutoff);

        $count = $query->count();

        if ($dryRun) {
            $this->info("[dry-run] Verrebbero eliminate {$count} query più vecchie di {$days} giorni (prima del {$cutoff->toDateTimeString()})");
            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("Eliminate {$deleted} query più vecchie di {$days} giorni (prima del {$cutoff->toDateTimeString()})");

        return self::SUCCESS;
    }
}

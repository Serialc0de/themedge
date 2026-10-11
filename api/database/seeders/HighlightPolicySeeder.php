<?php

namespace Database\Seeders;

use App\Models\HighlightPolicy;
use Illuminate\Database\Seeder;

class HighlightPolicySeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            [
                'name' => 'full',
                'label' => 'Completo',
                'description' => 'Tutte le funzionalità abilitate.',
                'color' => 'green',
                'priority' => 10,
                'settings' => [
                    'enabled' => true,
                    'allow_public' => true,
                    'allow_reactions' => true,
                    'allow_notes' => true,
                    'allow_share_image' => false,
                    'allow_heatmap' => true,
                    'max_fragment_length' => 2000,
                    'rate_limit_per_day' => 30,
                ],
            ],
            [
                'name' => 'interview',
                'label' => 'Intervista',
                'description' => 'Tutto tranne condivisione come immagine.',
                'color' => 'blue',
                'priority' => 20,
                'settings' => [
                    'enabled' => true,
                    'allow_public' => true,
                    'allow_reactions' => true,
                    'allow_notes' => true,
                    'allow_share_image' => false,
                    'allow_heatmap' => true,
                    'max_fragment_length' => 1500,
                    'rate_limit_per_day' => 20,
                ],
            ],
            [
                'name' => 'private_only',
                'label' => 'Solo privati',
                'description' => 'Highlight visibili solo all\'autore. No reazioni, no condivisione.',
                'color' => 'purple',
                'priority' => 30,
                'settings' => [
                    'enabled' => true,
                    'allow_public' => false,
                    'allow_reactions' => false,
                    'allow_notes' => true,
                    'allow_share_image' => false,
                    'allow_heatmap' => true,
                    'max_fragment_length' => 2000,
                    'rate_limit_per_day' => 20,
                ],
            ],
            [
                'name' => 'no_reactions',
                'label' => 'No reazioni',
                'description' => 'Highlight abilitati ma senza emoji.',
                'color' => 'amber',
                'priority' => 40,
                'settings' => [
                    'enabled' => true,
                    'allow_public' => true,
                    'allow_reactions' => false,
                    'allow_notes' => true,
                    'allow_share_image' => false,
                    'allow_heatmap' => true,
                    'max_fragment_length' => 2000,
                    'rate_limit_per_day' => 20,
                ],
            ],
            [
                'name' => 'disabled',
                'label' => 'Disabilitato',
                'description' => 'Nessun highlight permesso.',
                'color' => 'red',
                'priority' => 100,
                'settings' => [
                    'enabled' => false,
                    'allow_public' => false,
                    'allow_reactions' => false,
                    'allow_notes' => false,
                    'allow_share_image' => false,
                    'allow_heatmap' => false,
                    'max_fragment_length' => 0,
                    'rate_limit_per_day' => 0,
                ],
            ],
        ];

        foreach ($policies as $p) {
            HighlightPolicy::updateOrCreate(
                ['name' => $p['name']],
                array_merge($p, ['is_system' => true])
            );
        }

        $this->command->info('✅ Policy di sistema create: ' . count($policies));
    }
}

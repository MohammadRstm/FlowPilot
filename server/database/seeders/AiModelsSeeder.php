<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiModelsSeeder extends Seeder
{
    public function run(): void
    {
        $models = [
            'gpt-4.1-mini',
            'gpt-4.1',
            'gpt-4o',
            'gpt-4o-mini',
            'gpt-4-turbo',
            'gpt-3.5-turbo',
        ];

        foreach ($models as $model) {
            DB::table('ai_models')->updateOrInsert(
                ['name' => $model],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}

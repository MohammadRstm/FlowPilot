<?php

namespace Database\Seeders;

use App\Models\UserCopilotHistory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserCopilotHistorySeeder extends Seeder{
   
    public function run(): void{
        UserCopilotHistory::factory(50)->create();
    }
}

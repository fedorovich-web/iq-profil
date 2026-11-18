<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('questions')->count() === 0) {
            $this->call(QuestionSeeder::class);
        }
    }
}

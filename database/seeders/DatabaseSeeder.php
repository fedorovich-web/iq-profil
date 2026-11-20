<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\PuzzleCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('questions')->where('bank_version', PuzzleCatalog::VERSION)->count() !== PuzzleCatalog::COUNT) {
            $this->call(QuestionSeeder::class);
        }
    }
}

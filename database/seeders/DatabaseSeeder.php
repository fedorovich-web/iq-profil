<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // The seeder creates missing revisions and verifies every published option.
        $this->call(QuestionSeeder::class);
    }
}

<?php

use Illuminate\Database\Seeder;

require_once __DIR__.'/BidangJabatanSeederFromSheet.php';
require_once __DIR__.'/UserSeederFromSheet.php';

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(BidangJabatanSeederFromSheet::class);
        $this->call(UserSeederFromSheet::class);
    }
}

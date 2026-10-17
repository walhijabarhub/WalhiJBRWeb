<?php

namespace App\Console\Commands;

use App\Services\Content\ViewCounter;
use Illuminate\Console\Command;

class FlushContentViews extends Command
{
    protected $signature = 'walhi:flush-views';

    protected $description = 'Tulis jumlah views konten yang tertampung di cache ke database';

    public function handle(): int
    {
        $this->info('Views ditulis ke database: '.ViewCounter::flush());

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\scriptController;
use Illuminate\Http\Request;

class dailyPoolBonus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily-pool:bonus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Daily Bonus Release';

    /**
     * Execute the console command.
     */
    public function handle(Request $request)
    {
        $appHome = new scriptController;

        $appHome->dailyPoolRelease($request);
    }
}

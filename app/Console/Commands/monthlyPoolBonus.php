<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\scriptController;
use Illuminate\Http\Request;

class monthlyPoolBonus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monthly-pool:bonus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monthly Bonus Release';

    /**
     * Execute the console command.
     */
    public function handle(Request $request)
    {
        $appHome = new scriptController;

        $appHome->monthlyPoolRelease($request);
    }
}

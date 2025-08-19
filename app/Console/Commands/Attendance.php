<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class Attendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:command';

    /**
     * The console command description.
     *
     * @var string
     *
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
           DB::table('checkcorn')->insert(['time'=>'attendance']);
                
             $response = Http::get('https://hrmanagement.cvinfotechserver.com/CV/public/api/daily_attendance');
    
            if ($response->successful()) {
                $this->info('API request was successful');
            } else {
                $this->error('API request failed: ' . $response->body());
            }
            return 0;
    }
}

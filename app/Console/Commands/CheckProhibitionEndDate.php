<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckProhibitionEndDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check-prohibition-end-date:command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        
          DB::table('checkcorn')->insert(['time'=>'check_absent_status']);
                
             $response = Http::get('https://hrmanagement.cvinfotechserver.com/CV/public/api/check_prohibition_end_date');
    
            if ($response->successful()) {
                $this->info('API request was successful');
            } else {
                $this->error('API request failed: ' . $response->body());
            }
            return 0;
    }
}


<?php

namespace App\Console\Commands;

use App\Services\LotteryBetService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessCampaignResults extends Command
{
    protected $signature = 'campaigns:process-results {date? : The date to process (Y-m-d)}';
    protected $description = 'Process campaign betting results for a given date';

    protected $betService;

    public function __construct(LotteryBetService $betService)
    {
        parent::__construct();
        $this->betService = $betService;
    }

    public function handle()
    {
        $date = $this->argument('date') ? Carbon::parse($this->argument('date')) : Carbon::yesterday();
        
        $this->info("Processing campaign results for {$date->format('Y-m-d')}...");
        
        try {
            $this->betService->processCampaignResults($date);
            $this->betService->checkCompletedCampaigns();
            
            $this->info('Campaign results processed successfully.');
        } catch (\Exception $e) {
            $this->error("Error processing campaign results: {$e->getMessage()}");
        }
    }
}

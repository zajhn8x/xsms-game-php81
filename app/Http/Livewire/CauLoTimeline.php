<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\LotteryFormula;
use Carbon\Carbon;
use App\Services\FormulaHitService;

class CauLoTimeline extends Component
{
    public $cauLoId;
    public $page         = 1;
    public $perPage      = 10;
    public $hasMore      = false;
    public $timelineData = [];

    protected $formulaHitService;

    public function boot(FormulaHitService $formulaHitService)
    {
        $this->formulaHitService = $formulaHitService;
    }

    public function mount($cauLoId)
    {
        $this->cauLoId = $cauLoId;
        $this->loadData();
    }

    /**
     * Tải thêm dữ liệu cho timeline
     */
    public function loadMore()
    {
        $this->page++;
        $this->loadData();
    }

    /**
     * Tải dữ liệu timeline theo trang
     */
    private function loadData()
    {
        $cauLo = LotteryFormula::with('formula')->findOrFail($this->cauLoId);
        $startDate = Carbon::today()->subDays(($this->page - 1) * $this->perPage);

        // Lấy dữ liệu từ service
        $newData = $this->formulaHitService->getTimelineData(
            $cauLo,
            $startDate,
            $this->perPage
        );

        // Gộp dữ liệu mới vào timeline hiện tại
        if ($this->page === 1) {
            $this->timelineData = $newData;
        } else {
            $this->timelineData['dateRange'] = array_merge(
                $this->timelineData['dateRange'],
                $newData['dateRange']
            );
            $this->timelineData['hits'] = array_merge(
                $this->timelineData['hits'],
                $newData['hits']
            );
            $this->timelineData['results'] = array_merge(
                $this->timelineData['results'],
                $newData['results']
            );
            $this->timelineData['resultsIndexs'] = array_merge(
                $this->timelineData['resultsIndexs'],
                $newData['resultsIndexs']
            );
        }

        $this->hasMore = count($newData['dateRange']) === $this->perPage;
    }

    public function render()
    {
        return view('livewire.cau-lo-timeline', [
            'timelineData' => $this->timelineData
        ]);
    }
}

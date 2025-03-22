<?php

namespace App\View\Components;

use Illuminate\View\Component;

class LotteryResults extends Component
{
    public array $prizes;

    public function __construct(array $prizes)
    {
        $this->prizes = $prizes;
    }

    public function formatPrize($prize)
    {
        if (is_array($prize)) {
            return implode(', ', $prize);
        }
        return $prize;
    }

    public function render()
    {
        return view('components.lottery-results');
    }
}

<?php

namespace App\View\Components;

use Illuminate\Support\Arr;
use Illuminate\View\Component;

class LotteryResults extends Component
{
    public array $prizes;
    public array $hits;
    public array $posKey;
    public array $defaultPos;
    public array $posAbs; // Lưu danh sách vị trí tuyệt đối (tối ưu)

    public function __construct(array $prizes, array $hits = [], array $positions = [])
    {
        $this->prizes = $prizes;
        $this->hits = $hits;
        $this->posKey = $positions;
        $this->defaultPos = config('xsmb.positions');
        $this->tempAbs = [];

        // Tối ưu: Tạo sẵn danh sách vị trí tuyệt đối, không tính lại nhiều lần
        $this->posAbs = array_merge(...array_values($this->defaultPos));

        // Xác định vị trí tuyệt đối của posKey (nếu có trong posAbs)
        $this->posValue = array_keys(array_intersect($this->posAbs, $this->posKey));

    }

    public function highlightPrizes($numbers)
    {
        if (is_string($numbers)) {
            return $this->highlightHits($numbers);
        }

        if (is_array($numbers)) {
            return implode(' ', array_map([$this, 'highlightHits'], $numbers));
        }

        return $numbers;
    }

    private function highlightHits($num)
    {
        $lastTwoDigits = substr($num, -2); // Lấy 2 số cuối

        if (in_array($lastTwoDigits, $this->hits)) {
            return $this->applyHighlight($num, 'hit'); // Bôi đậm cả 2 số cuối
        }

        return $this->getAbsolutePositionIndex($num);
    }

    private function getAbsolutePositionIndex($num)
    {
        $digits = str_split($num);

        foreach ($this->posAbs as $absIndex) {
            if (isset($digits[$absIndex])) {
                $digits[$absIndex] = $this->applyHighlight($digits[$absIndex], 'abs');
                break; // Chỉ bôi đậm 1 ký tự duy nhất
            }
        }

        return implode('', $digits);
    }

    private function applyHighlight($char, $type)
    {
        $styles = [
            'hit' => "fw-bold text-danger bg-warning px-1",
            'abs' => "fw-bold text-primary bg-light px-1",
        ];

        //return "<span class='{$styles[$type] ?? 'fw-bold text-dark'}'>$char</span>";
    }

    public function render()
    {
        return view('components.lottery-results');
    }
}

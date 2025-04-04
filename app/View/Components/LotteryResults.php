<?php

namespace App\View\Components;

use Illuminate\Support\Arr;
use Illuminate\View\Component;
use function Symfony\Component\Mime\Test\Constraint\toString;

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
        // Nếu là một chuỗi số (giải đặc biệt, giải nhất)
        if (is_string($numbers)) {
            return $this->highlightHits($numbers);
        }
        $result = [];
        // Nếu là mảng số (các giải khác)
        if (is_array($numbers)) {
            foreach ($numbers as $number) {
                $result[] = $this->highlightHits($number);
            }
        }
        return implode(' ,  ', $result);
    }

    private function highlightHits($num)
    {
        $arrAsb = [];
        $arrHit = [];
        $arrAsb = $this->getAbsolutePositionIndex($num);
        if (!empty($this->hits)) {
            $lastTwoDigits = substr($num, -2); // Lấy 2 số cuối
            if (in_array($lastTwoDigits, $this->hits)) {
                $arrHit = $this->applyHighlight($num, 'hit'); // Bôi đậm cả 2 số cuối
            }
        }
        if (empty($arrHit)) {
            return implode(' ', $arrAsb);
        } else {
            return $num;
        }
    }

    /** @return  array */

    private function getAbsolutePositionIndex($num)
    {
        $digits = str_split($num);
        //Cất từ số 0-9 vào mảng thứ tự 0-106
        foreach ($digits as $key => $digit) {
            $this->tempAbs[] = $digit;
            if (in_array(array_key_last(array_keys($this->tempAbs)), $this->posValue)) {
                $digits[$key] = $this->applyHighlight($digit, 'abs');
            }
        }
        return $digits;
    }

    private function applyHighlight($char, $type)
    {
        $styles = [
            'hit' => "fw-bold text-danger bg-warning px-1",
            'abs' => "fw-bold text-primary bg-light px-1",
        ];
        $cssClass = $styles[$type] ?? 'fw-bold text-dark';
        return "<span class='{$cssClass}'>{$char}</span>";
    }

    public function render()
    {
        return view('components.lottery-results');
    }
}

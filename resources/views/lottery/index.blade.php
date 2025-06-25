@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <h2 class="text-2xl font-bold text-center text-gray-800">
        <span class="inline-block relative">
            📌 KẾT QUẢ XỔ SỐ
            <span class="absolute bottom-0 left-0 w-full h-1 bg-primary-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></span>
        </span>
    </h2>

    @if($results->isNotEmpty())
        @foreach($results as $result)
            @php
                $prizes = $result->prizes;
                $lo_array = $result->lo_array;

                $formatPrize = fn($prize) => is_array($prize) ? implode(', ', $prize) : $prize;

                $lo_head = [];
                $lo_tail = [];
                if (!empty($lo_array)) {
                    foreach ($lo_array as $lo) {
                        if (strlen((string)$lo) >= 2) {
                           $head = substr($lo, 0, 1);
                           $tail = substr($lo, -1);
                           $lo_head[$head][] = $lo;
                           $lo_tail[$tail][] = $lo;
                        }
                    }
                }
                ksort($lo_head);
                ksort($lo_tail);
            @endphp

            <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
                <div class="px-6 py-3 bg-gray-800 text-white font-bold">
                    📅 Ngày {{ \Carbon\Carbon::parse($result->draw_date)->format('d/m/Y') }}
                </div>
                <div class="p-6">
                    <x-lottery-results :prizes="$prizes" :highlightPositions="['G3-2-4', 'G5-1-3']"/>

                    <h3 class="text-xl font-bold text-center mt-8 mb-4 text-primary-700">BẢNG ĐẦU - ĐUÔI LÔ TÔ</h3>
                    <div class="flex flex-col md:flex-row md:space-x-8">
                        <div class="w-full md:w-1/2">
                            <h4 class="font-semibold text-center text-lg mb-3">Đầu Lô tô</h4>
                            <table class="w-full border-collapse border border-gray-300">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="border border-gray-300 p-2 font-bold w-1/4">Đầu</th>
                                        <th class="border border-gray-300 p-2 font-bold">Các số</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach(range(0, 9) as $num)
                                    <tr class="text-center">
                                        <td class="border border-gray-300 p-2 font-bold bg-gray-50">{{ $num }}</td>
                                        <td class="border border-gray-300 p-2 text-left">
                                            @if(isset($lo_head[$num]))
                                                @foreach($lo_head[$num] as $loto)
                                                    <span class="inline-block bg-blue-100 text-blue-800 text-sm font-semibold mr-2 px-2.5 py-0.5 rounded">{{ $loto }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="w-full md:w-1/2 mt-8 md:mt-0">
                            <h4 class="font-semibold text-center text-lg mb-3">Đuôi Lô tô</h4>
                            <table class="w-full border-collapse border border-gray-300">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="border border-gray-300 p-2 font-bold w-1/4">Đuôi</th>
                                        <th class="border border-gray-300 p-2 font-bold">Các số</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach(range(0, 9) as $num)
                                    <tr class="text-center">
                                        <td class="border border-gray-300 p-2 font-bold bg-gray-50">{{ $num }}</td>
                                        <td class="border border-gray-300 p-2 text-left">
                                            @if(isset($lo_tail[$num]))
                                                 @foreach($lo_tail[$num] as $loto)
                                                    <span class="inline-block bg-green-100 text-green-800 text-sm font-semibold mr-2 px-2.5 py-0.5 rounded">{{ $loto }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <p class="text-gray-500 text-lg">⛔ Không có kết quả nào để hiển thị.</p>
        </div>
    @endif
</div>
@endsection

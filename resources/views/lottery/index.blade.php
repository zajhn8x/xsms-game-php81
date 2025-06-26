@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <h2 class="text-2xl font-bold text-center text-gray-800">
        <span class="inline-block relative">
            📌 KẾT QUẢ XỔ SỐ MIỀN BẮC
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

                // Debug: kiểm tra lo_array
                // dd($lo_array);

                if (!empty($lo_array) && is_array($lo_array)) {
                    foreach ($lo_array as $lo) {
                        // Chuyển về string và đảm bảo có đủ 2 chữ số
                        $lo_str = str_pad((string)$lo, 2, '0', STR_PAD_LEFT);

                        if (strlen($lo_str) >= 2) {
                           $head = $lo_str[0]; // Chữ số đầu
                           $tail = $lo_str[1]; // Chữ số cuối
                           $lo_head[$head][] = $lo_str;
                           $lo_tail[$tail][] = $lo_str;
                        }
                    }
                }
                ksort($lo_head);
                ksort($lo_tail);
            @endphp

            <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
                <div class="px-6 py-4 bg-gradient-to-r from-gray-800 to-gray-700 text-white">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold">📅 Ngày {{ \Carbon\Carbon::parse($result->draw_date)->format('d/m/Y') }}</h3>
                        <span class="text-sm bg-white/20 px-3 py-1 rounded-full">{{ \Carbon\Carbon::parse($result->draw_date)->locale('vi')->dayName }}</span>
                    </div>
                </div>
                <div class="p-6">
                    <x-lottery-results :prizes="$prizes" :highlightPositions="['G3-2-4', 'G5-1-3']"/>

                    <!-- Kiểm tra nếu có dữ liệu lô tô -->
                    @if(!empty($lo_array) && count($lo_array) > 0)
                        <h3 class="text-xl font-bold text-center mt-8 mb-6 text-primary-700 border-b-2 border-primary-200 pb-2">
                            🎯 BẢNG ĐẦU - ĐUÔI LÔ TÔ
                        </h3>

                        <!-- Hiển thị tổng số lô -->
                        <div class="text-center mb-4">
                            <span class="inline-block bg-blue-100 text-blue-800 text-sm font-semibold px-3 py-1 rounded-full">
                                Tổng số lô: {{ count($lo_array) }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Đầu Lô tô -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="font-bold text-center text-lg mb-4 text-blue-700">🔢 Đầu Lô tô</h4>
                                <div class="overflow-x-auto">
                                    <table class="w-full border-collapse border border-gray-300 text-sm">
                                        <thead>
                                            <tr class="bg-blue-100">
                                                <th class="border border-gray-300 p-3 font-bold w-20">Đầu</th>
                                                <th class="border border-gray-300 p-3 font-bold text-left">Các số lô</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach(range(0, 9) as $num)
                                            <tr class="hover:bg-blue-50 transition-colors">
                                                <td class="border border-gray-300 p-3 font-bold bg-blue-50 text-center text-blue-800">{{ $num }}</td>
                                                <td class="border border-gray-300 p-3">
                                                    @if(isset($lo_head[$num]) && count($lo_head[$num]) > 0)
                                                        <div class="flex flex-wrap gap-1">
                                                            @foreach($lo_head[$num] as $loto)
                                                                <span class="inline-block bg-blue-500 text-white text-xs font-bold px-2 py-1 rounded hover:bg-blue-600 transition-colors">{{ $loto }}</span>
                                                            @endforeach
                                                        </div>
                                                        <span class="text-xs text-gray-500 mt-1 block">({{ count($lo_head[$num]) }} số)</span>
                                                    @else
                                                        <span class="text-gray-400 italic">Không có</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Đuôi Lô tô -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="font-bold text-center text-lg mb-4 text-green-700">🎲 Đuôi Lô tô</h4>
                                <div class="overflow-x-auto">
                                    <table class="w-full border-collapse border border-gray-300 text-sm">
                                        <thead>
                                            <tr class="bg-green-100">
                                                <th class="border border-gray-300 p-3 font-bold w-20">Đuôi</th>
                                                <th class="border border-gray-300 p-3 font-bold text-left">Các số lô</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach(range(0, 9) as $num)
                                            <tr class="hover:bg-green-50 transition-colors">
                                                <td class="border border-gray-300 p-3 font-bold bg-green-50 text-center text-green-800">{{ $num }}</td>
                                                <td class="border border-gray-300 p-3">
                                                    @if(isset($lo_tail[$num]) && count($lo_tail[$num]) > 0)
                                                        <div class="flex flex-wrap gap-1">
                                                            @foreach($lo_tail[$num] as $loto)
                                                                <span class="inline-block bg-green-500 text-white text-xs font-bold px-2 py-1 rounded hover:bg-green-600 transition-colors">{{ $loto }}</span>
                                                            @endforeach
                                                        </div>
                                                        <span class="text-xs text-gray-500 mt-1 block">({{ count($lo_tail[$num]) }} số)</span>
                                                    @else
                                                        <span class="text-gray-400 italic">Không có</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Hiển thị tất cả các số lô -->
                        <div class="mt-6 p-4 bg-gray-100 rounded-lg">
                            <h5 class="font-semibold text-gray-700 mb-3">📋 Tất cả các số lô ngày {{ \Carbon\Carbon::parse($result->draw_date)->format('d/m/Y') }}:</h5>
                            <div class="flex flex-wrap gap-2">
                                @foreach($lo_array as $lo)
                                    @php $lo_formatted = str_pad((string)$lo, 2, '0', STR_PAD_LEFT); @endphp
                                    <span class="inline-block bg-gray-600 text-white text-sm font-bold px-3 py-1 rounded">{{ $lo_formatted }}</span>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="mt-8 text-center p-6 bg-yellow-50 rounded-lg border border-yellow-200">
                            <p class="text-yellow-700 font-medium">⚠️ Chưa có dữ liệu lô tô cho ngày này</p>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @else
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="text-6xl mb-4">🎲</div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">Chưa có kết quả xổ số</h3>
            <p class="text-gray-500">Vui lòng quay lại sau hoặc liên hệ quản trị viên để cập nhật dữ liệu.</p>
        </div>
    @endif
</div>
@endsection


@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-4">Kết Quả Xổ Số Miền Bắc</h1>
        
        <!-- Filter Controls -->
        <div class="bg-white p-4 rounded-lg shadow mb-6">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Chọn khoảng thời gian</label>
                    <div class="flex gap-4">
                        <a href="?days=10" class="px-4 py-2 rounded-md {{ request('days') == 10 ? 'bg-blue-500 text-white' : 'bg-gray-100' }}">10 ngày</a>
                        <a href="?days=30" class="px-4 py-2 rounded-md {{ request('days') == 30 ? 'bg-blue-500 text-white' : 'bg-gray-100' }}">30 ngày</a>
                        <a href="?days=90" class="px-4 py-2 rounded-md {{ request('days') == 90 ? 'bg-blue-500 text-white' : 'bg-gray-100' }}">90 ngày</a>
                    </div>
                </div>
                
                <div class="flex gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Từ ngày</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}" 
                               class="border rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Đến ngày</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}"
                               class="border rounded-md px-3 py-2">
                    </div>
                    <div>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md">
                            Tìm kiếm
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Ngày
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Giải ĐB
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Giải Nhất
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Giải Nhì
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($results as $result)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $result->draw_date->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $result->prizes['DB'] ?? '' }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $result->prizes['1'] ?? '' }}
                        </td>
                        <td class="px-6 py-4">
                            {{ implode(', ', $result->prizes['2'] ?? []) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection


@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-3xl font-bold mb-6">Đặt cược</h1>

        @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('bet.store') }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label for="lo_number" class="block text-sm font-medium text-gray-700">Số lô</label>
                <input type="number"
                       name="lo_number"
                       id="lo_number"
                       min="00"
                       max="99"
                       required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('lo_number')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700">Số tiền đặt (VNĐ)</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <input type="number"
                           name="amount"
                           id="amount"
                           min="1000"
                           step="1000"
                           required
                           class="block w-full rounded-md border-gray-300 pl-7 focus:border-blue-500 focus:ring-blue-500">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">VNĐ</span>
                    </div>
                </div>
                @error('amount')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="bg-gray-50 p-4 rounded-md">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Thông tin cược</h3>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>• Tỷ lệ cược: 1:80</li>
                    <li>• Số tiền tối thiểu: 1,000 VNĐ</li>
                    <li>• Thời gian đặt cược: Trước 18:00 mỗi ngày</li>
                </ul>
            </div>

            <div class="flex items-center">
                <button type="submit"
                        class="w-full bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Xác nhận đặt cược
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const amount = document.getElementById('amount').value;
        const loNumber = document.getElementById('lo_number').value;

        if (amount < 1000) {
            e.preventDefault();
            alert('Số tiền cược tối thiểu là 1,000 VNĐ');
            return;
        }

        if (loNumber < 0 || loNumber > 99) {
            e.preventDefault();
            alert('Số lô phải từ 00-99');

        }
    });
});
</script>
@endpush

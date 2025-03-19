@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Kết quả xổ số</h2>

    @if(isset($results) && count($results) > 0)
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Giải đặc biệt</th>
                        <th>Giải nhất</th>
                        <th>Giải nhì</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($results as $result)
                    <tr>
                        <td>{{ $result->date }}</td>
                        <td>{{ $result->special_prize }}</td>
                        <td>{{ $result->first_prize }}</td>
                        <td>{{ $result->second_prize }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p>Không có kết quả nào.</p>
    @endif
</div>
@endsection
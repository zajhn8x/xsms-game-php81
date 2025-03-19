@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h2>Kết Quả Xổ Số Miền Bắc</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-primary">
                    <tr>
                        @foreach($headers as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $row)
                        <tr>
                            @foreach($row as $cell)
                                <td>{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
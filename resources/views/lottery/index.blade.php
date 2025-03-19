
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header text-center bg-dark text-white py-3">
            <h2>Kết quả Xổ số Miền Bắc {{ $date ?? 'Hôm nay' }}</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <td class="col-3 text-end fw-bold">Đặc biệt</td>
                            <td class="text-center fw-bold text-danger" style="font-size: 1.5em;">48130</td>
                        </tr>
                        <tr>
                            <td class="text-end fw-bold">Giải nhất</td>
                            <td class="text-center">66421</td>
                        </tr>
                        <tr>
                            <td class="text-end fw-bold">Giải nhì</td>
                            <td>
                                <div class="row text-center">
                                    <div class="col">73844</div>
                                    <div class="col">41421</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-end fw-bold">Giải ba</td>
                            <td>
                                <div class="row text-center">
                                    <div class="col">62423</div>
                                    <div class="col">46621</div>
                                    <div class="col">17961</div>
                                    <div class="col">19630</div>
                                    <div class="col">55272</div>
                                    <div class="col">97320</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-end fw-bold">Giải tư</td>
                            <td>
                                <div class="row text-center">
                                    <div class="col">9526</div>
                                    <div class="col">7565</div>
                                    <div class="col">2651</div>
                                    <div class="col">1660</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-end fw-bold">Giải năm</td>
                            <td>
                                <div class="row text-center">
                                    <div class="col">9130</div>
                                    <div class="col">1718</div>
                                    <div class="col">4336</div>
                                    <div class="col">9548</div>
                                    <div class="col">9052</div>
                                    <div class="col">7386</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-end fw-bold">Giải sáu</td>
                            <td>
                                <div class="row text-center">
                                    <div class="col">119</div>
                                    <div class="col">731</div>
                                    <div class="col">059</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-end fw-bold">Giải bảy</td>
                            <td>
                                <div class="row text-center">
                                    <div class="col">63</div>
                                    <div class="col">26</div>
                                    <div class="col">78</div>
                                    <div class="col">06</div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <h4 class="text-center bg-secondary text-white py-2">Lô tô Miền Bắc {{ $date ?? 'Hôm nay' }}</h4>
                <div class="table-responsive">
                    <table class="table table-bordered text-center">
                        <tbody>
                            <tr>
                                <td>06</td>
                                <td>18</td>
                                <td>19</td>
                                <td>20</td>
                                <td>21</td>
                                <td>21</td>
                                <td>21</td>
                                <td>23</td>
                                <td>26</td>
                            </tr>
                            <tr>
                                <td>26</td>
                                <td>30</td>
                                <td>30</td>
                                <td class="text-danger">30</td>
                                <td>31</td>
                                <td>36</td>
                                <td>44</td>
                                <td>48</td>
                                <td>51</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-flex justify-content-center mt-4">
                <div class="btn-group">
                    <button class="btn btn-outline-primary active">Đầy đủ</button>
                    <button class="btn btn-outline-primary">2 số</button>
                    <button class="btn btn-outline-primary">3 số</button>
                </div>
                <button class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-expand-arrows-alt"></i> Phóng to
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

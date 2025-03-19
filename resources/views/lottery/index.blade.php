<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả Xổ số Miền Bắc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .region-title { color: #dc3545; font-weight: bold; }
        .date-select { max-width: 200px; }
        .prize-number { color: #dc3545; font-weight: bold; }
        .prize-table td { padding: 8px; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="text-center mb-4">
            <h1>Kết quả Xổ số Miền Bắc</h1>
            <p>Kết quả ngày {{ date('d/m/Y') }}</p>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="region-title">XSMB</h5>
                        <p>Miền Bắc</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="region-title">XSMT</h5>
                        <p>Miền Trung</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="region-title">XSMN</h5>
                        <p>Miền Nam</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <select class="form-select date-select">
                <option selected>Chọn ngày</option>
                @for($i = 0; $i < 7; $i++)
                    <option value="{{ date('Y-m-d', strtotime("-$i days")) }}">
                        {{ date('d/m/Y', strtotime("-$i days")) }}
                    </option>
                @endfor
            </select>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table prize-table">
                    <tbody>
                        <tr>
                            <td width="200">Giải Đặc Biệt</td>
                            <td><span class="prize-number">12345</span></td>
                        </tr>
                        <tr>
                            <td>Giải Nhất</td>
                            <td><span class="prize-number">67890</span></td>
                        </tr>
                        <tr>
                            <td>Giải Nhì</td>
                            <td>
                                <span class="prize-number">11111</span>
                                <span class="prize-number ms-3">22222</span>
                            </td>
                        </tr>
                        <tr>
                            <td>Giải Ba</td>
                            <td>
                                <span class="prize-number">33333</span>
                                <span class="prize-number ms-3">44444</span>
                                <span class="prize-number ms-3">55555</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
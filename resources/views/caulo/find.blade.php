
@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Tìm Cầu Lô</h2>
    <div class="card">
        <div class="card-body">
            <input type="date" id="searchDate" class="form-control mb-3" value="{{ date('Y-m-d') }}">
            <div id="results" class="list-group">
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('searchDate').addEventListener('change', function() {
        fetch(`/caulo/search?date=${this.value}`)
            .then(res => res.json())
            .then(data => {
                const results = document.getElementById('results');

                if (!data.length) {
                    results.innerHTML = `<div class="alert alert-warning">Không có cầu lô nào trúng liên tiếp trong ${this.value}</div>`;
                    return;
                }

                // Chọn kiểu hiển thị timeline (1, 2 hoặc 3)
                const timelineStyle = 1;

                results.innerHTML = data.slice(0, 20).map(hit => {
                    const daysArray = hit.ngay_trung.split(',');

                    let timelineHTML = '';
                    if (timelineStyle === 1) {
                        // Kiểu 1: Danh sách ngang (Badges) có link
                        timelineHTML = daysArray.map(day => `
                        <a href="/caulo/timeline/${hit.cau_lo_id}?date=${day}" class="badge bg-success me-1">${day}</a>
                    `).join('');
                    } else if (timelineStyle === 2) {
                        // Kiểu 2: Danh sách dọc (List Group) có link
                        timelineHTML = `<ul class="list-group list-group-flush">` +
                            daysArray.map(day => `
                            <li class="list-group-item">
                                <a href="/caulo/timeline/${hit.cau_lo_id}?date=${day}" class="text-decoration-none">${day}</a>
                            </li>
                        `).join('') +
                            `</ul>`;
                    } else if (timelineStyle === 3) {
                        // Kiểu 3: Thanh tiến trình (Progress Bar) có link
                        const progressWidth = 100 / daysArray.length;
                        timelineHTML = `<div class="progress">` +
                            daysArray.map(day => `
                            <a href="/caulo/timeline/${hit.cau_lo_id}?date=${day}"
                               class="progress-bar bg-success text-white"
                               style="width: ${progressWidth}%">${day}</a>
                        `).join('') +
                            `</div>`;
                    }

                    return `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-1"><strong>Cầu Lô:</strong> ${hit.formula_name}</h5>
                                <span class="badge bg-info text-dark">${hit.combination_type}</span>
                                <p class="mb-1">
                                    <strong>Cấu trúc:</strong>
                                    <pre class="bg-light p-2 rounded">${JSON.stringify(hit.formula_structure, null, 2)}</pre>
                                </p>
                                <strong>Ngày trúng:</strong>
                                <div class="mt-2">${timelineHTML}</div>
                            </div>
                        </div>
                    </div>
                `;
                }).join('');
            });
    });
</script>
@endsection

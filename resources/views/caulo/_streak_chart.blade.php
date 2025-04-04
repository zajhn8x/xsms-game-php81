<div class="card mb-4">
    <div class="card-header">
        <h5>Biểu đồ Streak</h5>
    </div>
    <div class="card-body">
        <div class="streak-chart">
            @foreach($streakData as $date => $streak)
                <div class="streak-bar" style="
                        height: {{ abs($streak) * 20 }}px;
                        background-color: {{ match($streak) {
                        2 => '#007bff', // Xanh dương
                        3 => '#28a745', // Xanh lá
                        4 => '#fd7e14', // Cam
                        5 => '#dc3545', // Đỏ
                        default => '#343a40' // Đen (gián đoạn)
                    } }}"
                     title="{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }} - Streak: {{ abs($streak) }}"
                ></div>
            @endforeach
        </div>
    </div>
</div>

<style>
    .streak-chart {
        display: flex;
        align-items: flex-end;
        gap: 2px;
        height: 120px;
        padding: 10px;
    }

    .streak-bar {
        flex: 1;
        min-width: 10px;
        transition: height 0.3s ease;
    }
</style>

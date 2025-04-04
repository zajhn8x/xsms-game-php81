<div>
    <div class="list-group">
        @foreach($timelineData['dateRange'] ?? [] as $date)
            @php
                $hit = $timelineData['hits'][$date] ?? null;
                $result = $timelineData['results'][$date] ?? null;
                $formulaValues = isset($timelineData['resultsIndexs'][$date]) ? $timelineData['resultsIndexs'][$date]['values'] : [];
                $formulaPairs = isset($timelineData['resultsIndexs'][$date]) ? $timelineData['resultsIndexs'][$date]['pairs'] : [];
            @endphp
            <div class="list-group-item {{ $hit ? 'list-group-item-success' : '' }}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">📅 {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</h5>
                        @if($result)
                            <small class="text-muted">Kết quả: {{ $result->result_string ?? 'N/A' }}</small>
                        @endif
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @if(isset($timelineData['resultsIndexs'][$date]))
                            <div>
                                <strong>Cặp số:</strong>
                                @foreach($formulaPairs as $pair)
                                    <span class="badge bg-primary">{{ $pair }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($hasMore)
        <div class="text-center mt-3 mb-3">
            <button wire:click="loadMore" class="btn btn-primary">
                <span wire:loading.remove>Tải thêm</span>
                <span wire:loading>Đang tải...</span>
            </button>
        </div>
    @endif
</div>

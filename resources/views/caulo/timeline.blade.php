
@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Timeline Cầu Lô #{{ $cauLo->id }}</h2>
    <div class="card">
        <div class="card-body">
            <div id="timeline"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/vis-timeline@7.7.2/dist/vis-timeline-graph2d.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/vis-timeline@7.7.2/dist/vis-timeline-graph2d.min.css" rel="stylesheet">

<script>
const hits = @json($hits);
const items = hits.map(hit => ({
    id: hit.id,
    content: hit.so_trung,
    start: hit.ngay,
    type: 'point',
    title: `Ngày: ${hit.ngay}<br>Số trúng: ${hit.so_trung}`
}));

const container = document.getElementById('timeline');
const timeline = new vis.Timeline(container, new vis.DataSet(items), {
    height: '300px',
    start: new Date().setDate(new Date().getDate() - 30),
    end: new Date()
});
</script>
@endsection

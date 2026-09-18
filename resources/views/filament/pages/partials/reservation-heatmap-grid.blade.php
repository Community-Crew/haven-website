@php
    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $hours = range($hourRange[0], $hourRange[1]);
@endphp

<div
    class="reservation-heatmap {{ $size === 'sm' ? 'reservation-heatmap--sm' : '' }}"
    style="--hour-count: {{ count($hours) }};"
>
    <div class="reservation-heatmap__label"></div>
    @foreach ($hours as $hour)
        <div class="reservation-heatmap__hour">{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}</div>
    @endforeach

    @foreach ($days as $dayIndex => $dayLabel)
        <div class="reservation-heatmap__label">{{ $dayLabel }}</div>
        @foreach ($hours as $hour)
            @php
                $count = $data['grid'][$dayIndex][$hour];
                $color = $data['colors'][$dayIndex][$hour];
            @endphp
            <div
                class="reservation-heatmap__cell"
                style="background-color: {{ $color }};"
                data-tooltip="{{ $dayLabel }} {{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}:00 - {{ $count }} reservation(s)"
            ></div>
        @endforeach
    @endforeach
</div>

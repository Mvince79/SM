@if ($getRecord()->lat && $getRecord()->lng)
    @php
        $lat = (float) $getRecord()->lat;
        $lng = (float) $getRecord()->lng;

        $bbox = ($lng - 0.01) . ',' .
            ($lat - 0.01) . ',' .
            ($lng + 0.01) . ',' .
            ($lat + 0.01);
    @endphp

    <iframe
        width="100%"
        height="300"
        style="border:0; border-radius:12px;"
        loading="lazy"
        src="https://www.openstreetmap.org/export/embed.html?bbox={{ $bbox }}&layer=mapnik&marker={{ $lat }},{{ $lng }}">
    </iframe>
@else
    <div class="text-sm text-gray-500">
        Không có dữ liệu GPS
    </div>
@endif
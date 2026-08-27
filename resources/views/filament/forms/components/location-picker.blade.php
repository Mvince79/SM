{{--
    V-01 — Field Filament `LocationPicker` chỉ là lớp mỏng bọc lấy component Blade
    dùng chung `<x-location-picker>` (resources/views/components/location-picker.blade.php).

    Nhờ vậy form missing, form found và các resource Filament (HandoverPointResource,
    MissingChildResource) dùng CHUNG một implementation duy nhất: cùng logic ghim
    bằng chạm/kéo, cùng cách ghi toạ độ, không còn 4 bản Leaflet copy-paste.

    Field không dehydrate giá trị của chính nó (`->dehydrated(false)`); nó ghi trực
    tiếp vào 2 field `lat` / `lng` cùng cấp trong schema.
--}}
@php
    $statePath = $getStatePath();
    $latPath = str_replace('location_picker', 'lat', $statePath);
    $lngPath = str_replace('location_picker', 'lng', $statePath);
@endphp

<x-location-picker :lat-wire="$latPath" :lng-wire="$lngPath" :map-id="'lp-' . str_replace(['.', '_'], '-', $statePath)"
    :label="null" :hint="null" :hidden-fields="false" :sovereignty="false" theme="filament" :default-lat="10.045162"
    :default-lng="105.746857" :default-zoom="13" />

<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
    Chạm/click vào bản đồ hoặc kéo marker để chọn vị trí. Vĩ độ và kinh độ được cập nhật tự động.
</p>

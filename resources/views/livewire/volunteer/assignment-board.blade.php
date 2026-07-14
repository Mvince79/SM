<div
    x-data="{
        loadingLocation: false,
        locationError: '',

        requestLocation() {
            this.locationError = '';
            this.loadingLocation = true;

            if (! navigator.geolocation) {
                this.locationError = 'Trình duyệt không hỗ trợ định vị.';
                this.loadingLocation = false;

                return;
            }

            /*
             * Layout của dự án có gps-guard.
             * Cần mở quyền cho lần gọi GPS tiếp theo sau thao tác click.
             */
            if (typeof window.allowGeolocationForNextCall === 'function') {
                const allowed = window.allowGeolocationForNextCall();

                if (! allowed) {
                    this.locationError =
                        'Không thể mở quyền định vị. Vui lòng bấm lại nút lấy vị trí.';
                    this.loadingLocation = false;

                    return;
                }
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    $wire.setLocation(
                        position.coords.latitude,
                        position.coords.longitude
                    )
                    .then(() => {
                        this.loadingLocation = false;
                    })
                    .catch(() => {
                        this.locationError =
                            'Không thể tải manh mối theo vị trí hiện tại.';
                        this.loadingLocation = false;
                    });
                },
                (error) => {
                    this.loadingLocation = false;

                    if (error.code === 1) {
                        this.locationError =
                            'Bạn đã từ chối quyền truy cập vị trí. Hãy cho phép vị trí trong cài đặt trình duyệt.';
                    } else if (error.code === 2) {
                        this.locationError =
                            'Không thể xác định vị trí hiện tại.';
                    } else if (error.code === 3) {
                        this.locationError =
                            'Yêu cầu vị trí đã hết thời gian chờ.';
                    } else {
                        this.locationError =
                            'Có lỗi khi lấy vị trí.';
                    }
                },
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 60000
                }
            );
        }
    }"
    class="space-y-6"
>
    @if (session()->has('success'))
        <div
            class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
        >
            {{ session('success') }}
        </div>
    @endif

    @error('assignment')
        <div
            class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
        >
            {{ $message }}
        </div>
    @enderror

    @error('result')
        <div
            class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
        >
            {{ $message }}
        </div>
    @enderror

    <section
        class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
    >
        <div
            class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
        >
            <div>
                <h2 class="text-xl font-semibold text-gray-900">
                    Manh mối gần bạn
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Cho phép truy cập vị trí để tìm các manh mối cần tình
                    nguyện viên xác minh.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div>
                    <label
                        for="radius"
                        class="mb-1 block text-sm font-medium text-gray-700"
                    >
                        Bán kính
                    </label>

                    <select
                        id="radius"
                        wire:model="radius"
                        wire:change="loadNearbyClues"
                        @disabled(! $locationLoaded)
                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="5">5 km</option>
                        <option value="10">10 km</option>
                        <option value="20">20 km</option>
                        <option value="50">50 km</option>
                    </select>
                </div>

                <button
                    type="button"
                    x-on:click="requestLocation()"
                    x-bind:disabled="loadingLocation"
                    class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span x-show="! loadingLocation">
                        Lấy vị trí hiện tại
                    </span>

                    <span x-show="loadingLocation" x-cloak>
                        Đang xác định vị trí...
                    </span>
                </button>
            </div>
        </div>

        <p
            x-show="locationError"
            x-text="locationError"
            x-cloak
            class="mt-3 text-sm text-red-600"
        ></p>

        @error('lat')
            <p class="mt-3 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror

        @error('lng')
            <p class="mt-3 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror

        <div
            wire:loading
            wire:target="setLocation,loadNearbyClues"
            class="mt-5"
        >
            <div
                class="rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600"
            >
                Đang tải manh mối gần bạn...
            </div>
        </div>

        <div
            wire:loading.remove
            wire:target="setLocation,loadNearbyClues"
            class="mt-5"
        >
            @if (! $locationLoaded)
                <div
                    class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center"
                >
                    <p class="text-sm text-gray-600">
                        Bạn chưa cung cấp vị trí hiện tại.
                    </p>
                </div>
            @elseif (count($nearbyClues) === 0)
                <div
                    class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center"
                >
                    <p class="text-sm text-gray-600">
                        Không có manh mối chưa được nhận trong bán kính đã chọn.
                    </p>
                </div>
            @else
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($nearbyClues as $clue)
                        <article
                            wire:key="nearby-clue-{{ $clue['id'] }}"
                            class="rounded-xl border border-gray-200 p-4"
                        >
                            <div
                                class="flex items-start justify-between gap-3"
                            >
                                <div>
                                    <p
                                        class="text-xs font-semibold uppercase tracking-wide text-blue-600"
                                    >
                                        {{ $clue['missing_child']['case_code'] ?? 'Không có mã hồ sơ' }}
                                    </p>

                                    <h3
                                        class="mt-1 font-semibold text-gray-900"
                                    >
                                        {{ $clue['missing_child']['full_name'] ?? 'Chưa xác định' }}
                                    </h3>
                                </div>

                                <span
                                    class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700"
                                >
                                    {{ number_format($clue['distance_km'], 2) }}
                                    km
                                </span>
                            </div>

                            <p
                                class="mt-3 line-clamp-4 text-sm text-gray-600"
                            >
                                {{ $clue['description'] ?: 'Không có mô tả.' }}
                            </p>

                            <div
                                class="mt-4 space-y-1 text-xs text-gray-500"
                            >
                                <p>
                                    Tạo lúc:
                                    {{ $clue['created_at'] ?? 'Không rõ' }}
                                </p>

                                <p>
                                    Tọa độ:
                                    {{ $clue['lat'] }},
                                    {{ $clue['lng'] }}
                                </p>
                            </div>

                            <button
                                type="button"
                                wire:click="acceptAssignment({{ $clue['id'] }})"
                                wire:loading.attr="disabled"
                                wire:target="acceptAssignment({{ $clue['id'] }})"
                                class="mt-4 inline-flex w-full items-center justify-center rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <span
                                    wire:loading.remove
                                    wire:target="acceptAssignment({{ $clue['id'] }})"
                                >
                                    Nhận xác minh
                                </span>

                                <span
                                    wire:loading
                                    wire:target="acceptAssignment({{ $clue['id'] }})"
                                >
                                    Đang nhận nhiệm vụ...
                                </span>
                            </button>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section
        class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
    >
        <div>
            <h2 class="text-xl font-semibold text-gray-900">
                Nhiệm vụ của tôi
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Theo dõi các nhiệm vụ đã nhận và gửi kết quả xác minh.
            </p>
        </div>

        <div class="mt-5">
            @if (count($myAssignments) === 0)
                <div
                    class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center"
                >
                    <p class="text-sm text-gray-600">
                        Bạn chưa nhận nhiệm vụ nào.
                    </p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($myAssignments as $assignment)
                        <article
                            wire:key="assignment-{{ $assignment['id'] }}"
                            class="rounded-xl border border-gray-200 p-4"
                        >
                            <div
                                class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
                            >
                                <div class="min-w-0">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <h3
                                            class="font-semibold text-gray-900"
                                        >
                                            {{ $assignment['clue']['missing_child']['full_name'] ?? 'Chưa xác định' }}
                                        </h3>

                                        @php
                                            $statusClasses = match ($assignment['status']) {
                                                'ASSIGNED' => 'bg-amber-50 text-amber-700',
                                                'VERIFIED' => 'bg-green-50 text-green-700',
                                                'COULD_NOT_VERIFY' => 'bg-gray-100 text-gray-700',
                                                default => 'bg-gray-100 text-gray-700',
                                            };

                                            $statusLabel = match ($assignment['status']) {
                                                'ASSIGNED' => 'Đang xác minh',
                                                'VERIFIED' => 'Đã xác minh',
                                                'COULD_NOT_VERIFY' => 'Không thể xác minh',
                                                default => $assignment['status'],
                                            };
                                        @endphp

                                        <span
                                            class="rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClasses }}"
                                        >
                                            {{ $statusLabel }}
                                        </span>
                                    </div>

                                    <p
                                        class="mt-1 text-xs font-medium text-blue-600"
                                    >
                                        {{ $assignment['clue']['missing_child']['case_code'] ?? 'Không có mã hồ sơ' }}
                                    </p>

                                    <p class="mt-3 text-sm text-gray-600">
                                        {{ $assignment['clue']['description'] ?? 'Không có mô tả.' }}
                                    </p>

                                    <div
                                        class="mt-3 space-y-1 text-xs text-gray-500"
                                    >
                                        <p>
                                            Nhận lúc:
                                            {{ $assignment['assigned_at'] ?? 'Không rõ' }}
                                        </p>

                                        @if ($assignment['completed_at'])
                                            <p>
                                                Hoàn thành lúc:
                                                {{ $assignment['completed_at'] }}
                                            </p>
                                        @endif

                                        @if ($assignment['notes'])
                                            <p>
                                                Ghi chú:
                                                {{ $assignment['notes'] }}
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                @if ($assignment['status'] === 'ASSIGNED')
                                    <button
                                        type="button"
                                        wire:click="openResultForm({{ $assignment['id'] }})"
                                        class="inline-flex shrink-0 items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                                    >
                                        Nộp kết quả
                                    </button>
                                @endif
                            </div>

                            @if ($selectedAssignmentId === $assignment['id'])
                                <form
                                    wire:submit="submitResult"
                                    class="mt-5 space-y-4 border-t border-gray-200 pt-5"
                                >
                                    <div>
                                        <label
                                            for="result-status-{{ $assignment['id'] }}"
                                            class="mb-1 block text-sm font-medium text-gray-700"
                                        >
                                            Kết quả xác minh
                                        </label>

                                        <select
                                            id="result-status-{{ $assignment['id'] }}"
                                            wire:model="resultStatus"
                                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        >
                                            <option value="">
                                                Chọn kết quả
                                            </option>

                                            <option value="VERIFIED">
                                                Đã xác minh đúng
                                            </option>

                                            <option value="COULD_NOT_VERIFY">
                                                Không thể xác minh
                                            </option>
                                        </select>

                                        @error('resultStatus')
                                            <p
                                                class="mt-1 text-sm text-red-600"
                                            >
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            for="notes-{{ $assignment['id'] }}"
                                            class="mb-1 block text-sm font-medium text-gray-700"
                                        >
                                            Ghi chú
                                        </label>

                                        <textarea
                                            id="notes-{{ $assignment['id'] }}"
                                            wire:model="notes"
                                            rows="4"
                                            maxlength="2000"
                                            placeholder="Mô tả kết quả xác minh thực tế..."
                                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        ></textarea>

                                        @error('notes')
                                            <p
                                                class="mt-1 text-sm text-red-600"
                                            >
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div class="flex flex-wrap gap-3">
                                        <button
                                            type="submit"
                                            wire:loading.attr="disabled"
                                            wire:target="submitResult"
                                            class="inline-flex items-center justify-center rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            <span
                                                wire:loading.remove
                                                wire:target="submitResult"
                                            >
                                                Gửi kết quả
                                            </span>

                                            <span
                                                wire:loading
                                                wire:target="submitResult"
                                            >
                                                Đang gửi...
                                            </span>
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="closeResultForm"
                                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                        >
                                            Hủy
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</div>
@extends('layouts.app')

@section('title', 'Quy trình bàn giao an toàn')

@section('content')
    <main class="bg-gray-50 py-10 sm:py-14">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            {{-- Tiêu đề --}}
            <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="bg-gradient-to-r from-blue-700 to-cyan-600 px-6 py-10 text-white sm:px-10">
                    <p class="mb-3 text-sm font-semibold uppercase tracking-wider text-blue-100">
                        Bảo vệ trẻ em là ưu tiên cao nhất
                    </p>

                    <h1 class="text-3xl font-bold sm:text-4xl">
                        Quy trình bàn giao trẻ an toàn
                    </h1>

                    <p class="mt-4 max-w-3xl text-base leading-7 text-blue-50 sm:text-lg">
                        TìmTrẻ.vn chỉ hỗ trợ định vị, xác thực thông tin và kết nối
                        các bên liên quan. Việc tiếp nhận và bàn giao trẻ phải được
                        thực hiện thông qua cơ quan, tổ chức hoặc điểm tiếp nhận có
                        thẩm quyền.
                    </p>
                </div>

                <div class="space-y-10 px-6 py-8 sm:px-10 sm:py-10">
                    {{-- Cảnh báo --}}
                    <section
                        class="rounded-2xl border border-amber-300 bg-amber-50 p-5"
                        aria-labelledby="important-notice"
                    >
                        <h2
                            id="important-notice"
                            class="flex items-center gap-2 text-lg font-bold text-amber-900"
                        >
                            <span aria-hidden="true">⚠️</span>
                            Không tự ý bàn giao trực tiếp
                        </h2>

                        <p class="mt-3 leading-7 text-amber-900">
                            Người phát hiện trẻ không nên tự thỏa thuận hoặc bàn giao
                            trẻ trực tiếp cho một cá nhân tự nhận là người thân khi
                            danh tính và mối quan hệ chưa được cơ quan có thẩm quyền
                            xác minh.
                        </p>
                    </section>

                    {{-- Lý do --}}
                    <section aria-labelledby="why-third-party">
                        <h2
                            id="why-third-party"
                            class="text-2xl font-bold text-gray-900"
                        >
                            Vì sao phải bàn giao qua bên thứ ba?
                        </h2>

                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                            <article class="rounded-2xl border border-gray-200 p-5">
                                <h3 class="font-semibold text-gray-900">
                                    Xác minh đúng người
                                </h3>

                                <p class="mt-2 leading-7 text-gray-600">
                                    Cơ quan tiếp nhận có thể kiểm tra giấy tờ, thông tin
                                    người giám hộ và mối quan hệ với trẻ trước khi thực
                                    hiện bàn giao.
                                </p>
                            </article>

                            <article class="rounded-2xl border border-gray-200 p-5">
                                <h3 class="font-semibold text-gray-900">
                                    Bảo vệ an toàn cho trẻ
                                </h3>

                                <p class="mt-2 leading-7 text-gray-600">
                                    Quy trình có giám sát giúp hạn chế nguy cơ trẻ bị
                                    đưa nhầm người, bị lợi dụng hoặc tiếp tục rơi vào
                                    tình huống không an toàn.
                                </p>
                            </article>

                            <article class="rounded-2xl border border-gray-200 p-5">
                                <h3 class="font-semibold text-gray-900">
                                    Có thông tin và biên nhận
                                </h3>

                                <p class="mt-2 leading-7 text-gray-600">
                                    Việc tiếp nhận tại đơn vị có thẩm quyền giúp ghi nhận
                                    thời gian, địa điểm và những người tham gia xử lý vụ việc.
                                </p>
                            </article>

                            <article class="rounded-2xl border border-gray-200 p-5">
                                <h3 class="font-semibold text-gray-900">
                                    Bảo vệ người hỗ trợ
                                </h3>

                                <p class="mt-2 leading-7 text-gray-600">
                                    Người phát hiện trẻ không phải tự mình xác minh,
                                    giữ trẻ quá lâu hoặc chịu trách nhiệm cho một cuộc
                                    bàn giao không được kiểm soát.
                                </p>
                            </article>
                        </div>
                    </section>

                    {{-- Quy trình --}}
                    <section aria-labelledby="handover-steps">
                        <h2
                            id="handover-steps"
                            class="text-2xl font-bold text-gray-900"
                        >
                            Quy trình bàn giao đề xuất
                        </h2>

                        <ol class="mt-6 space-y-5">
                            <li class="flex gap-4">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-700 font-bold text-white"
                                >
                                    1
                                </div>

                                <div>
                                    <h3 class="font-semibold text-gray-900">
                                        Đưa trẻ tới vị trí an toàn
                                    </h3>

                                    <p class="mt-1 leading-7 text-gray-600">
                                        Đưa trẻ ra khỏi khu vực nguy hiểm nhưng không tự
                                        ý di chuyển trẻ đi xa nếu không thật sự cần thiết.
                                        Trong trường hợp khẩn cấp, hãy liên hệ cơ quan chức năng.
                                    </p>
                                </div>
                            </li>

                            <li class="flex gap-4">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-700 font-bold text-white"
                                >
                                    2
                                </div>

                                <div>
                                    <h3 class="font-semibold text-gray-900">
                                        Liên hệ đơn vị tiếp nhận
                                    </h3>

                                    <p class="mt-1 leading-7 text-gray-600">
                                        Liên hệ công an, UBND địa phương, Tổng đài Quốc gia
                                        Bảo vệ Trẻ em 111 hoặc điểm tiếp nhận được công bố
                                        trên hệ thống.
                                    </p>
                                </div>
                            </li>

                            <li class="flex gap-4">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-700 font-bold text-white"
                                >
                                    3
                                </div>

                                <div>
                                    <h3 class="font-semibold text-gray-900">
                                        Cung cấp thông tin cần thiết
                                    </h3>

                                    <p class="mt-1 leading-7 text-gray-600">
                                        Cung cấp địa điểm phát hiện, thời gian, tình trạng
                                        hiện tại của trẻ và những thông tin liên quan mà
                                        bạn biết. Không đăng công khai dữ liệu riêng tư
                                        không cần thiết của trẻ.
                                    </p>
                                </div>
                            </li>

                            <li class="flex gap-4">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-700 font-bold text-white"
                                >
                                    4
                                </div>

                                <div>
                                    <h3 class="font-semibold text-gray-900">
                                        Bàn giao cho người có thẩm quyền
                                    </h3>

                                    <p class="mt-1 leading-7 text-gray-600">
                                        Chỉ bàn giao khi đại diện đơn vị tiếp nhận đã có mặt
                                        và xác nhận việc tiếp nhận. Không giao trẻ cho cá nhân
                                        chỉ dựa trên hình ảnh, tin nhắn hoặc lời xác nhận miệng.
                                    </p>
                                </div>
                            </li>

                            <li class="flex gap-4">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-700 font-bold text-white"
                                >
                                    5
                                </div>

                                <div>
                                    <h3 class="font-semibold text-gray-900">
                                        Lưu lại thông tin tiếp nhận
                                    </h3>

                                    <p class="mt-1 leading-7 text-gray-600">
                                        Ghi nhận tên đơn vị, địa điểm, thời gian bàn giao
                                        và thông tin liên hệ cần thiết để hỗ trợ việc xác minh
                                        sau đó.
                                    </p>
                                </div>
                            </li>
                        </ol>
                    </section>

                    {{-- Các việc không nên làm --}}
                    <section
                        class="rounded-2xl border border-red-200 bg-red-50 p-6"
                        aria-labelledby="do-not-do"
                    >
                        <h2
                            id="do-not-do"
                            class="text-xl font-bold text-red-900"
                        >
                            Những việc không nên làm
                        </h2>

                        <ul class="mt-4 space-y-3 text-red-900">
                            <li class="flex gap-3">
                                <span aria-hidden="true">•</span>
                                <span>
                                    Không tự xác định người tới nhận trẻ là cha, mẹ hoặc
                                    người giám hộ hợp pháp.
                                </span>
                            </li>

                            <li class="flex gap-3">
                                <span aria-hidden="true">•</span>
                                <span>
                                    Không công khai địa điểm chính xác của trẻ trên mạng xã hội.
                                </span>
                            </li>

                            <li class="flex gap-3">
                                <span aria-hidden="true">•</span>
                                <span>
                                    Không chia sẻ giấy tờ, hình ảnh riêng tư hoặc thông tin
                                    nhạy cảm của trẻ cho người không có trách nhiệm.
                                </span>
                            </li>

                            <li class="flex gap-3">
                                <span aria-hidden="true">•</span>
                                <span>
                                    Không yêu cầu hoặc nhận tiền để đổi lấy thông tin hay
                                    việc bàn giao trẻ.
                                </span>
                            </li>
                        </ul>
                    </section>

                    {{-- Link chéo H-08 --}}
                    <section
                        class="rounded-2xl bg-blue-50 p-6 text-center sm:p-8"
                        aria-labelledby="find-handover-point"
                    >
                        <h2
                            id="find-handover-point"
                            class="text-2xl font-bold text-gray-900"
                        >
                            Tìm điểm tiếp nhận gần bạn
                        </h2>

                        <p class="mx-auto mt-3 max-w-2xl leading-7 text-gray-600">
                            Tra cứu các điểm bàn giao, cơ quan và đơn vị tiếp nhận
                            được hiển thị trên hệ thống.
                        </p>

                        <a
                            href="{{ route('handover-points.index') }}"
                            class="mt-6 inline-flex items-center justify-center rounded-xl bg-blue-700 px-6 py-3 font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200"
                        >
                            Xem danh sách điểm tiếp nhận
                            <span class="ml-2" aria-hidden="true">→</span>
                        </a>
                    </section>

                    {{-- Căn cứ và miễn trừ --}}
                    <section class="border-t border-gray-200 pt-7 text-sm leading-6 text-gray-500">
                        <p>
                            Nội dung được xây dựng theo định hướng bảo vệ quyền, sự an toàn,
                            quyền riêng tư và lợi ích tốt nhất của trẻ em theo Luật Trẻ em 2016.
                        </p>

                        <p class="mt-2">
                            TìmTrẻ.vn không thay thế cơ quan nhà nước và không trực tiếp
                            quyết định người có quyền tiếp nhận trẻ. Trong tình huống khẩn cấp,
                            hãy liên hệ công an gần nhất hoặc Tổng đài Quốc gia Bảo vệ Trẻ em 111.
                        </p>
                    </section>
                </div>
            </section>
        </div>
    </main>
@endsection
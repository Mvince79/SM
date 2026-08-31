<?php

namespace App\Providers;

use App\Livewire\Volunteer\AssignmentBoard;
use App\Services\Notification\Contracts\FcmPushSender;
use App\Services\Notification\FcmHttpV1PushSender;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\OtpService::class);
        $this->app->singleton(\App\Services\RateLimiterService::class);
        $this->app->singleton(\App\Services\GoogleAuthService::class);
        $this->app->singleton(\App\Services\QrCodeService::class);
        $this->app->bind(FcmPushSender::class, FcmHttpV1PushSender::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Bước 6 — Bảo vệ quota miễn phí chatbot: 20 tin nhắn/giờ khách (IP),
        // 50/giờ tài khoản đã đăng nhập (sanctum).
        RateLimiter::for('chatbot', function (Request $request) {
            return $request->user('sanctum')
                ? Limit::perHour(50)->by('chatbot:user:'.$request->user('sanctum')->id)
                : Limit::perHour(20)->by('chatbot:ip:'.$request->ip());
        });

        // Tìm kiếm bằng hình ảnh (S-02): giới hạn tốc độ lấy từ Cài đặt hệ thống
        // (photo_search.rate_limit lần / photo_search.rate_window phút), mặc định
        // 5 lần/giờ/IP. Admin chỉnh được trên giao diện mà không sửa code.
        RateLimiter::for('photo-search', function (Request $request) {
            $settings = app(\App\Settings\SettingService::class);
            $maxAttempts = (int) $settings->get('photo_search_rate_limit');
            $windowMinutes = (int) $settings->get('photo_search_rate_window');

            return Limit::perMinutes($windowMinutes, $maxAttempts)
                ->by('photo-search:'.$request->ip());
        });

        // Register Policy
        Gate::policy(
            \App\Models\MissingChild::class,
            \App\Policies\MissingChildPolicy::class
        );

        // Register Observer
        \App\Models\MissingChild::observe(
            \App\Observers\MissingChildObserver::class
        );

        \App\Models\FoundChild::observe(
            \App\Observers\FoundChildObserver::class
        );

        \App\Models\Photo::observe(
            \App\Observers\PhotoObserver::class
        );

        \App\Models\FaceMatchResult::observe(
            \App\Observers\FaceMatchResultObserver::class
        );

        \App\Models\User::observe(
            \App\Observers\UserObserver::class
        );

        \App\Models\Clue::observe(
            \App\Observers\ClueObserver::class
        );

        \App\Models\SupportTicketReply::observe(
            \App\Observers\SupportTicketReplyObserver::class
        );

        \App\Models\SupportTicket::observe(
            \App\Observers\SupportTicketObserver::class
        );

        // D-01 — Audit log mọi thay đổi status của MissingChild / FoundChild.
        \App\Models\MissingChild::observe(
            \App\Observers\StatusChangeAuditObserver::class
        );
        \App\Models\FoundChild::observe(
            \App\Observers\StatusChangeAuditObserver::class
        );

        // Register Livewire component
        Livewire::component(
            'volunteer.assignment-board',
            AssignmentBoard::class
        );
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed default Admin Account (DUY NHẤT 1 tài khoản quản trị để đăng nhập)
        \App\Models\User::firstOrCreate(
            ['email' => 'admin@timkiemtreem.vn'],
            [
                'full_name' => 'Hệ thống Admin',
                'phone' => '0905123456',
                'password' => \Illuminate\Support\Facades\Hash::make('Admin@123456'),
                'role' => 'ADMIN',
                'status' => 'ACTIVE',
                'email_verified_at' => now(),
            ]
        );

        // Chỉ seed dữ liệu tối thiểu, sạch sẽ để chuẩn bị thi:
        //  - ProvincesSeeder     : dữ liệu tham chiếu tỉnh/thành (bản đồ, địa chỉ, ranh giới)
        //  - HandoverPointsSeeder: điểm bàn giao (tham chiếu, phục vụ định vị)
        //  - DemoArticlesSeeder  : bài viết/SEO
        // ĐÃ LOẠI BỎ toàn bộ seeder demo giao dịch (Users, MissingChildren, Photos,
        // Clues, DemoMissingChildren, DemoFoundChildren): chúng sinh photo chờ xử lý
        // AI, thông báo, escalation... gây treo/kẹt/tắc nghẽn luồng. Giữ lại sẽ khiến
        // worker AI / thông báo cố xử lý dữ liệu ảo. DemoEscalationSeeder không nằm
        // trong danh sách gọi nên mặc định không sinh dữ liệu escalation.
        $this->call([
            ProvincesSeeder::class,
            HandoverPointsSeeder::class,
            DemoArticlesSeeder::class,
        ]);

        // Xoá toàn bộ cache (config/route/view/event/cache) để sau
        // `php artisan migrate:fresh --seed` mọi thứ thực sự sạch, tránh cache cũ
        // gây treo/kẹt luồng. bọc try/catch để không làm gãy quá trình seeding
        // nếu môi trường không cho xoá cache.
        try {
            \Illuminate\Support\Facades\Artisan::call('optimize:clear');
            $this->command?->info('Đã xoá cache (optimize:clear).');
        } catch (\Throwable $e) {
            // bỏ qua — không chặn seeding
        }
    }
}

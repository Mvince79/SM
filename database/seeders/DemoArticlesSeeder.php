<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use RuntimeException;

class DemoArticlesSeeder extends Seeder
{
    /**
     * O-04 — Seed 3 bài viết SEO mẫu phục vụ demo và Rich Results Test.
     */
    public function run(): void
    {
        $author = User::query()
            ->where('email', 'admin@timkiemtreem.vn')
            ->first();

        if (! $author) {
            $author = User::query()
                ->whereIn('role', ['ADMIN', 'MODERATOR'])
                ->orderBy('id')
                ->first();
        }

        if (! $author) {
            throw new RuntimeException(
                'Không tìm thấy tài khoản ADMIN hoặc MODERATOR để làm tác giả bài viết.'
            );
        }

        $articles = [
            [
                'slug' => 'can-lam-gi-khi-phat-hien-tre-di-lac',
                'title' => 'Cần làm gì khi phát hiện một trẻ em đi lạc?',
                'meta_title' => 'Cần làm gì khi phát hiện trẻ đi lạc? | TìmTrẻ.vn',
                'meta_description' => 'Hướng dẫn các bước an toàn cần thực hiện khi phát hiện trẻ em đi lạc, từ trấn an trẻ đến liên hệ cơ quan có thẩm quyền.',
                'content' => <<<'HTML'
<h2>Giữ bình tĩnh và bảo đảm an toàn cho trẻ</h2>

<p>Khi phát hiện một trẻ em có dấu hiệu đi lạc, điều đầu tiên cần làm là giữ bình tĩnh và đưa trẻ đến khu vực an toàn, dễ quan sát. Không nên tự ý đưa trẻ về nhà riêng hoặc di chuyển trẻ đến một địa điểm xa nơi phát hiện.</p>

<h2>Hỏi thông tin một cách nhẹ nhàng</h2>

<p>Hãy hỏi tên, tuổi, tên cha mẹ hoặc người chăm sóc và địa điểm trẻ nhớ được. Không gây áp lực nếu trẻ đang sợ hãi, hoảng loạn hoặc không thể trả lời rõ ràng.</p>

<h2>Thông báo cho đơn vị có thẩm quyền</h2>

<p>Liên hệ công an gần nhất, UBND địa phương, Tổng đài quốc gia bảo vệ trẻ em 111 hoặc một điểm tiếp nhận có thẩm quyền. Việc bàn giao trẻ cần được thực hiện minh bạch và có người chịu trách nhiệm xác nhận.</p>

<h2>Không tự đăng tải thông tin nhạy cảm</h2>

<p>Không công khai địa chỉ, số điện thoại cá nhân hoặc thông tin nhận dạng nhạy cảm của trẻ trên mạng xã hội. Chỉ cung cấp thông tin cần thiết cho cơ quan chức năng hoặc nền tảng hỗ trợ đáng tin cậy.</p>

<p>TìmTrẻ.vn hỗ trợ định vị, xác thực và kết nối các bên liên quan; việc tiếp nhận trẻ trên thực tế luôn cần thông qua đơn vị có thẩm quyền.</p>
HTML,
                'published_at' => Carbon::parse('2026-07-25 08:00:00'),
            ],
            [
                'slug' => 'quy-trinh-ban-giao-tre-em-an-toan',
                'title' => 'Quy trình bàn giao trẻ em an toàn qua đơn vị có thẩm quyền',
                'meta_title' => 'Quy trình bàn giao trẻ em an toàn | TìmTrẻ.vn',
                'meta_description' => 'Tìm hiểu quy trình xác minh và bàn giao trẻ em an toàn thông qua công an, UBND, Tổng đài 111 hoặc tổ chức có thẩm quyền.',
                'content' => <<<'HTML'
<h2>Vì sao không nên tự ý bàn giao trực tiếp?</h2>

<p>Việc hai cá nhân tự thỏa thuận giao nhận trẻ có thể dẫn đến nhầm lẫn danh tính, tranh chấp hoặc nguy cơ mất an toàn. Vì vậy, quá trình tiếp nhận cần có một bên thứ ba đủ thẩm quyền tham gia xác minh.</p>

<h2>Bước 1: Xác định điểm tiếp nhận phù hợp</h2>

<p>Người đang hỗ trợ trẻ nên lựa chọn công an xã, phường, UBND địa phương, Tổng đài quốc gia bảo vệ trẻ em 111 hoặc tổ chức bảo vệ trẻ em được xác minh.</p>

<h2>Bước 2: Xác minh thông tin liên quan</h2>

<p>Cơ quan tiếp nhận kiểm tra thông tin của trẻ, người phát hiện và gia đình đang tìm kiếm. Các dữ liệu nhận diện chỉ được sử dụng trong phạm vi cần thiết để hỗ trợ xác minh.</p>

<h2>Bước 3: Ghi nhận việc tiếp nhận</h2>

<p>Thời gian, địa điểm và người xác nhận tiếp nhận cần được ghi nhận rõ ràng. Điều này giúp bảo đảm tính minh bạch và hỗ trợ đối chiếu khi cần thiết.</p>

<h2>Bước 4: Thông báo cho gia đình</h2>

<p>Sau khi xác minh phù hợp, gia đình được hướng dẫn đến đúng điểm tiếp nhận. Trẻ chỉ được bàn giao sau khi cơ quan có thẩm quyền hoàn thành quy trình xác nhận cần thiết.</p>

<p>Nền tảng TìmTrẻ.vn không tự động hóa việc bàn giao trẻ giữa hai cá nhân mà chỉ hỗ trợ định vị, xác thực và kết nối.</p>
HTML,
                'published_at' => Carbon::parse('2026-07-26 08:00:00'),
            ],
            [
                'slug' => 'bao-ve-thong-tin-ca-nhan-tre-em-tren-internet',
                'title' => 'Cách bảo vệ thông tin cá nhân của trẻ em trên Internet',
                'meta_title' => 'Bảo vệ thông tin trẻ em trên Internet | TìmTrẻ.vn',
                'meta_description' => 'Các nguyên tắc giúp phụ huynh bảo vệ hình ảnh, vị trí, số điện thoại và dữ liệu cá nhân của trẻ khi đăng thông tin trên Internet.',
                'content' => <<<'HTML'
<h2>Hạn chế công khai thông tin nhận dạng</h2>

<p>Phụ huynh không nên đăng công khai địa chỉ nhà, trường học, lịch sinh hoạt, số điện thoại hoặc giấy tờ cá nhân của trẻ. Những thông tin này có thể bị người khác thu thập và sử dụng sai mục đích.</p>

<h2>Kiểm tra kỹ hình ảnh trước khi đăng</h2>

<p>Ảnh có thể vô tình làm lộ biển số xe, bảng tên trường học, địa chỉ hoặc vị trí hiện tại. Trước khi đăng, hãy kiểm tra nền ảnh và che các chi tiết không cần thiết.</p>

<h2>Chỉ chia sẻ với đơn vị đáng tin cậy</h2>

<p>Trong trường hợp cần tìm kiếm trẻ thất lạc, hãy ưu tiên cung cấp thông tin cho cơ quan chức năng và các nền tảng có chính sách xác thực, bảo mật và kiểm soát quyền truy cập rõ ràng.</p>

<h2>Không chia sẻ dữ liệu vượt quá mục đích</h2>

<p>Chỉ cung cấp những dữ liệu thực sự cần cho việc tìm kiếm và xác minh. Khi vụ việc đã được giải quyết, cần xem xét ẩn hoặc gỡ các thông tin không còn cần thiết.</p>

<h2>Thường xuyên kiểm tra quyền riêng tư</h2>

<p>Phụ huynh nên rà soát quyền riêng tư của tài khoản mạng xã hội, giới hạn người xem bài viết và tránh chấp nhận các tài khoản không rõ danh tính.</p>

<p>Bảo vệ dữ liệu cá nhân là một phần quan trọng trong quá trình bảo đảm an toàn lâu dài cho trẻ em.</p>
HTML,
                'published_at' => Carbon::parse('2026-07-27 08:00:00'),
            ],
        ];

        foreach ($articles as $articleData) {
            Article::query()->updateOrCreate(
                ['slug' => $articleData['slug']],
                [
                    'title' => $articleData['title'],
                    'content' => $articleData['content'],
                    'cover_image_path' => null,
                    'meta_title' => $articleData['meta_title'],
                    'meta_description' => $articleData['meta_description'],
                    'author_id' => $author->id,
                    'published_at' => $articleData['published_at'],
                ]
            );
        }

        $this->command?->info('Đã seed thành công 3 bài viết SEO mẫu.');
    }
}
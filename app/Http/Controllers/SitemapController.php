<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\FoundChild;
use App\Models\MissingChild;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    private const CACHE_KEY = 'sitemap:xml';

    private const CACHE_TTL_SECONDS = 3600; // 1 giờ, đúng DoD L-04

    public function index(): Response
    {
        $xml = Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            return $this->buildXml();
        });

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    private function buildXml(): string
    {
        $urls = collect()
            ->concat($this->staticUrls())
            ->concat($this->missingChildUrls())
            ->concat($this->foundChildUrls())
            ->concat($this->articleUrls());

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . e($url['loc']) . '</loc>' . "\n";
            if (!empty($url['lastmod'])) {
                $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
            }
            $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
            $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    private function staticUrls(): array
    {
        return [
            ['loc' => url('/'), 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => url('/missing'), 'changefreq' => 'daily', 'priority' => '0.9'],
            ['loc' => url('/diem-ban-giao'), 'changefreq' => 'weekly', 'priority' => '0.6'],
            ['loc' => url('/bai-viet'), 'changefreq' => 'daily', 'priority' => '0.7'],
        ];
    }

    private function missingChildUrls(): array
    {
        return MissingChild::query()
            ->where('status', 'APPROVED')
            ->select(['id', 'case_code', 'updated_at'])
            ->get()
            ->map(fn(MissingChild $case) => [
                'loc' => url('/missing/' . $case->case_code),
                'lastmod' => $case->updated_at?->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.8',
            ])
            ->all();
    }

    private function foundChildUrls(): array
    {
        // FoundChild hiện chưa có route public riêng theo case_code trong scope Sprint 4
        // -> chỉ thêm khi model có cột tương đương (id dùng tạm nếu route tồn tại).
        if (!\Illuminate\Support\Facades\Route::has('found.show')) {
            return [];
        }

        return FoundChild::query()
            ->where('status', 'APPROVED')
            ->select(['id', 'updated_at'])
            ->get()
            ->map(fn(FoundChild $case) => [
                'loc' => route('found.show', $case->id),
                'lastmod' => $case->updated_at?->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.7',
            ])
            ->all();
    }

    private function articleUrls(): array
    {
        return Article::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->select(['slug', 'updated_at'])
            ->get()
            ->map(fn(Article $article) => [
                'loc' => url('/bai-viet/' . $article->slug),
                'lastmod' => $article->updated_at?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ])
            ->all();
    }
}
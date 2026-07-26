@props(['type', 'data' => []])

@php
    $schema = match ($type) {
        'article' => [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $data['title'] ?? '',
            'description' => $data['meta_description'] ?? '',
            'image' => $data['image'] ?? asset('images/og-default.jpg'),
            'author' => [
                '@type' => 'Organization',
                'name' => $data['author_name'] ?? 'TìmTrẻ.vn',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'TìmTrẻ.vn',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('images/logo.png'),
                ],
            ],
            'datePublished' => optional($data['published_at'] ?? null)->toAtomString(),
            'dateModified' => optional($data['updated_at'] ?? null)->toAtomString(),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $data['url'] ?? url()->current(),
            ],
        ],
        'person' => [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $data['name'] ?? '',
            'image' => $data['image'] ?? asset('images/og-default.jpg'),
            'description' => $data['description'] ?? '',
            'additionalProperty' => array_values(array_filter([
                isset($data['age']) ? [
                    '@type' => 'PropertyValue',
                    'name' => 'Tuổi',
                    'value' => $data['age'],
                ] : null,
                isset($data['missing_location']) ? [
                    '@type' => 'PropertyValue',
                    'name' => 'Khu vực mất tích',
                    'value' => $data['missing_location'],
                ] : null,
                isset($data['missing_date']) ? [
                    '@type' => 'PropertyValue',
                    'name' => 'Ngày mất tích',
                    'value' => $data['missing_date'],
                ] : null,
                isset($data['case_code']) ? [
                    '@type' => 'PropertyValue',
                    'name' => 'Mã hồ sơ',
                    'value' => $data['case_code'],
                ] : null,
            ])),
        ],
        default => null,
    };
@endphp

@if ($schema)
    <script
        type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endif
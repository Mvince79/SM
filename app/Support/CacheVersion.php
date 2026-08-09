<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * CacheVersion
 *
 * C-01: "invalidate cả nhóm cache cùng lúc" mà KHÔNG cần driver hỗ trợ
 * Cache::tags() (chỉ redis/memcached/array hỗ trợ — driver 'database'
 * hay 'file' sẽ ném BadMethodCallException: "This cache store does not
 * support tagging").
 *
 * Cách làm: mỗi namespace (map-data, home-highlights...) có 1 số
 * "version" lưu trong cache. Mọi cache key thật sự đọc/ghi đều gắn kèm
 * version hiện tại, vd "map_data:v3:<hash>". Muốn xoá "cả nhóm" chỉ cần
 * tăng version lên — mọi key theo version cũ coi như không còn ai đọc
 * tới nữa (và sẽ tự biến mất sau khi hết TTL, TTL ở đây chỉ 3 phút nên
 * không tốn bộ nhớ đáng kể). Không cần biết trước có bao nhiêu key,
 * không cần driver hỗ trợ tag — chạy đúng như nhau trên redis, database,
 * file, array (kể cả CACHE_STORE=array lúc chạy test).
 */
final class CacheVersion
{
    public const MAP_DATA = 'map-data';

    public const HOME_HIGHLIGHTS = 'home-highlights';

    private const KEY_PREFIX = 'cache-version:';

    /**
     * Version hiện tại của 1 namespace. Mặc định 1 nếu chưa từng bump.
     */
    public static function current(string $namespace): int
    {
        return (int) Cache::get(self::versionKey($namespace), 1);
    }

    /**
     * Tăng version lên 1 — coi như "xoá" toàn bộ cache cũ của namespace
     * này (mọi key cũ sẽ không còn ai đọc/ghi tới nữa).
     *
     * increment() lưu version KHÔNG có TTL (Cache::forever) vì bản thân
     * số version cần tồn tại mãi (không được để nó tự hết hạn, nếu không
     * version sẽ tự nhảy về 1 và có thể trùng với 1 key cũ chưa kịp hết
     * hạn trong hiếm trường hợp — dù rủi ro rất thấp, cứ để version sống
     * mãi cho chắc).
     */
    public static function bump(string $namespace): void
    {
        $key = self::versionKey($namespace);

        if (Cache::has($key)) {
            Cache::increment($key);

            return;
        }

        Cache::forever($key, 2);
    }

    private static function versionKey(string $namespace): string
    {
        return self::KEY_PREFIX.$namespace;
    }
}

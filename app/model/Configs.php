<?php
/**
 *
 * User: 会飞的鱼
 * Date: 2023/7/29
 * QQ: 137691250
 * Email: <137691250@qq.com>
 */
declare (strict_types=1);
namespace app\model;


use think\facade\Cache;
use think\Model;

class Configs extends Model
{
    public static function gets($refresh = false): array
    {
        if ($refresh) self::refreshCache();
        return Cache::remember('siteConfig', self::getConfData());
    }

    private static function refreshCache()
    {
        $cacheKey = "siteConfig";
        $data = self::getConfData();
        cache($cacheKey, $data);
    }

    private static function getConfData(): array
    {
        $data = [];
        $list = self::select();
        foreach ($list as $item) {
            $data[$item['id']] = $item['value'];
        }
        return $data;

    }
}
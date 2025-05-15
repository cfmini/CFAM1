<?php
/**
 *
 * User: 会飞的鱼
 * Date: 2023/7/29
 * QQ: 137691250
 * Email: <137691250@qq.com>
 */

namespace app\middleware;


use app\model\Configs;
use think\facade\View;

class BaseMiddleware
{
    /**
     * 处理请求
     * 基础中间件
     * @param \think\Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        $config = Configs::gets(true);
        View::assign('configs', $config);
        return $next($request);
    }
}
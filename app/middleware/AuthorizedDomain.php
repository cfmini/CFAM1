<?php
/**
 *
 * @Author: 会飞的鱼
 * @Date: 2024/1/30 0030
 * @Time: 18:31
 * @Blog：https://houz.cn/
 * @Description: 会飞的鱼作品,禁止修改版权违者必究。
 */


namespace app\middleware;


class AuthorizedDomain
{
    public function handle($request, \Closure $next)
    {
        return $next($request);
    }
}
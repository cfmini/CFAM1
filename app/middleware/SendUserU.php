<?php
/**
 *
 * User: 会飞的鱼
 * Date: 2023/7/29
 * QQ: 137691250
 * Email: <137691250@qq.com>
 */

namespace app\middleware;


use app\model\Send;
use think\facade\Session;
use think\facade\View;
use think\facade\Response;

class SendUserU
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        $res = Send::where('usn', session('USER_LOGIN_USN'))->find();
    
        if ($res !== null) {
            View::assign('Sendinfo', $res);
        } else {
            View::assign('Sendinfo', []);
        }
     
        return $next($request);
    }
}
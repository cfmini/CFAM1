<?php
/**
 *
 * User: 会飞的鱼
 * Date: 2023/8/5
 * QQ: 137691250
 * Email: <137691250@qq.com>
 */

declare (strict_types=1);
namespace app\middleware;


use app\model\Admins;
use think\facade\View;

class SuperAuth
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
        if (!session('ADMIN_LOGIN_ID')) {
            return redirect("/Adminlogin");
        }
        $userinfo = Admins::where('id', session('ADMIN_LOGIN_ID'))->find();
        if (!$userinfo) {
            return json([
                'code' => 500,
                'msg' => '权限不足',
            ]);
        }
        $request->User = $userinfo;
        View::assign('user', $request->User);
        return $next($request);
    }
}
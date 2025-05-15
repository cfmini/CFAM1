<?php
/**
 *
 * User: 会飞的鱼
 * Date: 2023/7/29
 * QQ: 137691250
 * Email: <137691250@qq.com>
 */

namespace app\middleware;


use app\model\Admins;
use think\facade\Session;
use think\facade\View;
use think\facade\Response;

class AuthU
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

            if ($request->isPost()) {
                return json([
                    'code' => 401,
                    'msg' => '先登录在进行操作'
                ]);
            } else {
                return redirect("/Adminlogin");

            }
        }

        $res = Admins::where('id',session('ADMIN_LOGIN_ID'))->find();


        if (!$res) {
            session::delete('ADMIN_LOGIN_ID');
            return Response::create()->header('Refresh', '0');

        }

        $userInfo = Admins::where('id', session('ADMIN_LOGIN_ID'))->find()->toArray();

        $request->User = $userInfo;
        View::assign('userinfo', $userInfo);
        View::assign('user', $request->User);
        return $next($request);
    }
}
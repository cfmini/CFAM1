<?php
/**
 *
 * User: 会飞的鱼
 * Date: 2023/7/29
 * QQ: 137691250
 * Email: <137691250@qq.com>
 */

namespace app\middleware;


use think\facade\Db;
use think\facade\Session;
use think\facade\View;
use think\Response;


class UserAuth
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
        if (!session('USER_LOGIN_USN')) {
            if ($request->isPost()) {
                return json([
                    'code' => 401,
                    'msg' => '先登录在进行操作'
                ]);
            } else {
                return redirect("/login");

            }
        }

        $res = Db::connect('sqlsrv')->table('CF_MEMBER')
            ->where('USN', session('USER_LOGIN_USN'))
            ->where('USER_ID', session('USER_LOGIN_ID'))
            ->find();

        if (!$res) {
            session::delete('USER_LOGIN_USN');
            session::delete('USER_LOGIN_ID');
            return Response::create()->header('Refresh', '0');

        }

        //$userInfo = Admins::where('id', session('ADMIN_LOGIN_ID'))->find()->toArray();

        $request->User = $res;
        View::assign('userinfo', $res);
        View::assign('user', $request->User);
        return $next($request);
    }
}
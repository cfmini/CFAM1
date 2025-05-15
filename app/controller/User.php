<?php
/**
 *
 * User: 会飞的鱼
 * Date: 2023/8/11
 * QQ: 137691250
 * Email: <137691250@qq.com>
 */

namespace app\controller;


use app\BaseController;
use app\middleware\BaseMiddleware;
use app\middleware\SendUserU;
use app\middleware\UserAuth;
use app\model\Cdk;
use app\model\CdkLog;
use app\model\Configs;
use app\model\Qqlogin;
use app\model\ShopLog;
use think\facade\Db;
use think\facade\View;


class User extends BaseController
{
    protected $middleware = [
        UserAuth::class,
        BaseMiddleware::class,
        SendUserU::class
    ];
    public function index(){

        $siteConfig = Configs::gets();

        $user = Db::connect('sqlsrv')->table('CF_USER')
            ->where('USN', session('USER_LOGIN_USN'))
            ->find();



        $lu = Db::connect('cf_g4box')->table('TAccountCashMst')
            ->where('UserNo', session('USER_LOGIN_USN'))
            ->find();

        $gp = empty($user) ? '无角色' : $user['GAME_POINT'];
        $nick = empty($user) ? '未创建' : $user['NICK'];
        $cf = empty($lu) ? '无角色' : $lu['Cash'];

        $qq = Qqlogin::where('usn',session('USER_LOGIN_USN'))->find();
        View::assign([
            'nick'=>$nick,
            'gp'=>$gp,
            'cf'=>$cf,
            'qq'=>$qq
        ]);
        return View::fetch($siteConfig['template'].'/user/index');
    }

    public function uppasswd(\think\Request $request){
        $siteConfig = Configs::gets();
        if (empty(input('passwd')) || empty(input('new_passwd')) || empty(input('new_passwd2'))){
            return json([
                'code' => 500,
                'msg' => '必填项不能为空'
            ]);
        }
        if (md5(input('passwd').$siteConfig['md5pass']) != $request->User['USER_PASS']){
            return json([
                'code' => 501,
                'msg' => '旧密码不正确'
            ]);
        }
        if (input('new_passwd') != input('new_passwd2')){
            return json([
                'code' => 502,
                'msg' => '新密码不一致'
            ]);
        }

        Db::connect('sqlsrv')->table('CF_MEMBER')->where('USN',session('USER_LOGIN_USN'))->update([
            'USER_PASS' => md5(input('new_passwd').$siteConfig['md5pass']),
        ]);

        return json([
            'code' => 200,
            'msg' => '修改成功'
        ]);
    }

    public function stash(){
        $siteConfig = Configs::gets();
        $key = input('keyword'); // 获取搜索关键字

        $query = Db::connect('sqlsrv')->table('CF_USER_INVENTORY')
            ->where('USN', session('USER_LOGIN_USN'))
            ->where('ITEM_CODE', 'like', '%' . $key . '%') // 添加模糊搜索条件
            ->order('REG_DATE', 'DESC')
            ->paginate(10);

        $item_name = Db::connect('sqlsrv')->table('CF_ITEM_INFO')
            ->whereIn('ITEM_CODE', array_column($query->items(), 'ITEM_CODE'))
            ->column('NAME', 'ITEM_CODE');

        $list = $query->items();
        foreach ($list as &$item) {
            $item['itemname'] = $item_name[$item['ITEM_CODE']];
        }

        $count = $query->total(); // 统计查询结果的数量

        return view($siteConfig['template'].'/user/stash', ['list' => $query, 'its' => $list, 'keyword' => $key, 'count' => $count]);
    }
    public function delitem(){

        Db::connect('sqlsrv')->table('CF_USER_INVENTORY')
            ->where('INVENTORY_SRL', input('id'))
            ->where('USN',session('USER_LOGIN_USN'))
            ->delete();

        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);
    }

    public function bought(){
        $siteConfig = Configs::gets();
        $limit = 10; // 每页显示的记录数

        $query = ShopLog::order('create_time', 'DESC')
            ->where('usn',session('USER_LOGIN_USN'))
            ->paginate($limit);

        return view($siteConfig['template'].'/user/bought', ['list' => $query]);
    }

    public function cdks(){
        $siteConfig = Configs::gets();
        $limit = 10; // 每页显示的记录数

        $query = CdkLog::order('create_time', 'DESC')
            ->where('usn',session('USER_LOGIN_USN'))
            ->paginate($limit);

        return view($siteConfig['template'].'/user/cdks', ['list' => $query]);
    }

    public function AjaxCdk(){

        if (empty(input('cdk'))){
            return json([
                'code' => 500,
                'msg' => '请输入Cdk'
            ]);
        }

        $res = Cdk::where('code',input('cdk'))->find();

        if ($res['status'] == 1){
            return json([
                'code' => 501,
                'msg' => 'Cdk已被使用'
            ]);
        }

        $reg_pay_sql = "EXECUTE WSP_GIVE_ITEM @p_USN = ?, @p_GiveUSN = ?, @p_ID = ?, @p_Name = '', @p_Result = 0";

        $result = Db::connect('cf_sa')->execute($reg_pay_sql, [session('USER_LOGIN_USN'), session('USER_LOGIN_USN'), $res['item_id']]);


        if (!$result){


            CdkLog::insert([
                'usn' => session('USER_LOGIN_USN'),
                'user' => session('USER_LOGIN_ID'),
                'name' => $res['name'],
                'cdk' => input('cdk'),
                'create_time' => time()
            ]);

            Cdk::where('code',input('cdk'))->update([
                'status' => 1
            ]);
            return json([
                'code' => 200,
                'msg' => '兑换成功，重新打开仓库即可看到'
            ]);

        }else{
            return json([
                'code' => 506,
                'msg' => '兑换失败'
            ]);
        }


    }

    public function clear_backpack(){
        if (input('id') != session('USER_LOGIN_USN')){
            return json([
                'code' => 500,
                'msg' => '请求出错！'
            ]);
        }
        $res = Db::connect('sqlsrv')->table('CF_USER_INVENTORY')
            ->where('USN',session('USER_LOGIN_USN'))
            ->delete();

        if (!$res){
            return json([
                'code' => 200,
                'msg' => '清空成功！'
            ]);
        }else{
            return json([
                'code' => 501,
                'msg' => '网络错误！'
            ]);
        }

    }




}
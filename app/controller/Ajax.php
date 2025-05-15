<?php


namespace app\controller;


use app\middleware\AjaxSet;
use app\middleware\BaseMiddleware;
use app\middleware\SuperAuth;
use app\model\Activity;
use app\model\ActivityLog;
use app\model\Admins;
use app\model\Configs;
use app\model\Huodong;
use app\model\Send;
use app\model\Feedback;
use app\model\Prize;
use app\model\PrizeLog;
use app\model\Qqlogin;
use app\model\RegLog;
use app\model\Shop;
use app\model\ShopLog;
use app\validate\Users;

use mailer\think\Mailer;

use think\facade\Db;

use think\facade\Request;
use think\facade\Cache;
use think\facade\Session;
use think\captcha\facade\Captcha;


class Ajax
{
    protected $middleware = [
        BaseMiddleware::class
    ];

    public function geetest_auth(){
        Cache::set('lot_number', input('lot_number'), 30);

        Cache::set('pass_token', input('pass_token'), 30);

        Cache::set('gen_time', input('gen_time'), 30);

        Cache::set('captcha_output', input('captcha_output'), 30);


        return json([
            'msg' => 'ok！'
        ]);
    }

    public function GetPass(){
        try {

            if (empty(input('inputEmail'))){
                return json([
                    'code' => 500,
                    'msg' => "邮箱不能为空！"
                ]);
            }


            if(checkEmail(input('inputEmail'))==0){
                return json([
                    'code' => 500,
                    'msg' => "邮箱格式不正确！"
                ]);
            }

            $user =Db::connect('sqlsrv')->table('CF_MEMBER')
                ->where('EMAIL', input('inputEmail'))
                ->find();

            if (!$user){
                return json([
                    'code' => 500,
                    'msg' => '邮箱不存在！'
                ]);
            }

            validate(Users::class)->scene('updateCode')->check([
                'email' => input('inputEmail')
            ]);


            if(!empty($_COOKIE['ajax_retrieve_code'])){
                return json([
                    'code' => 500,
                    'msg' => "请1分钟后再获取验证码"
                ]);
            }



            $code = mt_rand(100000, 999999);

            cache("mailCode:" . input('inputEmail') . 'ajax_retrieve_code', $code, 300);

            $mailer = Mailer::instance();

            $mailer->from('137691250@qq.com')
                ->to(input('inputEmail'))
                ->subject('找回密码')
                ->html('验证码：'.$code.' 5分钟后失效')
                ->send();




            setcookie("ajax_retrieve_code", $code,time()+60);

            return json([
                'code' => 200,
                'msg' => "验证码发送成功"
            ]);
        } catch (ValidateException $exception) {
            return json([
                'code' => 500,
                'msg' => $exception->getMessage()
            ]);
        }
    }

    public function CodeS(){
        $siteConfig = Configs::gets();
        if ($siteConfig['Reg_Switch'] !=1){
            return json([
                'code' => 500,
                'msg' => '暂时未开放注册'
            ]);
        }
//        if (empty(input('gd_code'))){
//            return json([
//                'code' => 500,
//                'msg' => '验证码不能为空！'
//            ]);
//        }
//
//        if( !captcha_check(input('gd_code') ))
//        {
//            return json([
//                'code' => 504,
//                'msg' => '验证码错误'
//            ]);
//        }
        //发送手机验证码
        $sms = new Sms;
        $sms -> send_sms(input('mobile'),input('gd_code'));



//        return json([
//            'code' => 200,
//            'msg' => '验证码已发送到您的手机'.input('mobile')
//        ]);
    }

    public function Codemail(){
        try {
            $siteConfig = Configs::gets();

            //检测是否开启注册
            if ($siteConfig['Reg_Switch'] !=1){
                return json([
                    'code' => 500,
                    'msg' => '暂时未开放注册'
                ]);
            }


            if ($siteConfig['ips']==1){
                $Inspect = RegLog::where('ip',Request::ip())->find();
                if($Inspect){
                    return json([
                        'code' => 509,
                        'msg' => '注册频繁，请稍后再试！'
                    ]);
                }
            }

            if(checkEmail(input('email'))==0){
                return json([
                    'code' => 500,
                    'msg' => "邮箱格式不正确"
                ]);
            }
            validate(Users::class)->scene('sendCode')->check([
                'email' => input('email')
            ]);


//            if(!empty($_COOKIE['register_code'])){
//                return json([
//                    'code' => 500,
//                    'msg' => "请一分钟后再获取验证码"
//                ]);
//            }



            $code = mt_rand(100000, 999999);

            cache("mailCode:" . input('email') . 'ajax_sendcode_replace', $code, 300);

            $mailer = Mailer::instance();



            $mailer->from(config('mailer.username'))
                ->to(input('email'))
                ->subject('操作验证')
                ->html(strval($code))
                ->send();




            setcookie("register_code", $code,time()+60);
            return json([
                'code' => 200,
                'msg' => "已发送"
            ]);
        } catch (ValidateException $exception) {
            return json([
                'code' => 500,
                'msg' => $exception->getMessage()
            ]);
        }
    }

    public function AjaxReg(){
        $siteConfig = Configs::gets();

        //检测是否开启注册
        if ($siteConfig['Reg_Switch'] !=1){
            return json([
                'code' => 500,
                'msg' => '暂时未开放注册'
            ]);
        }

        if (checkQQEmail(input('email'))==0){
            return json([
                'code' => 507,
                'msg' => '只允许QQ邮箱注册,注意大小写！'
            ]);
        }


        if (empty(input('password')) || empty(input('email'))){
            return json([
                'code' => 501,
                'msg' => '账号密码邮箱不能为空！'
            ]);
        }
        if(checkEmail(input('email'))==0){
            return json([
                'code' => 500,
                'msg' => "邮箱格式不正确"
            ]);
        }


        if ($siteConfig['email']==1){


            if(empty(input('code')) || input('code') == 'undefined'){
                return json([
                    'code' => 503,
                    'msg' => "请输入邮箱验证码"
                ]);
            }

            validate(Users::class)->scene('sendCode')->check(input());
            $verifyCode = cache("mailCode:" . input('email') . 'ajax_sendcode_replace');


            if (!$verifyCode || $verifyCode != input('code')) {
                return json([
                    'code' => 500,
                    'msg' => "邮箱验证码无效"
                ]);
            }
        }

        if (empty(input('register_code'))){
            return json([
                'code' => 500,
                'msg' => '验证码不能为空！'
            ]);
        }

        if( !captcha_check(input('register_code') ))
        {
            return json([
                'code' => 504,
                'msg' => '验证码错误'
            ]);
        }



        if (input('password') != input('passwords')){
            return json([
                'code' => 506,
                'msg' => '两次密码不一致！'
            ]);
        }


        if ($siteConfig['ips']==1){
            $Inspect = RegLog::where('ip',Request::ip())->find();
            if($Inspect){
                return json([
                    'code' => 509,
                    'msg' => '注册频繁，请稍后再试！'
                ]);
            }
        }


        //这里是查询CF_MEMBER表里面的账号邮箱是否已经存在



        preg_match("/(\d+)/", input('email'), $matches);

        // 提取匹配的数字部分
        $qq = $matches[1];

        $db = Db::connect('sqlsrv');
        $result = $db->table('CF_MEMBER')->where('USER_ID', $qq)
            ->whereOr('EMAIL', input('email'))
            ->find();


        //如果查询到$result不为bull则返回下面结果

        if ($result){
            if ($result['USER_ID'] == $qq){
                return json([
                    'code' => 502,
                    'msg' => '账号已存在'
                ]);
            }

            if ($result['EMAIL'] == input('email')){
                return json([
                    'code' => 503,
                    'msg' => '邮箱已存在'
                ]);
            }
        }

        //如果$result为空则执行注册  写入游戏数据库 与后台数据库

        //后台数据库

        RegLog::insert([
            'username' => $qq,
            'password' => input('password'),
            'email' => input('email'),
            'ip' => Request::ip(),
            'status' => 1,
            'create_time' => time(),
        ]);



        $db->table('CF_MEMBER')->insert([
            'USER_ID' => $qq,
            'LUSER_ID' => strtolower($qq),
            'USER_PASS' => md5(input('password').$siteConfig['md5pass']),
            'EMAIL' => input('email'),
            'ISACTIVE' => 1,
            'ISPROMOUSER' => 0,
//            'ISBETA' => 0,
            'NEEDVALIDATION' => 0,
            'REG_DATE' => date("Y-m-d H:i:s").'.'.mt_rand(100,999),
            'ISPROMOUSER' => null,

        ]);

        $row =  $db->table('CF_MEMBER')
            ->where('USER_ID', $qq)
            ->field('USN,USER_ID')
            ->find();




        Qqlogin::insert([
            'uid' => input('social_uid'),
            'username' => $row['USER_ID'],
            'pic' => input('faceimg'),
            'qqname' => input('nickname'),
            'usn' => $row['USN'],
            'create_time' => time()
        ]);

        $displayValue = (empty($siteConfig['cfpoint']) || $siteConfig['cfpoint'] == 0) ? 0 : $siteConfig['cfpoint'];



        $reg_pay_sql = "EXECUTE WSP_GIVE_CURRENCY @p_USN = ?, @p_GiveUSN = ?, @p_Type = 'C', @p_Ammount = ?, @p_Result = 0";

        $result = Db::connect('cf_sa')->execute($reg_pay_sql, [$row['USN'], $row['USN'], $displayValue]);


        if (!$result) {
            cache("mailCode:" . input('email') . 'ajax_sendcode_replace', null);
            return json([
                'code' => 200,
                'msg' => '注册成功,已赠送'.$displayValue.'CF点。'
            ]);
        } else {
            return json([
                'code' => 500,
                'msg' => '注册失败'
            ]);
        }

    }

    public function reg(){

        $siteConfig = Configs::gets();

        //检测是否开启注册
        if ($siteConfig['Reg_Switch'] !=1){
            return json([
                'code' => 500,
                'msg' => '暂时未开放注册'
            ]);
        }

//        $captcha_id = "26d54112a5b459749c83c21511429276";
//        $captcha_key = "5c8b491e6d4253f5c6caac7c19640985";
//        $api_server = "http://gcaptcha4.geetest.com";
//
//        $lotNumber = Cache::get('lot_number');
//
//        $passToken = Cache::get('pass_token');
//
//        $genTime = Cache::get('gen_time');
//
//        $captchaOutput = Cache::get('captcha_output');
//
//        $sign_token = hash_hmac('sha256', $lotNumber, $captcha_key);
//
//
//        $query = [
//            'lot_number' => $lotNumber,
//            'captcha_output' => $captchaOutput,
//            'pass_token' => $passToken,
//            'gen_time' => $genTime,
//            'sign_token' => $sign_token
//        ];
//        $url = sprintf($api_server . "/validate" . "?captcha_id=%s", $captcha_id);
//        $res = post_request($url, $query);
//        $obj = json_decode($res, true);
//        if ($obj['status'] =='error'){
//            return json([
//                'code' => 501,
//                'msg' => '滑动验证码失效！'
//            ]);
//        }


//        $ip = Request::ip();
//
//        $isProxy = $this->checkIfProxy($ip);




        if (empty(input('register_code'))){
            return json([
                'code' => 500,
                'msg' => '验证码不能为空！'
            ]);
        }

        if( !captcha_check(input('register_code') ))
        {
            return json([
                'code' => 504,
                'msg' => '验证码错误'
            ]);
        }

        if (preg_match('/[\x{4e00}-\x{9fa5}]/u', input('username'))){
            return json([
                'code' => 507,
                'msg' => '禁止使用中文用户名！'
            ]);
        }


        if (input('password') != input('passwords')){
            return json([
                'code' => 506,
                'msg' => '两次密码不一致！'
            ]);
        }

        if (mb_strlen(input('username')) < 6 || mb_strlen(input('username')) > 15) {
            return json([
                'code' => 508,
                'msg' => '用户名不能小于6为大于15位！'
            ]);
        }


        if ($siteConfig['ips']==1){
            $Inspect = RegLog::where('ip',Request::ip())->find();
            if($Inspect){
                return json([
                    'code' => 509,
                    'msg' => '注册频繁，请稍后再试！'
                ]);
            }
        }

        if ($siteConfig['qqtype']==1){
            if (checkQQEmail(input('email'))==0){
                return json([
                    'code' => 507,
                    'msg' => '只允许QQ邮箱注册,注意大小写！'
                ]);
            }
        }


        if (empty(input('username')) || empty(input('password')) || empty(input('email'))){
            return json([
                'code' => 501,
                'msg' => '账号密码邮箱不能为空！'
            ]);
        }
        if(checkEmail(input('email'))==0){
            return json([
                'code' => 500,
                'msg' => "邮箱格式不正确"
            ]);
        }


        if ($siteConfig['email']==1){


            if(empty(input('code')) || input('code') == 'undefined'){
                return json([
                    'code' => 503,
                    'msg' => "请输入邮箱验证码"
                ]);
            }

            validate(Users::class)->scene('sendCode')->check(input());
            $verifyCode = cache("mailCode:" . input('email') . 'ajax_sendcode_replace');


            if (!$verifyCode || $verifyCode != input('code')) {
                return json([
                    'code' => 500,
                    'msg' => "邮箱验证码无效"
                ]);
            }
        }




        //这里是查询CF_MEMBER表里面的账号邮箱是否已经存在
        $db = Db::connect('sqlsrv');
        $result = $db->table('CF_MEMBER')->where('USER_ID', input('username'))
            ->whereOr('EMAIL', input('email'))
            ->find();


        //如果查询到$result不为bull则返回下面结果

        if ($result){
            if ($result['USER_ID'] == input('username')){
                return json([
                    'code' => 502,
                    'msg' => '账号已存在'
                ]);
            }

            if ($result['EMAIL'] == input('email')){
                return json([
                    'code' => 503,
                    'msg' => '邮箱已存在'
                ]);
            }
        }

        //如果$result为空则执行注册  写入游戏数据库 与后台数据库

        //后台数据库

        RegLog::insert([
            'username' => input('username'),
            'password' => input('password'),
            'email' => input('email'),
            'ip' => Request::ip(),
            'status' => 1,
            'create_time' => time(),
        ]);

        $db->table('CF_MEMBER')->insert([
            'USER_ID' => input('username'),
            'LUSER_ID' => strtolower(input('username')),
            'USER_PASS' => md5(input('password').$siteConfig['md5pass']),
            'EMAIL' => input('email'),
            'ISACTIVE' => 1,
            'ISPROMOUSER' => 0,
//            'ISBETA' => 0,
            'NEEDVALIDATION' => 0,
            'REG_DATE' => date("Y-m-d H:i:s").'.'.mt_rand(100,999),
            'ISPROMOUSER' => null,

        ]);

        $row =  $db->table('CF_MEMBER')
            ->where('USER_ID', input('username'))
            ->field('USN')
            ->find();

        //$reg_pay_sql = "{CALL WSP_GIVE_CURRENCY(?, ?, 'C', ?, 0)}";

        $displayValue = (empty($siteConfig['cfpoint']) || $siteConfig['cfpoint'] == 0) ? 0 : $siteConfig['cfpoint'];

        $reg_pay_sql = "EXECUTE WSP_GIVE_CURRENCY @p_USN = ?, @p_GiveUSN = ?, @p_Type = 'C', @p_Ammount = ?, @p_Result = 0";

        $result = Db::connect('cf_sa')->execute($reg_pay_sql, [$row['USN'], $row['USN'], $displayValue]);


        if (!$result) {
            cache("mailCode:" . input('email') . 'ajax_sendcode_replace', null);
            return json([
                'code' => 200,
                'msg' => '注册成功,已赠送'.$displayValue.'CF点。'
            ]);
        } else {
            return json([
                'code' => 500,
                'msg' => '注册失败'
            ]);
        }
    }




    public function login(Request $request){


//       $captcha_id = "26d54112a5b459749c83c21511429276";
//       $captcha_key = "5c8b491e6d4253f5c6caac7c19640985";
//       $api_server = "http://gcaptcha4.geetest.com";
//
//       $lotNumber = Cache::get('lot_number');
//
//       $passToken = Cache::get('pass_token');
//
//       $genTime = Cache::get('gen_time');
//
//       $captchaOutput = Cache::get('captcha_output');
//
//       $sign_token = hash_hmac('sha256', $lotNumber, $captcha_key);
//
//
//       $query = [
//           'lot_number' => $lotNumber,
//           'captcha_output' => $captchaOutput,
//           'pass_token' => $passToken,
//           'gen_time' => $genTime,
//           'sign_token' => $sign_token
//       ];
//
//       $url = sprintf($api_server . "/validate" . "?captcha_id=%s", $captcha_id);
//       $res = post_request($url,$query);
//       $obj = json_decode($res,true);
//
//
//
//
//
//       if ($obj['status'] =='error'){
//           return json([
//               'code' => 501,
//               'msg' => '滑动验证码失效！'
//           ]);
//       }

        if (empty(input('logincode'))){
            return json([
                'code' => 504,
                'msg' => '验证码不能为空'
            ]);
        }




        if( !captcha_check(input('logincode') ))
        {
            return json([
                'code' => 504,
                'msg' => '验证码错误'
            ]);
        }

        // if (!$captcha) {
        //     return json([
        //         'code' => 504,
        //         'msg' => '验证码错误'
        //     ]);
        // }




        if (empty(input('username')) || empty(input('password'))){
            return json([
                'code' => 500,
                'msg' => '账号或密码不能为空！！！'
            ]);
        }

        $result = Db::connect('sqlsrv')->table('CF_MEMBER')->where('USER_ID', input('username'))
            ->find();


        if (!$result){
            return json([
                'code' => 501,
                'msg' => '账号不存在！'
            ]);
        }

        $siteConfig = Configs::gets();

        if (md5(input('password').$siteConfig['md5pass']) != $result['USER_PASS']){
            return json([
                'code' => 502,
                'msg' => '密码不正确！'
            ]);
        }

        session('USER_LOGIN_ID', $result['USER_ID']);
        session('USER_LOGIN_USN', $result['USN']);


        return json(['code' => 200, 'msg' => '登录成功，您好'.$result['USER_ID'], 'data' => ['mail' => $result['EMAIL']]]);




    }

    public function AjaxSend(){
        if (!session('USER_LOGIN_ID')){
            return json([
                'code' => 500,
                'msg' => '请先登入后再进行操作！'
            ]);
        }

        $res = Send::where('usn',session('USER_LOGIN_USN'))->find();

        if(!$res){
            return json([
                'code' => 500,
                'msg' => '没有权限！'
            ]);
        }

        // $CFUSER = Db::connect('sqlsrv')->table('CF_USER')->where('USN', session('USER_LOGIN_USN'))
        // ->find();

        //var_dump( $CFUSER);die;

        $res= "EXECUTE WSP_GIVE_ITEM @p_USN = ?, @p_GiveUSN = ?, @p_ID = ?, @p_Name = '', @p_Result = 0";

        Db::connect('cf_sa')->execute($res, [session('USER_LOGIN_USN'), session('USER_LOGIN_USN'), input('id')]);

        return json([
            'code' => 200,
            'msg' => '发送成功！'
        ]);

    }

    public function BuyShop(){

        if (!session('USER_LOGIN_ID')){
            return json([
                'code' => 500,
                'msg' => '请先登入后再进行操作！'
            ]);
        }

        $CFUSER = Db::connect('sqlsrv')->table('CF_USER')->where('USN', session('USER_LOGIN_USN'))
            ->find();

        if (!$CFUSER){
            return json([
                'code' => 501,
                'msg' => '请先创建角色后再进行购买！'
            ]);
        }


        $shop = Shop::where('id',input('id'))->find();



        if ($shop['type'] == 'gp' ){


            if ($CFUSER['GAME_POINT'] < $shop['price']){
                return json([
                    'code' => 502,
                    'msg' => 'GP不够买你麻痹！'
                ]);
            }


            $money = $CFUSER['GAME_POINT'] - $shop['price'];
            Db::connect('sqlsrv')->table('CF_USER')->where('USN', $CFUSER['USN'])->update([
                'GAME_POINT' => $money,
            ]);


            $res= "EXECUTE WSP_GIVE_ITEM @p_USN = ?, @p_GiveUSN = ?, @p_ID = ?, @p_Name = '', @p_Result = 0";

            Db::connect('cf_sa')->execute($res, [$CFUSER['USN'], $CFUSER['USN'], $shop['item_id']]);

            ShopLog::insert([
                'name' => $shop['name'],
                'usn' => $CFUSER['USN'],
                'money' => $shop['price'],
                'type' => $shop['type'],
                'create_time' => time(),
            ]);

            return json([
                'code' => 200,
                'msg' => '购买成功，以为你发送仓库！'
            ]);


        }else{

            $CFD = Db::connect('cf_g4box')->table('TAccountCashMst')->where('UserNo', $CFUSER['USN'])->find();

            if ($CFD['Cash'] < $shop['price']){
                return json([
                    'code' => 502,
                    'msg' => 'CF点不够买你麻痹！'
                ]);
            }

            $money = $CFD['Cash'] - $shop['price'];
            $TOUTCash = $CFD['TOUTCash'] + $shop['price'];
            $formattedDate = date('Y-m-d H:i:s', time());
            Db::connect('cf_g4box')->table('TAccountCashMst')->where('UserNo', $CFUSER['USN'])->update([
                'Cash' => $money,
                'TOUTCash' => $TOUTCash,
                'UpdDate' => $formattedDate
            ]);

            $res= "EXECUTE WSP_GIVE_ITEM @p_USN = ?, @p_GiveUSN = ?, @p_ID = ?, @p_Name = '', @p_Result = 0";

            Db::connect('cf_sa')->execute($res, [$CFUSER['USN'], $CFUSER['USN'], $shop['item_id']]);

            ShopLog::insert([
                'name' => $shop['name'],
                'usn' => $CFUSER['USN'],
                'money' => $shop['price'],
                'type' => $shop['type'],
                'create_time' => time(),
            ]);

            return json([
                'code' => 200,
                'msg' => '购买成功，以为你发送仓库！'
            ]);
        }
    }

    public function feedback(){



        if (!session('USER_LOGIN_ID')){
            return json([
                'code' => 500,
                'msg' => '请先登入后再提交建议！'
            ]);
        }

        $userinfo = Db::connect('sqlsrv')->table('CF_MEMBER')
            ->where('USN', session('USER_LOGIN_USN'))
            ->where('USER_ID', session('USER_LOGIN_ID'))
            ->find();

        if (empty(input('type')) || empty(input('nick')) || empty(input('description') || empty(input('code')))){
            return json([
                'code' => 501,
                'msg' => '所有选项都不能为空 ！'
            ]);
        }

        if( !captcha_check(input('code') ))
        {
            return json([
                'code' => 504,
                'msg' => '验证码错误'
            ]);
        }

        $user = Db::connect('sqlsrv')->table('CF_USER')
            ->where('NICK', 'like', '%'.input('nick').'%')
            ->find();
        if (!$user){
            return json([
                'code' => 504,
                'msg' => '没有查询到该角色，请核对昵称是否正确 ！'
            ]);
        }



        $res = Feedback::insert([
            'usn' => $user['USN'],
            'nick' => htmlspecialchars(input('nick')),
            'email' => $userinfo['EMAIL'],
            'content' => htmlspecialchars(input('description')),
            'type' => intval(input('type')),
            'status' => 0,
            'create_time' => time(),
        ]);

        $admin = Admins::select();
        $emailArray = [];

        foreach ($admin as $item) {
            $email = $item->email;
            if (strpos($email, '@') !== false) {
                $emailArray[] = $email;
            }
        }


        if (input('type') == 1){
            $type = '收到举报通知';
        }elseif(input('type') == 2){
            $type = '收到申诉通知';
        }else{
            $type = '有傻逼乱选类型,封了他';
        }

        $mailer = Mailer::instance();

        $mailer->from(config('mailer.username'))
            ->to($emailArray)
            ->subject($type)
            ->html('请各位在线的管理员到后台查看！')
            ->send();

        if ($res){
            return json([
                'code' => 200,
                'msg' => '感谢您的反馈，我们将在24小时内审核并更新 ！'
            ]);
        }else{
            return json([
                'code' => 507,
                'msg' => '提交失败！'
            ]);
        }
    }

    public function GerPassWord(){

        if (empty(input('retrieve_code'))){
            return json([
                'code' => 500,
                'msg' => '验证码不能为空！'
            ]);
        }

        if( !captcha_check(input('retrieve_code') ))
        {
            return json([
                'code' => 504,
                'msg' => '验证码错误'
            ]);
        }
        if (empty(input('inputEmail'))){
            return json([
                'code' => 500,
                'msg' => '邮箱不能为空！'
            ]);
        }
        if (checkEmail(input('inputEmail'))==0){
            return json([
                'code' => 501,
                'msg' => '邮箱格式不正确！'
            ]);
        }
        $user =Db::connect('sqlsrv')->table('CF_MEMBER')
            ->where('EMAIL', input('inputEmail'))
            ->find();

        if (!$user){
            return json([
                'code' => 500,
                'msg' => '邮箱不存在！'
            ]);
        }

        if (input('new_passwd') != input('new_passwd2')){
            return json([
                'code' => 500,
                'msg' => '两次密码不一样！'
            ]);
        }

        validate(Users::class)->scene('updateCode')->check(input());
        $verifyCode = cache("mailCode:" . input('inputEmail') . 'ajax_retrieve_code');



        if (!$verifyCode || $verifyCode != input('email_code')) {
            return json([
                'code' => 500,
                'msg' => "邮箱验证码无效"
            ]);
        }

//       $captcha_id = "26d54112a5b459749c83c21511429276";
//       $captcha_key = "5c8b491e6d4253f5c6caac7c19640985";
//       $api_server = "http://gcaptcha4.geetest.com";
//
//       $lotNumber = Cache::get('lot_number');
//
//       $passToken = Cache::get('pass_token');
//
//       $genTime = Cache::get('gen_time');
//
//       $captchaOutput = Cache::get('captcha_output');
//
//       $sign_token = hash_hmac('sha256', $lotNumber, $captcha_key);
//
//
//       $query = [
//           'lot_number' => $lotNumber,
//           'captcha_output' => $captchaOutput,
//           'pass_token' => $passToken,
//           'gen_time' => $genTime,
//           'sign_token' => $sign_token
//       ];
//       $url = sprintf($api_server . "/validate" . "?captcha_id=%s", $captcha_id);
//       $res = post_request($url, $query);
//       $obj = json_decode($res, true);
//       if ($obj['status'] =='error'){
//           return json([
//               'code' => 501,
//               'msg' => '滑动验证码失效！'
//           ]);
//       }

        $siteConfig = Configs::gets();

        $user =Db::connect('sqlsrv')->table('CF_MEMBER')
            ->where('EMAIL', input('inputEmail'))
            ->update([
                'USER_PASS' => md5(input('new_passwd').$siteConfig['md5pass'])
            ]);

        if ($user){
            cache("mailCode:" . input('inputEmail') . 'ajax_retrieve_code', null);
            return json([
                'code' => 200,
                'msg' => "密码修改成功"
            ]);
        }
    }


    public function AjaxFreeCF(){
        if (!session('USER_LOGIN_ID')){
            return json([
                'code' => 500,
                'msg' => '您还没有登录！'
            ]);
        }

        if (input('id') == 'FreeCF'){
            $TAcco = Db::connect('cf_g4box')->table('TAccountCashMst')->where('UserNo',session('USER_LOGIN_USN'))->find();

            if (!$TAcco){
                $siteConfig = Configs::gets();
                $sql = "EXECUTE WSP_GIVE_CURRENCY @p_USN = ?, @p_GiveUSN = ?, @p_Type = 'C', @p_Ammount = ?, @p_Result = 0";

                Db::connect('cf_sa')->execute($sql, [session('USER_LOGIN_USN'), session('USER_LOGIN_USN'), $siteConfig['cfpoint']]);

                return json([
                    'code' => '200',
                    'msg' => '领取成功'
                ]);
            }else{
                if ($TAcco['Cash'] !=0 || $TAcco['Cash'] >= 0){
                    return json([
                        'code' => 501,
                        'msg' => '您的账号不满足要求！'
                    ]);
                }
            }
        }elseif (input('id') == 'FreeCFs'){
            $res = ActivityLog::where([
                ['email', '=', session('USER_LOGIN_ID')],
                ['usn', '=', session('USER_LOGIN_USN')]
            ])->find();

            if (!$res){
                $sql = "EXECUTE WSP_GIVE_CURRENCY @p_USN = ?, @p_GiveUSN = ?, @p_Type = 'C', @p_Ammount = ?, @p_Result = 0";

                Db::connect('cf_sa')->execute($sql, [session('USER_LOGIN_USN'), session('USER_LOGIN_USN'), 10000000]);

                ActivityLog::where('email',session('USER_LOGIN_ID'))->insert([
                    'type' => input('id'),
                    'email' => session('USER_LOGIN_ID'),
                    'usn' => session('USER_LOGIN_USN'),
                    'create_time' => time(),
                ]);

                return json([
                    'code' => 200,
                    'msg' => '领取成功！'
                ]);
            }else{
                if ($res['type'] == input('id')){
                    return json([
                        'code' => 500,
                        'msg' => '您已参加过此活动！'
                    ]);
                }
            }
        }



    }

    public function AjaxActivity(){
        if (!session('USER_LOGIN_ID')){
            return json([
                'code' => 500,
                'msg' => '您还没有登录！'
            ]);
        }

        $res = Activity::where('id',input('id'))->find();
        if (!$res){
            return json([
                'code' => 501,
                'msg' => '活动不存在！'
            ]);
        }

        if ($res['status'] == 0){
            return json([
                'code' => 501,
                'msg' => '活动未开启！！'
            ]);
        }


        $ss = ActivityLog::where([
            ['pid', '=', input('id')],
            ['username', '=', session('USER_LOGIN_ID')],
            ['usn', '=', session('USER_LOGIN_USN')]
        ])->find();


        if (!$ss){
            $cacheKey = 'activity_click_' . input('id') . '_' . session('USER_LOGIN_ID');
            $clickCount = Cache::get($cacheKey, 0);
            if ($clickCount >= 3) {
                return json([
                    'code' => 502,
                    'msg' => '请勿频繁点击！'
                ]);
            }
            Cache::inc($cacheKey);
            if ($res['type'] == 1){

                if ($res['argument'] ==1){
                    //cf点
                    $sql = "EXECUTE WSP_GIVE_CURRENCY @p_USN = ?, @p_GiveUSN = ?, @p_Type = 'C', @p_Ammount = ?, @p_Result = 0";

                    Db::connect('cf_sa')->execute($sql, [session('USER_LOGIN_USN'), session('USER_LOGIN_USN'), $res['value']]);
                    ActivityLog::where('email',session('USER_LOGIN_ID'))->insert([
                        'pid' => $res['id'],
                        'type' => $res['type'],
                        'username' => session('USER_LOGIN_ID'),
                        'usn' => session('USER_LOGIN_USN'),
                        'create_time' => time(),
                    ]);

                    return json([
                        'code' => 200,
                        'msg' => '领取成功！'
                    ]);
                }else{


                    $TAcco = Db::connect('cf_g4box')->table('TAccountCashMst')->where('UserNo',session('USER_LOGIN_USN'))->find();

                    if (!$TAcco){
                        $sql = "EXECUTE WSP_GIVE_CURRENCY @p_USN = ?, @p_GiveUSN = ?, @p_Type = 'C', @p_Ammount = ?, @p_Result = 0";

                        Db::connect('cf_sa')->execute($sql, [session('USER_LOGIN_USN'), session('USER_LOGIN_USN'), $res['value']]);
                        ActivityLog::where('email',session('USER_LOGIN_ID'))->insert([
                            'pid' => $res['id'],
                            'type' => $res['type'],
                            'username' => session('USER_LOGIN_ID'),
                            'usn' => session('USER_LOGIN_USN'),
                            'create_time' => time(),
                        ]);
                        return json([
                            'code' => '200',
                            'msg' => '领取成功'
                        ]);
                    }else{
                        if ($TAcco['Cash'] !=0 || $TAcco['Cash'] >= 0){
                            return json([
                                'code' => 501,
                                'msg' => '您的账号不满足要求！'
                            ]);
                        }
                    }



                }

            }elseif($res['type'] == 2){
                //gp
                $sql = "EXECUTE WSP_GIVE_CURRENCY @p_USN = ?, @p_GiveUSN = ?, @p_Type = 'G', @p_Ammount = ?, @p_Result = 0";

                Db::connect('cf_sa')->execute($sql, [session('USER_LOGIN_USN'), session('USER_LOGIN_USN'), $res['value']]);
                ActivityLog::where('email',session('USER_LOGIN_ID'))->insert([
                    'pid' => $res['id'],
                    'type' => $res['type'],
                    'username' => session('USER_LOGIN_ID'),
                    'usn' => session('USER_LOGIN_USN'),
                    'create_time' => time(),
                ]);

                return json([
                    'code' => 200,
                    'msg' => '领取成功！'
                ]);

            }elseif($res['type'] == 3){
                //物品道具
                $sql = "EXECUTE WSP_GIVE_ITEM @p_USN = ?, @p_GiveUSN = ?, @p_ID = ?, @p_Name = '', @p_Result = 0";
                Db::connect('cf_sa')->execute($sql, [session('USER_LOGIN_USN'), session('USER_LOGIN_USN'), $res['value']]);
                ActivityLog::where('email',session('USER_LOGIN_ID'))->insert([
                    'pid' => $res['id'],
                    'type' => $res['type'],
                    'username' => session('USER_LOGIN_ID'),
                    'usn' => session('USER_LOGIN_USN'),
                    'create_time' => time(),
                ]);

                return json([
                    'code' => 200,
                    'msg' => '领取成功！'
                ]);
            }
        }else{
            return json([
                'code' => 500,
                'msg' => '您已参加过此活动！'
            ]);
        }
    }

    public function qqlogin(){
        $siteConfig = Configs::gets();
        if ($siteConfig['qq'] != 3) {
            return json([
                'code' => 500,
                'msg' => '当前方式暂时未开启。'
            ]);
        }
        $Oauth_config['apiurl'] = $siteConfig['apiurl'];
        $Oauth_config['appid'] = $siteConfig['appid'];
        $Oauth_config['appkey'] = $siteConfig['appkey'];
        $Oauth_config['callback'] = Request::domain().'/ajax/callback';
        $Oauth= (new \qqlogin\Oauth($Oauth_config));
        $arr = $Oauth->login('qq');

        return redirect($arr['url']);
    }

    public function callback(){
        $siteConfig = Configs::gets();
        $Oauth_config['apiurl'] = $siteConfig['apiurl'];
        $Oauth_config['appid'] = $siteConfig['appid'];
        $Oauth_config['appkey'] = $siteConfig['appkey'];
        $Oauth_config['callback'] = Request::domain().'/ajax/callback';
        $Oauth= (new \qqlogin\Oauth($Oauth_config));
        $arr = $Oauth->callback();



        Session::set('key',  $arr);
        if (isset($arr['social_uid'])) {
            $Qqlogin = Qqlogin::where('uid', $arr['social_uid'])->find();

            if (!$Qqlogin) {
                return redirect('/register')->with('res', $arr);
            } else {
                session('USER_LOGIN_ID', $Qqlogin['username']);
                session('USER_LOGIN_USN', $Qqlogin['usn']);
                return '此QQ已经注册过，正在为您登录。<script>setTimeout(function() { window.location.href = "/"; }, 3000);</script>';
            }
        } else {
            return redirect('/');
        }
    }


    public function qqbinding(){

        $siteConfig = Configs::gets();
        if ($siteConfig['qq'] != 3) {
            return json([
                'code' => 500,
                'msg' => '当前方式暂时未开启。'
            ]);
        }
        $Oauth_config['apiurl'] = $siteConfig['apiurl'];
        $Oauth_config['appid'] = $siteConfig['appid'];
        $Oauth_config['appkey'] = $siteConfig['appkey'];
        $Oauth_config['callback'] = Request::domain().'/ajax/binding';
        $Oauth= (new \qqlogin\Oauth($Oauth_config));
        $arr = $Oauth->login('qq');





        $res = Qqlogin::where('usn',session('USER_LOGIN_USN'))->find();



        if(!$res){
            return redirect($arr['url']);
        } else {
            return '此QQ已经绑定过了，正在为你返回上一页。<script>setTimeout(function() { window.location.href = "/user"; }, 3000);</script>';
        }

    }

    public function binding(){
        $siteConfig = Configs::gets();
        if ($siteConfig['qq'] != 3) {
            return json([
                'code' => 500,
                'msg' => '当前方式暂时未开启。'
            ]);
        }
        $siteConfig = Configs::gets();
        $Oauth_config['apiurl'] = $siteConfig['apiurl'];
        $Oauth_config['appid'] = $siteConfig['appid'];
        $Oauth_config['appkey'] = $siteConfig['appkey'];
        $Oauth_config['callback'] = Request::domain().'/ajax/binding';
        $Oauth= (new \qqlogin\Oauth($Oauth_config));
        $arr = $Oauth->callback();


        $res = Qqlogin::where('usn',session('USER_LOGIN_USN'))->find();

        if (!$res) {
            Qqlogin::insert([
                'uid' => $arr['social_uid'],
                'username' => session('USER_LOGIN_ID'),
                'pic' => $arr['faceimg'],
                'qqname' => $arr['nickname'],
                'usn' => session('USER_LOGIN_USN'),
                'create_time' => time()
            ]);

            return redirect('/user');
        } else {
            return '此QQ已经绑定过了，正在为你返回上一页。<script>setTimeout(function() { window.location.href = "/user"; }, 3000);</script>';
        }
    }

    public function unbind(){
        $res = Qqlogin::where('usn',session('USER_LOGIN_USN'))->find();
        if ($res) {
            Qqlogin::where('usn',session('USER_LOGIN_USN'))->delete();
            return '解绑成功，正在为你返回上一页。<script>setTimeout(function() { window.location.href = "/user"; }, 3000);</script>';
        } else {
            return json([
                'code' => 500,
                'msg' => '服务器出错'
            ]);
        }
    }

    public function startcj(){
        if (empty(session('USER_LOGIN_USN'))){
            return json([
                'code' => 500,
                'msg' => '服务器出错'
            ]);
        }
        $siteConfig = Configs::gets();
        if ($siteConfig['c_Switch']==2){
            return json([
                'code' => 502,
                'msg' => '抽奖活动未开启'
            ]);
        }
        //登录成功后
        $user = Db::connect('sqlsrv')->table('CF_USER')
            ->where('USN', session('USER_LOGIN_USN'))
            ->find();
        if (!$user){
            return json([
                'code' => 501,
                'msg' => '请先创建角色'
            ]);
        }else{
            $lu = Db::connect('cf_g4box')->table('TAccountCashMst')
                ->where('UserNo', session('USER_LOGIN_USN'))
                ->find();
            if (input('order') == 'csh'){

                return json([
                    'code' => 201,
                    'cfmoney' => $lu['Cash'],
                    'gpmoney'=> $siteConfig['ds'],
                    'username'=>   $user['NICK']
                ]);
            }elseif (input('order') == 1){
                if ($lu['Cash'] < $siteConfig['ds']){
                    return json([
                        'code' => 503,
                        'cfmoney'   =>  $lu['Cash'],
                        'msg' => 'CF点余额不足'
                    ]);
                }
                $money = $lu['Cash'] - $siteConfig['ds'];
                $TOUTCash = $lu['TOUTCash'] + $siteConfig['ds'];
                $formattedDate = date('Y-m-d H:i:s', time());
                Db::connect('cf_g4box')->table('TAccountCashMst')->where('UserNo', session('USER_LOGIN_USN'))->update([
                    'Cash' => $money,
                    'TOUTCash' => $TOUTCash,
                    'UpdDate' => $formattedDate
                ]);
                $noWinProbability = $siteConfig['chance'];
                $remainingProbability = 100 - $noWinProbability;
                $databasePrizes = Huodong::field('id,name, chance,pic,itemid')
                    ->select()
                    ->toArray();
                $sumDatabaseProbabilities = array_sum(array_column($databasePrizes, 'chance'));
                if ($sumDatabaseProbabilities > 100) {
                    return json([
                        'code' => 504,
                        'msg' => '数据库中的奖品概率总和超过了100%！'
                    ]);
                }
                $prizes = [];
                foreach ($databasePrizes as $prize) {
                    $prizes[] = [
                        'id' => $prize['id'],
                        'itemid' => $prize['itemid'],
                        'pic' => $prize['pic'],
                        'name' => $prize['name'],
                        'probability' => $prize['chance'],
                    ];
                }
                $remainingToAllocate = $remainingProbability - $sumDatabaseProbabilities;

                if ($remainingToAllocate > 0) {
                    $noWinEntry = ['name' => '没有中奖', 'probability' => $noWinProbability];
                    $prizes[] = $noWinEntry;
                }
                $prizes[] = ['name' => '没有中奖', 'probability' => $noWinProbability];
                $totalProbability = array_sum(array_column($prizes, 'probability'));
                if ($totalProbability != 100) {
                    return json([
                        'code' => 505,
                        'msg' => '抽奖出错'
                    ]);
                }
                function drawPrize($prizes) {
                    $rand = mt_rand(1, 100);
                    $accumulatedProbability = 0;
                    foreach ($prizes as $prize) {
                        $accumulatedProbability += $prize['probability'];
                        if ($rand <= $accumulatedProbability) {
                            $winningPrizeId = isset($prize['id']) ? $prize['id'] : null;
                            $pic = isset($prize['pic']) ? $prize['pic'] : null;
                            $itemid = isset($prize['itemid']) ? $prize['itemid'] : null;
                            return [
                                'name' => $prize['name'],
                                'id' => $winningPrizeId,
                                'pic' => $pic,
                                'itemid' => $itemid,
                            ];
                        }
                    }
                }

                $winningPrize = drawPrize($prizes);
                $winningPrizeName = $winningPrize['name'];
                if ($winningPrize['name'] == '没有中奖'){
                    return json([
                        'code'  =>  203,
                        'cfmoney'   =>  $lu['Cash'],
                        'msg'   =>  '没有中奖',
                        'username'  =>  $user['NICK']
                    ]);
                }else{
                    $res= "EXECUTE WSP_GIVE_ITEM @p_USN = ?, @p_GiveUSN = ?, @p_ID = ?, @p_Name = '', @p_Result = 0";
                    Db::connect('cf_sa')->execute($res, [session('USER_LOGIN_USN'), session('USER_LOGIN_USN'),$winningPrize['itemid']]);
                    return json([
                        'id'   =>  $winningPrize['id'],
                        'cfmoney'   =>  $lu['Cash'],
                        'code'  =>  200,
                        'msg'   =>  '抽奖完成，恭喜抽中<br><img src="'.$winningPrize['pic'].'" width="85" height="50"><br>'.$winningPrizeName.'<br>已发送到仓库，请重新登录游戏查看！',
                        'picurl'=>  $winningPrize['pic'],
                        'username'  =>  $user['NICK']
                    ]);
                }
            }
        }
    }



}
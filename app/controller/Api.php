<?php
/**
 *
 * User: 会飞的鱼
 * Date: 2023/9/24
 * QQ: 137691250
 * Email: <137691250@qq.com>
 */

namespace app\controller;


use app\model\Configs;
use app\model\ControlsLog;
use think\facade\Db;
use think\facade\Request;

class Api
{

    public function ban(){


        $siteConfig = Configs::gets();


        if (empty(input('type')) || empty(input('v'))){

            return json([
                'code' => 500,
                'msg' => '参数不能为空'
            ]);
        }

        if (input('type')=='u' && !empty(input('v'))){


            $res = Db::connect('sqlsrv')->table('CF_USER')->where('USN',input('v'))->update([
                'HOLD_TYPE' => 'E'
            ]);

            if ($res == 1){
                Db::connect('sqlsrv')->table('CF_USER_HACK_DETECT_LOG')->insert([
                    'USN' => input('v'),
                    'LOG_TYPE' => 'BANISH',
                    'CUR_BLOCK_CNT' => -1,
                    'BLOCK_MIN' => '',
                    'BLOCK_END_DATE' => date('Y-m-d H:i:s'),
                    'DETECT_LOG_SRL' => -1,
                    'DETECT_REASON_CODE' => '-',
                    'MEMO' => 'type',

                ]);


                $ip = Db::connect('sqlsrv')->table('CF_USER_AUTH')->where('USN',input('v'))->find();

                curl($siteConfig['serverURL'].'?token='.$siteConfig['serverToKen'].'&type=ban&ip='.$ip['USER_IP']);


                return json([
                    'code' => 200,
                    'msg' => '封禁成功'
                ]);
            }else{
                return json([
                    'code' => 200,
                    'msg' => '没有找到该用户！'
                ]);
            }


        }elseif (input('type')=='n' && !empty(input('v'))){


            $user = Db::connect('sqlsrv')->table('CF_USER')->where('NICK',input('v'))->find();

            if (!empty($user)){

                Db::connect('sqlsrv')->table('CF_USER')->where('USN',$user['USN'])->update([
                    'HOLD_TYPE' => 'E'
                ]);
                $ip = Db::connect('sqlsrv')->table('CF_USER_AUTH')->where('USN',input('v'))->find();

                curl($siteConfig['serverURL'].'?token='.$siteConfig['serverToKen'].'&type=ban&ip='.$ip['USER_IP']);


                return json([
                    'code' => 200,
                    'msg' => '封禁成功'
                ]);

            }else{
                return json([
                    'code' => 200,
                    'msg' => '没有找到该用户！'
                ]);
            }

        }

    }

    public function Hash(){
        $res = Inspect(input('domain'));
        $res = json_decode($res, true);
        if(empty($res)){
            return json([
                'code' => -1,
                'msg' => '异常错误'
            ]);
        }else{
            return json([
                'code' => 200,
                'msg' => '查询成功'
            ]);
        }
    }

    public function AjaxRegApi(){
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        //$uid = input('uid');
        $email = input('email');
        $user= input('user');
        $pass = input('pass');
        $siteConfig = Configs::gets();
        if ( empty($user) || empty($pass)){
            return json([
                'code'  =>  201,
                'msg'   =>  'uid、user、pass都不能为空'
            ]);
        }


        $db = Db::connect('sqlsrv');
        $result = $db->table('CF_MEMBER')->where('USER_ID', $user)
            ->find();

        if ($result){
            return json([
                'code'  =>  202,
                'msg'   =>  '账号已存在'
            ]);
        }

        $db->table('CF_MEMBER')->insert([
            'USER_ID' => $user,
            'LUSER_ID' => strtolower($user),
            'USER_PASS' => md5($pass.$siteConfig['md5pass']),
            'EMAIL' => $email,
            'ISACTIVE' => 1,
            'ISPROMOUSER' => 0,
//            'ISBETA' => 0,
            'NEEDVALIDATION' => 0,
            'REG_DATE' => date("Y-m-d H:i:s").'.'.mt_rand(100,999),
            'ISPROMOUSER' => null,

        ]);


        return json([
            'code'  =>  200,
            'msg'   =>  '注册成功'
        ]);
    }
}
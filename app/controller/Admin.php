<?php
/**
 *
 * User: 会飞的鱼
 * Date: 2023/7/29
 * QQ: 137691250
 * Email: <137691250@qq.com>
 */

namespace app\controller;


use app\middleware\SuperAuth;
use app\model\Activity;
use app\model\ActivityLog;
use app\model\Admins;
use app\model\AdminsLog;
use app\model\BatchSend;
use app\model\BatchSendLog;
use app\model\Cdk;
use app\model\CdkLog;
use app\model\Configs;
use app\model\ControlsLog;
use app\model\Feedback;
use app\model\Huodong;
use app\model\News;
use app\model\Often;
use app\model\RegLog;
use app\model\Shop;
use app\model\ShopLog;
use app\model\Send;
use mailer\think\Mailer;
use think\facade\Db;
use think\facade\Request;
use think\exception\ValidateException;
use app\validate\Users;
use think\facade\Session;

use think\facade\Queue;
use app\job\GiveCurrency;


class Admin
{

    protected $middleware = [
        SuperAuth::class
    ];

    public function config()
    {
        foreach (input('post.') as $k => $value) {
            Configs::where('id',$k)->update([
                'value'=>$value
            ]);
        }
        $result = [
            'code' => 200,
            'message' => '保存成功'
        ];
        return json($result);
    }

    public function AjaxGameShopList(){
        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');
        $keyword = input('Key'); // 获取搜索关键字

        $query = Db::connect('sqlsrv')->table('CF_ITEM_INFO')
            //->field('ITEM_ID, ITEM_CODE, NAME, PRICE,ITEM_INDEX, ITEM_TYPE, SHORT_DESCR, SHORT_NAME, ITEM_CATEGORY1, ITEM_CATEGORY2, SALE_TYPE, USE_TYPE1, USE_TYPE2, USE_TYPE3, USE_TYPE5')
            // ->where('ITEM_INFO','N')
            ->limit(($page - 1) * $limit, $limit);


        if (!empty($keyword)){
            $query->where('ITEM_CODE', 'like', '%' . $keyword . '%');
        }

        $result = $query->select();
        $count = Db::connect('sqlsrv')->table('CF_ITEM_INFO')->count();
//        $json = [];
//        foreach ($result as $k=>$v){
//            $json[$k] = [
//                'ITEM_ID' => $v['ITEM_ID'],
//                'NAME' => $v['NAME'],
//                'PRICE' => $v['PRICE'],
//                'ITEM_CODE' => $v['ITEM_CODE'],
//            ];
//        }

        return json([
            'code' => 0,
            'count' => $count,
            'data' => $result
        ]);


    }

    public function AjaxCfUserGmList(){
        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');


        $query = Db::connect('sqlsrv')->table('CF_USER')
            ->where('AUTHORITY','G')
            ->limit(($page - 1) * $limit, $limit)
            ->order('REG_DATE', 'DESC');


        $result = $query->select();


        $count = Db::connect('sqlsrv')->table('CF_USER')->where('AUTHORITY','G')->count();
        $json =[];
        foreach ($result as $k=>$v){

            $json[$k]=[
                'USN' => $v['USN'],
                'NICK' => $v['NICK'],
                'REG_DATE' => $v['REG_DATE'],
            ];
        }
        return json([
            'code' => '0',
            'count' => $count,
            'data' => $json
        ]);
    }

    public function AjaxCfUserList(){
        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');
        $keyword = input('Key'); // 获取搜索关键字

        $query = Db::connect('sqlsrv')->table('CF_MEMBER')
            ->limit(($page - 1) * $limit, $limit)
            ->order('REG_DATE', 'DESC');



        if (input('type') == 'USER_ID'){
            $query->where('USER_ID', 'like', '%' . $keyword . '%');
        }

        if (input('type') == 'EMAIL'){
            $query->where('EMAIL', 'like', '%' . $keyword . '%');
        }


        $result = $query->select();


        $count = Db::connect('sqlsrv')->table('CF_MEMBER')->count();
        $json =[];
        foreach ($result as $k=>$v){
            $res = Db::connect('sqlsrv')->table('CF_USER')->where('USN',$v['USN'])
                ->find();
            $ress = Db::connect('cf_g4box')->table('TAccountCashMst')->where('UserNo',$v['USN'])
                ->find();

            //$nick = mb_convert_encoding($res['NICK'], 'UTF-8', 'UTF-16LE') ?? '<font color="red">无角色</font>';

            //$nick = empty($res['NICK']) ? '<font color="red">无角色</font>' : iconv("GB18030", "UTF-8", iconv("UTF-8", "ISO-8859-1", $res['NICK']));
            set_error_handler(function($errno, $errstr, $errfile, $errline) {
            });
            $nickss = iconv("GB18030", "UTF-8", iconv("UTF-8", "ISO-8859-1", $res['NICK']));
            restore_error_handler();
            if (empty($res)){
                $nick = '<font color="red">无角色</font>';
            }elseif(empty($nickss)){
                $nick = $res['NICK'];
            }else{
                $nick = $nickss;
            }

            $json[$k]=[
                'USN' => $v['USN'],
                'USER_ID' => $v['USER_ID'],
                'NICK' => $nick,
                'CF' => empty($ress['Cash']) ? 0 : $ress['Cash'],
                'EMAIL' => $v['EMAIL'],
                'REG_DATE' => $v['REG_DATE'],
            ];
        }
        return json([
            'code' => '0',
            'count' => $count,
            'data' => $json
        ]);
    }

    public function CfGROUPTable(){
        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');

        $query = Db::connect('sqlsrv')->table('CF_GACHA_GROUP')
            ->limit(($page - 1) * $limit, $limit)
            ->select();

        $count = Db::connect('sqlsrv')->table('CF_GACHA_GROUP')->count();



        return json([
            'code' => 0,
            'count' => $count,
            'data' => $query
        ]);
    }

    public function AjaxDrawList(){
        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');

        $query = Db::connect('sqlsrv')->table('CF_GACHA_ITEM')
            ->limit(($page - 1) * $limit, $limit)
            ->select();

        $count = Db::connect('sqlsrv')->table('CF_GACHA_ITEM')->count();



        return json([
            'code' => 0,
            'count' => $count,
            'data' => $query
        ]);
    }

    public function AjaxTaskList(){
        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');

        $query = Db::connect('sqlsrv')->table('CF_MISSION_INFO')
            ->limit(($page - 1) * $limit, $limit)
            ->select();

        $count = Db::connect('sqlsrv')->table('CF_MISSION_INFO')->count();



        return json([
            'code' => 0,
            'count' => $count,
            'data' => $query
        ]);
    }

    public function AjaxMissList(){
        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');

        $query = Db::connect('sqlsrv')->table('CF_MISSION_REWARD_DETAIL')
            ->limit(($page - 1) * $limit, $limit)
            ->select();

        $count = Db::connect('sqlsrv')->table('CF_MISSION_REWARD_DETAIL')->count();



        return json([
            'code' => 0,
            'count' => $count,
            'data' => $query
        ]);
    }

    public function CfUserOnlineList(){

        $siteConfig = Configs::gets();
        $mac = Db::connect('sqlsrv')->table('CF_USER_AUTH')->field('USER_IP')->group('USER_IP')->select();

        $remoteData = curl($siteConfig['serverURL'].'?type=ip&token='.$siteConfig['serverToKen']);
        $remoteData=json_decode($remoteData,true);

        $addrs=[];
        foreach ($mac as $item) {
            $addrs[]= $item['USER_IP'];
        }

        $addrs=array_intersect($remoteData['ip'],$addrs);
        $addrs= array_values($addrs);
        $macs = Db::connect('sqlsrv')->table('CF_USER_AUTH')->whereIn('USER_IP',$addrs)->select();
        $result=[];
        foreach ($macs as $mac) {
            $User = Db::connect('sqlsrv')->table('CF_USER')->where('USN',$mac['USN'])->field('USN,NICK,LAST_PLAY_DATE')->find();
            if(isset($User)){
                set_error_handler(function($errno, $errstr, $errfile, $errline) {
                });
                $nickss = iconv("GB18030", "UTF-8", iconv("UTF-8", "ISO-8859-1", $User['NICK']));
                restore_error_handler();
                $User['NICK'] = empty($nickss) ? $User['NICK']:$nickss;

                $mac['NICK'] = $User['NICK'];
                $mac['LAST_PLAY_DATE']=$User['LAST_PLAY_DATE'];
                $result[]=$mac;
            }
        }
        return json([
            'code' => 0,
            'count' => count($result),
            'data' => $result
        ]);
    }



    public function GameInfoList(){
        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');

        $type = input('type');
        $key = input('key');


        $query = Db::connect('cf_log')->table('CF_PLAY_LOG')
            ->order('REG_DATE', 'DESC')
            ->limit(($page - 1) * $limit, $limit);

        if (!empty(input('key'))) {
            $query->where($type, 'like', '%' . $key . '%'); // 添加模糊搜索条件
        }



        $result = $query->select();

        //下面是统计

        $countQuery = Db::connect('cf_log')->table('CF_PLAY_LOG');

        if (!empty(input('key'))) {
            $countQuery->where($type, 'like', '%' . input('key') . '%'); // 添加模糊搜索条件
        }



        $count = $countQuery->count();


        $json =[];

        foreach ($result as $k=>$v){
            $res = Db::connect('sqlsrv')->table('CF_USER')->where('USN',$v['USN'])
                ->find();


            $json[$k] =[
                'ID' => $v['TX_SRL'],
                'NICK' => $res['NICK'],
                'GAME_LOG_SRL' => $v['GAME_LOG_SRL'],
                'USN' => $v['USN'],
                'START_DATE' => $v['START_DATE'],
                'IP' => $v['IP'],
                'TEAM' => $v['TEAM'],
                'CHAR_ITEM_ID' => $v['CHAR_ITEM_ID'],
                'KILL' => $v['KILL'],
                'DEATH' => $v['DEATH'],
                'EXP' => $v['EXP'],
                'GAME_POINT_AFT' => $v['GAME_POINT_AFT'],
                'EXP_AFT' => $v['EXP_AFT'],
                'REG_DATE' => $v['REG_DATE']
            ];
        }
        return json([
            'code' => 0,
            'count' => $count,
            'data' => $json
        ]);
    }

    public function AjaxCfItemList(){

        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');

        $type = input('type');
        $key = input('key');
        $arg = input('arg');

        $query = Db::connect('sqlsrv')->table('CF_ITEM_INFO')
            ->field('ITEM_ID, ITEM_CODE, NAME, ITEM_INDEX, ITEM_TYPE, SHORT_DESCR, SHORT_NAME, ITEM_CATEGORY1, ITEM_CATEGORY2, SALE_TYPE, USE_TYPE1, USE_TYPE2, USE_TYPE3, USE_TYPE5')
            ->limit(($page - 1) * $limit, $limit);

        if (!empty(input('key'))) {
            $query->where($arg, 'like', '%' . $key . '%'); // 添加模糊搜索条件
        }


        if ($type !== '-1' && !empty($type)) {
            $query->where('ITEM_TYPE', '=', $type)->where($arg, 'like', '%' . $key . '%');
        }

        $result = $query->select();

        //下面是统计

        $countQuery = Db::connect('sqlsrv')->table('CF_ITEM_INFO');

        if (!empty(input('key'))) {
            $countQuery->where($arg, 'like', '%' . input('key') . '%'); // 添加模糊搜索条件
        }

        if ($type !== '-1' && !empty($type)) {
            $countQuery->where('ITEM_TYPE', '=', $type)->where($arg, 'like', '%' . $key . '%');
        }

        $count = $countQuery->count();


        $json =[];
        foreach ($result as $k=>$v){
            $China_name = Often::where('ITEM_ID',$v['ITEM_ID'])->find();
            $name = empty($China_name['name']) ? '' : $China_name['name'];
            $json[$k] =[
                'ITEM_ID' => $v['ITEM_ID'],
                'NAME' => $v['NAME'],
                'CHINA_NAME' => $name,
                'ITEM_ING' => getIiemImg($v['ITEM_INDEX'],$v['ITEM_CODE']),
                'ITEM_CODE' => $v['ITEM_CODE'],
                'ITEM_INDEX' => $v['ITEM_INDEX'],
                'ITEM_TYPE' => cfItemType($v['ITEM_TYPE']),
                'ITEM_CATEGORY1' => cfItemType1($v['ITEM_TYPE'],$v['ITEM_CATEGORY1']),
                'ITEM_CATEGORY2' => cfItemType2($v['ITEM_TYPE'],$v['ITEM_CATEGORY2']),
                'SHORT_NAME' => $v['SHORT_NAME'],
                'SHORT_DESCR' => $v['SHORT_DESCR'],
            ];
        }
        return json([
            'code' => 0,
            'count' => $count,
            'data' => $json
        ]);
    }

    public function UpCf(\think\Request $request){

        if (empty(input('paycf'))){
            return json([
                'code' => '504',
                'msg' => '请输入要充值的点数'
            ]);
        }

        $reg_pay_sql = "EXECUTE WSP_GIVE_CURRENCY @p_USN = ?, @p_GiveUSN = ?, @p_Type = 'C', @p_Ammount = ?, @p_Result = 0";

        $result = Db::connect('cf_sa')->execute($reg_pay_sql, [input('usn'), input('usn'), input('paycf')]);


        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '充值CF给账号：（'.input('usn').'）',
            'ip' => Request::ip(),
            'type' => '充值账号',
            'create_time' => time(),
        ]);

        if (!$result){
            return json([
                'code' => '200',
                'msg' => '充值成功'
            ]);
        }

    }

    public function AddCfuser(\think\Request $request){
        $siteConfig = Configs::gets();
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


        $db = Db::connect('sqlsrv');
        $result = $db->table('CF_MEMBER')->where('USER_ID', input('username'))
            ->whereOr('EMAIL', input('email'))
            ->find();
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

        RegLog::insert([
            'username' => input('username'),
            'password' => input('password'),
            'email' => input('email'),
            'ip' => Request::ip(),
            'status' => 1,
            'create_time' => time(),
        ]);

        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '添加账号，用户名：（'.input('username').'）',
            'ip' => Request::ip(),
            'type' => '添加账号',
            'create_time' => time(),
        ]);

        $db->table('CF_MEMBER')->insert([
            'USER_ID' => input('username'),
            'LUSER_ID' => strtolower(input('username')),
            'USER_PASS' => md5(input('password').$siteConfig['md5pass']),
            'EMAIL' => input('email'),
            'ISACTIVE' => 1,
            'ISPROMOUSER' => 0,
            'NEEDVALIDATION' => 0,
            'REG_DATE' => date("Y-m-d H:i:s").'.'.mt_rand(100,999),
            'ISPROMOUSER' => null,

        ]);

        if (!empty(input('zcf')) || input('zcf') > 0){

            $row =  $db->table('CF_MEMBER')
                ->where('USER_ID', input('username'))
                ->field('USN')
                ->find();

            // $displayValue = (empty($siteConfig['cfpoint']) || $siteConfig['cfpoint'] == 0) ? 0 : $siteConfig['cfpoint'];

            $reg_pay_sql = "EXECUTE WSP_GIVE_CURRENCY @p_USN = ?, @p_GiveUSN = ?, @p_Type = 'C', @p_Ammount = ?, @p_Result = 0";

            $result = Db::connect('cf_sa')->execute($reg_pay_sql, [$row['USN'], $row['USN'], input('zcf')]);
        }

        if (!$result) {
            return json([
                'code' => 200,
                'msg' => '注册成功'
            ]);
        } else {
            return json([
                'code' => 500,
                'msg' => '注册失败'
            ]);
        }

    }

    public function AjaxGROUPTask($id){
        $field = input('field');
        $val = input('val');
        Db::connect('sqlsrv')->table('CF_GACHA_GROUP')->where('SRL', '=', $id)->update([$field => $val]);
        return json([
            'code' => 200,
            'msg' => '更新成功'
        ]);
    }


    public function AjaxEditDarw($id){
        $field = input('field');
        $val = input('val');
        Db::connect('sqlsrv')->table('CF_GACHA_ITEM')->where('SRL', '=', $id)->update([$field => $val]);
        return json([
            'code' => 200,
            'msg' => '更新成功'
        ]);
    }


    public function AjaxEditTask($id){

        $field = input('field');
        $val = input('val');
        Db::connect('sqlsrv')->table('CF_MISSION_INFO')->where('SRL', '=', $id)->update([$field => $val]);
        return json([
            'code' => 200,
            'msg' => '更新成功'
        ]);

    }

    public function AjaxMissTask($id){
        $field = input('field');
        $val = input('val');
        Db::connect('sqlsrv')->table('CF_MISSION_REWARD_DETAIL')->where('REWARD_SRL', '=', $id)->update([$field => $val]);
        return json([
            'code' => 200,
            'msg' => '更新成功'
        ]);
    }

    public function Brushing(\think\Request $request){

        if(empty(input('username'))){
            return json([
                'code' => 500,
                'msg' => '账号不能为空'
            ]);
        }
        $res= Db::connect('sqlsrv')->table('CF_MEMBER')->where('USER_ID', input('username'))
            ->find();


        if (!$res){
            return json([
                'code' => 501,
                'msg' => '账号不存在'
            ]);
        }
        $item = Db::connect('sqlsrv')->table('CF_ITEM_INFO')->where('ITEM_ID', input('itemID'))
            ->find();



        if(empty($item)){
            return json([
                'code' => 503,
                'msg' => '物品不存在'
            ]);
        }


        if(empty(input('num'))){
            return json([
                'code' => 502,
                'msg' => '数量不能为空'
            ]);
        }


        $reg_pay_sql = "EXECUTE WSP_GIVE_ITEM @p_USN = ?, @p_GiveUSN = ?, @p_ID = ?, @p_Name = '', @p_Result = 0";

        $result = Db::connect('cf_sa')->execute($reg_pay_sql, [$res['USN'], $res['USN'], input('itemID')]);



        if (!$result) {
            ControlsLog::insert([
                'username' => $request->User['username'],
                'desc' => '物品发送ItemId（'.input('itemid').'），数量：'.input('num').'，账号：'.$res['USER_ID'],
                'ip' => Request::ip(),
                'type' => '发送物品',
                'create_time' => time(),
            ]);
            return json([
                'code' => 200,
                'msg' => '发送成功'
            ]);
        } else {
            return json([
                'code' => 500,
                'msg' => '发送失败'
            ]);
        }
    }

    public function SendItem(\think\Request $request){
        if(empty(input('username'))){
            return json([
                'code' => 500,
                'msg' => '账号不能为空'
            ]);
        }
        $res= Db::connect('sqlsrv')->table('CF_MEMBER')->where('USER_ID', input('username'))
            ->find();
        if (!$res){
            return json([
                'code' => 501,
                'msg' => '账号不存在'
            ]);
        }
        $num = input('num');
        if (empty($num)) {
            return json([
                'code' => 502,
                'msg' => '数量不能为空'
            ]);
        }

        $reg_pay_sql = "EXECUTE WSP_GIVE_ITEM @p_USN = ?, @p_GiveUSN = ?, @p_ID = ?, @p_Name = '', @p_Result = 0";
        $successCount = 0;

        for ($i = 0; $i < $num; $i++) {
            $result = Db::connect('cf_sa')->execute($reg_pay_sql, [$res['USN'], $res['USN'], input('itemid')]);

            if (!$result) {
                $successCount++;
            }
        }

        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '物品发送ItemId（'.input('itemid').'），数量：'.$successCount.'，账号：'.$res['USER_ID'],
            'ip' => Request::ip(),
            'type' => '物品发送',
            'create_time' => time(),
        ]);



        if ($successCount == $num) {
            return json([
                'code' => 200,
                'msg' => '发送成功'
            ]);
        } else {
            return json([
                'code' => 500,
                'msg' => '发送失败'
            ]);
        }

    }

    public function AjaxSendItemLog(){
        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');
        $query = Db::connect('cf_sa')->table('CF_WEB_ADMIN_GIVE_LOG')
            ->field('SENDER_USN,RECEIVER_USN,GIVEN_TYPE,GIVEN,GIVEN_NAME,REG_DATE')
            ->limit(($page - 1) * $limit, $limit);

        $type = input('type');
        $search = input('key');
        if (!empty($type) && !empty($search)) {
            $query->where($type, 'like', '%' . $search . '%');
        }
        $result = $query->select();
        $countQuery = Db::connect('cf_sa')->table('CF_WEB_ADMIN_GIVE_LOG');
        if (!empty($type) && !empty($type)) {
            $countQuery->where($type, 'like', '%' . $search . '%');
        }
        $count = $countQuery->count();
        $json =[];
        foreach ($result as $k=>$v){
            //$China_name = Often::where('ITEM_ID',$v['ITEM_ID'])->find();
            $res = Db::connect('sqlsrv')->table('CF_USER')->where('USN',$v['SENDER_USN'])
                ->find();

            $item = Db::connect('sqlsrv')->table('CF_ITEM_INFO')->where('ITEM_ID',$v['GIVEN'])->find();
            $China_name = Often::where('ITEM_ID',$v['GIVEN'])->find();
            $name = empty($China_name['name']) ? '' : $China_name['name'];
            $NICK = empty($res['NICK']) ? '<font color="red">未找到</font>' : $res['NICK'];
            $ITEM_INDEX = empty($item['ITEM_INDEX']) ? '': $item['ITEM_INDEX'];
            $ITEM_CODE = empty($item['ITEM_CODE']) ? '': $item['ITEM_CODE'];
            $itemname = empty($item['NAME']) ? '<font color="red">未知</font>' : $item['NAME'];
            $SHORT_NAME = empty($item['SHORT_NAME']) ? '' : $item['SHORT_NAME'];

            $json[$k] =[
                'USN' => $v['SENDER_USN'],
                'NICK' => $NICK,
                'GIVEN' => $v['GIVEN'],
                'ITEM_NAME' => $itemname,
                'SHORT_NAME' => $SHORT_NAME,
                'CHINA_NAME' =>$name,
                'ITEM_ING' => getIiemImg($ITEM_INDEX,$ITEM_CODE),
                'REG_DATE' => $v['REG_DATE'],

            ];
        }
        return json([
            'code' => 0,
            'count' => $count,
            'data' => $json
        ]);
    }

    public function AjaxNewsList(){
        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');
        $result = News::limit(($page - 1) * $limit, $limit)->order('create_time', 'DESC')->select();


        $count = News::count();


        return json([
            'code' => 0,
            'count' => $count,
            'data' => $result
        ]);
    }

    public function AjaxControlsLog(){
        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');
        $result = ControlsLog::limit(($page - 1) * $limit, $limit)->order('create_time', 'DESC')->select();

        $count = ControlsLog::count();


        return json([
            'code' => 0,
            'count' => $count,
            'data' => $result
        ]);
    }

    public function AjaxRegLog(){
        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');
        $query = RegLog::limit(($page - 1) * $limit, $limit)->order('create_time', 'DESC');
        $type = input('type');
        $search = input('key');

        if (!empty($search) ){
            $query->where($type, 'like', '%' . $search . '%');
        }


        $result = $query->select();

        $count = RegLog::when($type, function ($query) use ($type, $search) {
            return $query->where($type, 'like', '%' . $search . '%');
        })->count();


        return json([
            'code' => 0,
            'count' => $count,
            'data' => $result
        ]);
    }

    public function AjaxActivityList(){
        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');
        $result = Activity::limit(($page - 1) * $limit, $limit)->order('create_time', 'DESC')->select();

        $json = [];

        $count = Activity::count();

        foreach ($result as $value){
            $type = $value['argument'] == 1 ? '普通类型' : '特殊类型';
            $status = $value['status'] == 1 ? '启用' : '关闭';
            $json[] = [
                'id' => $value['id'],
                'title' => $value['title'],
                'type' => $type,
                'status' => $status,
                'username' => $value['username'],
                'create_time' => $value['create_time'],
            ];
        }


        return json([
            'code' => 0,
            'count' => $count,
            'data' => $json
        ]);
    }

    public function AjaxActivityLog(){
        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');
        $result = ActivityLog::limit(($page - 1) * $limit, $limit)->order('create_time', 'DESC')->select();

        $json = [];

        $count = ActivityLog::count();

        foreach ($result as $value){
            $res = Activity::where('id',$value['pid'])->find();
            if(isset($res['argument']) && $res['argument'] != null){
                $type = $res['argument'] == 1 ? '普通类型' : '特殊类型';
            } else {
                $type = '已删除';
            }
            $title = empty($res) ? '已删除' : $res['title'];
            $json[] = [
                'id' => $value['id'],
                'title' => $title,
                'type' => $type,
                'username' => $value['username'],
                'create_time' => $value['create_time'],
            ];
        }


        return json([
            'code' => 0,
            'count' => $count,
            'data' => $json
        ]);
    }

    public function AjaxFeedBack(){
        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');
        $result = Feedback::limit(($page - 1) * $limit, $limit)->order('create_time', 'DESC')->select();

        $count = Feedback::count();
        $json = [];
        foreach ($result as $k=>$v){
            $ban = Feedback::where('usn',$v['usn'])->where('type',1)->count();
            $shen = Feedback::where('usn',$v['usn'])->where('type',2)->count();
            $json[$k] = [
                'id' => $v['id'],
                'usn' => $v['usn'],
                'nick' => $v['nick'],
                'email' => $v['email'],
                'ban' => $ban,
                'shen' => $shen,
                'type' => $v['type'],
                'status' => $v['status'],
                'content' => $v['content'],
                'create_time' => $v['create_time'],
            ];
        }
        return json([
            'code' => 0,
            'count' => $count,
            'data' => $json
        ]);
    }

    public function AjaxContent(){
        if (empty(input('id'))){
            return json([
                'code' => 500,
                'content' => '查询错误...'
            ]);
        }

        $res = Feedback::where('id',input('id'))->find();
        return json([
            'code' => 200,
            'content' => $res['content']
        ]);
    }

    public function AjaxCdkLog(){
        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');
        $result = CdkLog::limit(($page - 1) * $limit, $limit)->order('create_time', 'DESC')->select();

        $count = CdkLog::count();
        return json([
            'code' => 0,
            'count' => $count,
            'data' => $result
        ]);
    }

    public function AjaxAdminLog(){
        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');
        $result = AdminsLog::limit(($page - 1) * $limit, $limit)->order('create_time', 'DESC')->select();

        $count = AdminsLog::count();
        return json([
            'code' => 0,
            'count' => $count,
            'data' => $result
        ]);
    }

    public function AjaxPro(){
        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');
        $result = ShopLog::limit(($page - 1) * $limit, $limit)->order('create_time', 'DESC')->select();

        $count = RegLog::count();
        return json([
            'code' => 0,
            'count' => $count,
            'data' => $result
        ]);
    }

    public function resetPassword(\think\Request $request)
    {

        try {
            validate(Users::class)->scene('setPassword')->check(input());

            if (!password_verify(input('old_password'), $request->User['password'])) {
                return json(['code' => 2001, 'msg' => '旧密码不匹配']);
            }
            Admins::update([
                'password' => password_hash(input('password'), PASSWORD_BCRYPT)
            ], [
                'id' => $request->User['id']
            ]);
            session::delete('ADMIN_LOGIN_ID');
            return json(['code' => 200, 'msg' => '密码已修改成功']);
        } catch (ValidateException $exception) {
            return json([
                'code' => 5001,
                'msg' => $exception->getMessage()
            ]);
        }
    }

    public function AjaxBanUser(){

        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');
        $query = Db::connect('sqlsrv')->table('CF_USER')
            ->field('USN,NICK,LEV,REG_DATE,HOLD_TYPE,CONNECT_DENY_UDATE')
            ->order('REG_DATE', 'DESC')
            ->where(function($query) {
                $query->where('HOLD_TYPE', 'E')
                    ->whereOr('CONNECT_DENY_UDATE', '<>', 0);
            })
            ->limit(($page - 1) * $limit, $limit);


        $type = input('type');
        $search = input('key');

        if ($type == 'USER_ID' && !empty($search)){
            $res = Db::connect('sqlsrv')->table('CF_MEMBER')->where('USER_ID',$search)->find();
            $usn = empty($res) ? '' : $res['USN'];
            $query->where('USN',$usn);
        }

        if ($type == 'NICK' && !empty($search)){
            $query->where('NICK',$search);
        }

        $result = $query->select();

        $countQuery = Db::connect('sqlsrv')->table('CF_USER')->where('HOLD_TYPE','E');

        if($type=='USER_ID' && !empty($search)){

            $res = Db::connect('sqlsrv')->table('CF_MEMBER')->where('USER_ID',$search)->find();
            $usn = empty($res) ? '' : $res['USN'];
            $countQuery->where('USN',$usn);

        }
        if ($type == 'NICK' && !empty($search)){
            $countQuery->where('NICK',$search);
        }
        $count = $countQuery->count();


        $json =[];
        foreach ($result as $k=>$v){
            $user = Db::connect('sqlsrv')->table('CF_MEMBER')
                ->where('USN',$v['USN'])
                ->field('USER_ID')
                ->find();
            //$HOLD_TYPE = ($v['HOLD_TYPE'] === 'E' || $v['HOLD_TYPE'] === 'N') ? '<font color="red">是</font>' : ($v['HOLD_TYPE'] === 'A' ? '<font color="green">否</font>' : '');
            switch ($v['HOLD_TYPE']) {
                case 'E':
                case 'N':
                    $HOLD_TYPE = '<font color="red">是</font>';
                    break;
                case 'A':
                    $HOLD_TYPE = '<font color="green">否</font>';
                    break;
            }
            $CONNECT_DENY_UDATE = $v['HOLD_TYPE']!='A' ? '永久' : date('Y-m-d H:i:s',$v['CONNECT_DENY_UDATE']);
            $json[$k] =[
                'USN' => $v['USN'],
                'USER_ID' => $user['USER_ID'],
                'NICK' => iconv("GB18030", "UTF-8", iconv("UTF-8", "ISO-8859-1", $v['NICK'])),
                'LEV' => $v['LEV'],
                'CONNECT_DENY_UDATE' => $CONNECT_DENY_UDATE,
                'HOLD_TYPE' => $HOLD_TYPE,
                'REG_DATE' => $v['REG_DATE'],
            ];
        }
        return json([
            'code' => 0,
            'count' => $count,
            'data' => $json
        ]);
    }

    public function AjaxCha(){
        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');
        $query = Db::connect('sqlsrv')->table('CF_USER')
            ->field('USN,NICK,LOWER_NICK,AUTHORITY,GAME_POINT,LEV,EXP,HOLD_TYPE,REG_DATE,LAST_PLAY_DATE')
            ->order('REG_DATE', 'DESC')
            ->limit(($page - 1) * $limit, $limit);

        $type = input('type');
        $search = input('key');
        if (!empty($type) && !empty($search)) {
            $query->where($type, 'like', '%' . $search . '%');
        }
        $result = $query->select();
        $countQuery = Db::connect('sqlsrv')->table('CF_USER');
        if (!empty($type) && !empty($type)) {
            $countQuery->where($type, 'like', '%' . $search . '%');
        }
        $count = $countQuery->count();


        $json =[];
        foreach ($result as $k=>$v){
            //$HOLD_TYPE = ($v['HOLD_TYPE'] === 'E' || $v['HOLD_TYPE'] === 'N') ? '<font color="red">是</font>' : ($v['HOLD_TYPE'] === 'A' ? '<font color="green">否</font>' : '');
            switch ($v['HOLD_TYPE']) {
                case 'E':
                case 'N':
                    $HOLD_TYPE = '<font color="red">是</font>';
                    break;
                case 'A':
                    $HOLD_TYPE = '<font color="green">否</font>';
                    break;
            }

            if($v['AUTHORITY'] == 'A'){
                $AUTHORITY = '<font color="green">是</font>';
            }elseif($v['AUTHORITY'] == 'G'){
                $AUTHORITY = '<font color="green">是</font>';
            }elseif($v['AUTHORITY'] == 'N'){
                $AUTHORITY = '<font color="red">否</font>';
            }

            //$AUTHORITY = ($v['AUTHORITY'] === 'A' || $v['AUTHORITY'] === 'G') ? '<font color="red">是</font>' : ($v['AUTHORITY'] === 'N' ? '<font color="green">否</font>' : '');
            $json[$k] =[
                'USN' => $v['USN'],
                'NICK' => $v['NICK'],
                'LOWER_NICK' => $v['LOWER_NICK'],
                'LEV' => $v['LEV'],
                'EXP' => $v['EXP'],
                'GAME_POINT' =>$v['GAME_POINT'],
                'HOLD_TYPE' => $HOLD_TYPE,
                'Ban' => $v['HOLD_TYPE'],
                'AUTHORITY' => $AUTHORITY,
                'LAST_PLAY_DATE' => $v['LAST_PLAY_DATE'],
                'REG_DATE' => $v['REG_DATE'],
            ];
        }
        return json([
            'code' => 0,
            'count' => $count,
            'data' => $json
        ]);
    }
    public function EditCfCha(){
        $result = Db::connect('sqlsrv')->table('CF_USER')->where('USN',input('USN'))->update([
            'NICK' => input('NICK'),
            'LEV' => input('LEV'),
            'EXP' => input('EXP'),
            'HOLD_TYPE' => input('HOLD_TYPE'),
            'AUTHORITY' => input('AUTHORITY'),
            'GAME_POINT' => input('GAME_POINT'),
        ]);
        if ($result){
            return json([
                'code' => 200,
                'msg' => '修改成功'
            ]);
        }else{
            return json([
                'code' => 507,
                'msg' => '修改失败'
            ]);
        }
    }
    public function AjaxCfInvrList(){
        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');
        $query = Db::connect('sqlsrv')->table('CF_USER_INVENTORY')
            ->field('INVENTORY_SRL,USN,ITEM_TYPE,ITEM_CODE,EFF_START_DATE,EFF_END_DATE,GAUGE,REG_DATE')
            ->order('REG_DATE', 'DESC')
            ->limit(($page - 1) * $limit, $limit);

        $type = input('type');
        $key = input('key');



        if ($type == 'USN' && !empty($key) || $type == 'ITEM_CODE' && !empty($key)) {
            $query->where($type, 'like', '%' . $key . '%');
        }




        if($type=='NICK' && !empty($key)){

            $res = Db::connect('sqlsrv')->table('CF_USER')->where('NICK',$key)->find();
            $usn = empty($res) ? '' : $res['USN'];
            $query->where('USN',$usn);
        }

        if($type=='USER_ID' && !empty($key)){

            $res = Db::connect('sqlsrv')->table('CF_MEMBER')->where('USER_ID',$key)->find();

            $usn = empty($res) ? '' : $res['USN'];
            $query->where('USN',$usn);
        }



        $result = $query->select();


        $countQuery = Db::connect('sqlsrv')->table('CF_USER_INVENTORY');


        if ($type == 'USN' && !empty($key) || $type == 'ITEM_CODE' && !empty($key)) {
            $countQuery->where($type, 'like', '%' . $key . '%');
        }

        if($type=='NICK'){

            if (!empty($key)){
                $res = Db::connect('sqlsrv')->table('CF_USER')->where('NICK',$key)->find();
                $usn = empty($res) ? '' : $res['USN'];
                $countQuery->where('USN',$usn);
            }

        }

        if($type=='USER_ID'){

            if (!empty($key)){
                $res = Db::connect('sqlsrv')->table('CF_MEMBER')->where('USER_ID',$key)->find();
                $usn = empty($res) ? '' : $res['USN'];
                $countQuery->where('USN',$usn);
            }

        }



        $count = $countQuery->count();
        $json =[];
        foreach ($result as $k=>$v){
            $res = Db::connect('sqlsrv')->table('CF_MEMBER')->where('USN',$v['USN'])
                ->field('USER_ID')
                ->find();
            $ress = Db::connect('sqlsrv')->table('CF_USER')->where('USN',$v['USN'])
                ->field('NICK')
                ->find();
            $item_name = Db::connect('sqlsrv')->table('CF_ITEM_INFO')->where('ITEM_CODE',$v['ITEM_CODE'])
                ->field('NAME')
                ->find();
            $Pname = empty($res['USER_ID']) ? '' : $res['USER_ID'];
            $Nick = empty($ress['NICK']) ? '未知' : $ress['NICK'];
            $json[$k] =[
                'ID' => $v['INVENTORY_SRL'],
                'USN' => $v['USN'],
                'PName' => $Pname,
                'Nick' => $Nick,
                'ITEM_TYPE' => $v['ITEM_TYPE'],
                'ITEM_CODE' =>$v['ITEM_CODE'],
                'ITEM_NAME' => $item_name['NAME'],
                'EFF_END_DATE' => $v['EFF_END_DATE'],
                'REG_DATE' => $v['REG_DATE'],
            ];
        }
        return json([
            'code' => 0,
            'count' => $count,
            'data' => $json
        ]);
    }
    public function CdkList(){
        $cdk = Cdk::paginate(10);
        $json = [];
        foreach ($cdk as $k=>$v){
            $status = $v['status'] == 0 ? '<font color="green">未使用</font>' : '<font color="red">已使用</font>';
            $json[$k] = [
                'id' => $v['id'],
                'code' => $v['code'],
                'name' => $v['name'],
                'item_id' => $v['item_id'],
                'status' => $status,
                'create_time' => $v['create_time'],
            ];
        }
        $count = Cdk::count();
        return json([
            'code' => 0,
            'count' => $count,
            'data' => $json
        ]);
    }
    public function AddCdk(\think\Request $request){
        if (empty(input('name'))){
            return json([
                'code' => 501,
                'msg' => '商品名称不能为空'
            ]);
        }
        if (empty(input('itemid'))){
            return json([
                'code' => 501,
                'msg' => 'ItemId不能为空'
            ]);
        }



        $result = Db::connect('sqlsrv')->table('CF_ITEM_INFO')
            ->where('ITEM_ID',input('itemid'))
            ->find();
        if (!$result){
            return json([
                'code' => 502,
                'msg' => 'ItemId不存在'
            ]);
        }


        $pid = input('itemid');
        $number = input('number');
        $cdKeys = [];
        $saveData = [];

        for ($start = 0; $start < $number; $start++) {
            $cdKeys[$start] = generateSurvivalCDK(32);
            $saveData[$start]['name'] = input('name');
            $saveData[$start]['code'] = $cdKeys[$start];
            $saveData[$start]['item_id'] = $pid;

        }
        $cacheKey = md5(uniqid());

        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '添加CDK',
            'ip' => Request::ip(),
            'type' => '添加CDK',
            'create_time' => time(),
        ]);


        cache("cdKeyCache:" . $cacheKey, $cdKeys, 180);
        (new Cdk())->saveAll($saveData);
        return json(['code' => 200, 'msg' => '添加成功', 'key' => $cacheKey]);

    }

    public function AdminList(\think\Request $request){

        if($request->User['id']!==1){
            $res = Admins::where('id', '<>', '1')->select();
        }else{
            $res = Admins::select();
        }
        $count = $res->count();

        $json = [];
        foreach ($res as $k=>$v){
            $rank = $v['rank']  == 1 ? '超级管理员' : '普通管理员';
            $status = $v['status']  == 1 ? '正常' : '封停';
            $json[$k]=[
                'id' => $v['id'],
                'username' => $v['username'],
                'rank' => $rank,
                'status' => $status,
                'create_time' => $v['create_time']
            ];
        }
        return json([
            'code' => 0,
            'count' => $count,
            'data' => $json
        ]);
    }

    public function SendUserList(\think\Request $request){

        $res = Send::select();
        $count = $res->count();

        $json = [];
        foreach ($res as $k=>$v){
            $status = $v['status']  == 1 ? '正常' : '封停';
            $json[$k]=[
                'id' => $v['id'],
                'userid' => $v['userid'],
                'usn' => $v['usn'],
                'status' => $status,
                'create_time' => $v['create_time']
            ];
        }
        return json([
            'code' => 0,
            'count' => $count,
            'data' => $json
        ]);
    }

    public function EditAdmin(){
        $admin = Admins::where('id',session('ADMIN_LOGIN_ID'))->find();
        if ($admin['rank'] != 1){
            return json([
                'code' => 501,
                'msg' => '权限不足'
            ]);
        }

        if (empty(input('rank')) || empty(input('email')) || empty(input('username')) || empty(input('password')) || empty(input('status'))){
            return json([
                'code' => 500,
                'msg' => '必填项不能为空'
            ]);
        }

        Admins::where('id',input('id'))->update([
            'rank' => input('rank'),
            'email' => input('email'),
            'username' => input('username'),
            'password' => password_hash(input('password'), PASSWORD_BCRYPT),
            'status' => input('status'),

        ]);

        return json([
            'code' => 200,
            'msg' => '修改成功'
        ]);

    }

    public function AddSendUser(){
        $admin = Admins::where('id',session('ADMIN_LOGIN_ID'))->find();
        if ($admin['rank'] != 1){
            return json([
                'code' => 501,
                'msg' => '权限不足'
            ]);
        }

        $CFUSER = Db::connect('sqlsrv')->table('CF_MEMBER')->where('EMAIL', input('email'))
        ->find();

        //var_dump($CFUSER);

        

        if(!$CFUSER){
            return json([
                'code' => 500,
                'msg' => '账号不存在',
            ]);
        }

        $res = Send::where('email',input('email'))->count();
        if ($res > 0){
            return json([
                'code' => 500,
                'msg' => '账号已存在',
            ]);
        }
        if (empty(input('email'))){
            return json([
                'code' => 502,
                'msg' => '必填项不能为空'
            ]);
        }
        $res = Send::insert([
            'usn' => $CFUSER['USN'],
            'userid' => $CFUSER['USER_ID'],
            'email' => $CFUSER['EMAIL'],
            'status' => 1,
            'create_time' => time(),
        ]);
        if($res){
            return json([
                'code' => 200,
                'msg' => '添加成功'
            ]);
        }else{
            return json([
                'code' => 500,
                'msg' => '添加失败'
            ]);
        }
    }

    public function AddAdmin(){

        $admin = Admins::where('id',session('ADMIN_LOGIN_ID'))->find();
        if ($admin['rank'] != 1){
            return json([
                'code' => 501,
                'msg' => '权限不足'
            ]);
        }

        $res = Admins::where('username',input('username'))->count();
        if ($res > 0){
            return json([
                'code' => 500,
                'msg' => '账号已存在',
            ]);
        }

        if (empty(input('username')) || empty(input('password')) || empty(input('email')) || empty(input('rank')) || empty(input('status'))){
            return json([
                'code' => 502,
                'msg' => '必填项不能为空'
            ]);
        }

        $res = Admins::insert([
            'username' => input('username'),
            'password' => password_hash(input('password'), PASSWORD_BCRYPT),
            'email' => input('email'),
            'rank' => input('rank'),
            'rf' => 0,
            'status' => input('status'),
            'create_time' => time(),
        ]);

        if($res){
            return json([
                'code' => 200,
                'msg' => '添加成功'
            ]);
        }else{
            return json([
                'code' => 500,
                'msg' => '添加失败'
            ]);
        }

    }

    public function delCfUser(\think\Request $request){
        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '删除了账号，USN（'.input('id').'）',
            'ip' => Request::ip(),
            'type' => '删除账号',
            'create_time' => time(),
        ]);
        Db::connect('sqlsrv')->table('CF_USER')->where('USN',input('id'))->delete();
        Db::connect('sqlsrv')->table('CF_MEMBER')->where('USN',input('id'))->delete();
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);
    }

    public function Editpass(\think\Request $request){
        $siteConfig = Configs::gets();
        $password = input('password');

        // 判断密码不能为空
        if (empty($password)) {
            return json([
                'code' => 400,
                'msg' => '密码不能为空'
            ]);
        }

        // 判断密码不能超过15位
        if (strlen($password) > 15) {
            return json([
                'code' => 400,
                'msg' => '密码不能超过15位'
            ]);
        }

        // 判断密码不能包含空格
        if (preg_match('/\s/', $password)) {
            return json([
                'code' => 400,
                'msg' => '密码不能包含空格'
            ]);
        }
        Db::connect('sqlsrv')->table('CF_MEMBER')->where('USN',input('usn'))->update([
            'USER_PASS' => md5($password.$siteConfig['md5pass'])
        ]);

        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '修改了‘'.input('usn').'’的密码（'.$password.'）',
            'ip' => Request::ip(),
            'type' => '修改密码',
            'create_time' => time(),
        ]);
        return json([
            'code' => 200,
            'msg' => '修改成功'
        ]);
    }

    public function delCfInv(\think\Request $request){
        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '删除了仓库物品，ID：（'.input('id').'）',
            'ip' => Request::ip(),
            'type' => '删除物品',
            'create_time' => time(),
        ]);
        Db::connect('sqlsrv')->table('CF_USER_INVENTORY')->where('INVENTORY_SRL',input('id'))->delete();
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);
    }

    public function delNews(\think\Request $request){
        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '删除了文章（'.input('id').'）',
            'ip' => Request::ip(),
            'type' => '删除文章',
            'create_time' => time(),
        ]);

        News::where('id',input('id'))->delete();
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);
    }

    public function delAct(\think\Request $request){
        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '删除了活动（'.input('id').'）',
            'ip' => Request::ip(),
            'type' => '删除活动',
            'create_time' => time(),
        ]);

        Activity::where('id',input('id'))->delete();
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);
    }

    public function delCdk(\think\Request $request){
        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '删除CDK（'.input('id').'）',
            'ip' => Request::ip(),
            'type' => '删除CDK',
            'create_time' => time(),
        ]);

        Cdk::where('id',input('id'))->delete();
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);

    }

    public function delCFCha(\think\Request $request){
        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '删除了USN（'.input('id').'）的角色',
            'ip' => Request::ip(),
            'type' => '角色删除',
            'create_time' => time(),
        ]);
        Db::connect('sqlsrv')->table('CF_USER')->where('USN',input('id'))->delete();
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);
    }

    public function FeedBackBanned(\think\Request $request){
        $res = Feedback::where('id',input('id'))->find();

        Db::connect('sqlsrv')->table('CF_USER')->where('USN',$res['usn'])->update([
            'HOLD_TYPE' => 'E'
        ]);
        Feedback::where('id',input('id'))->update([
            'status' => 1,
            'update_time' => time()
        ]);
        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '审核了（'.$res['email'].'）举报角色：‘'.$res['nick'].'’的要求',
            'ip' => Request::ip(),
            'type' => '封禁角色',
            'create_time' => time(),
        ]);
        $mailer = Mailer::instance();

        $mailer->from(config('mailer.username'))
            ->to(input('email'))
            ->subject('举报通知')
            ->html('您举报的账号：'.input('nick').' 经核实已被封号！')
            ->send();
        return json([
            'code' => 200,
            'msg' => '封禁成功'
        ]);
    }

    public function FeedBackLiftBan(\think\Request $request){
        $res = Feedback::where('id',input('id'))->find();

        Db::connect('sqlsrv')->table('CF_USER')->where('USN',$res['usn'])->update([
            'HOLD_TYPE' => 'A',
            'CONNECT_DENY_UDATE' => 0
        ]);
        Feedback::where('id',input('id'))->update([
            'status' => 1,
            'update_time' => time()
        ]);
        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '审核了（'.$res['email'].'）申诉角色：‘'.$res['nick'].'’的要求',
            'ip' => Request::ip(),
            'type' => '解封角色',
            'create_time' => time(),
        ]);
        $mailer = Mailer::instance();

        $mailer->from(config('mailer.username'))
            ->to(input('email'))
            ->subject('申诉通知')
            ->html('您申诉的账号：'.input('email').' 已解封')
            ->send();
        return json([
            'code' => 200,
            'msg' => '解封成功'
        ]);
    }

    public function FeedBackTurn(\think\Request $request){
        Feedback::where('id',input('id'))->update([
            'status' => 3,
            'update_time' => time()
        ]);

        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '驳回（'.input('id').'）的举报申诉要求',
            'ip' => Request::ip(),
            'type' => '驳回工单',
            'create_time' => time(),
        ]);
        $mailer = Mailer::instance();

        $mailer->from(config('mailer.username'))
            ->to(input('email'))
            ->subject('驳回通知')
            ->html('您提交的诉求以为驳回！管理员回复理由：'.input('text'))
            ->send();

        return json([
            'code' => 200,
            'msg' => '驳回成功！'
        ]);
    }

    public function FeedBackDel(\think\Request $request){
        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '删除（'.input('id').'）的举报申诉要求',
            'ip' => Request::ip(),
            'type' => '删除工单',
            'create_time' => time(),
        ]);
        Feedback::where('id',input('id'))->delete();
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);
    }

    public function Kick(\think\Request $request){
        $siteConfig = Configs::gets();
        Db::connect('sqlsrv')->table('CF_USER')->where('USN',input('id'))->update([
            'HOLD_TYPE' => 'E'
        ]);

        //USN	LOG_TYPE	CUR_BLOCK_CNT	BLOCK_MIN	BLOCK_END_DATE	DETECT_LOG_SRL	DETECT_REASON_CODE	MEMO

        Db::connect('sqlsrv')->table('CF_USER_HACK_DETECT_LOG')->insert([
            'USN' => input('id'),
            'LOG_TYPE' => 'BANISH',
            'CUR_BLOCK_CNT' => -1,
            'BLOCK_MIN' => '',
            'BLOCK_END_DATE' => date('Y-m-d H:i:s'),
            'DETECT_LOG_SRL' => -1,
            'DETECT_REASON_CODE' => '-',
            'MEMO' => input('nick'),

        ]);




        $ip = Db::connect('sqlsrv')->table('CF_USER_AUTH')->where('USN',input('id'))->find();

        curl($siteConfig['serverURL'].'?token='.$siteConfig['serverToKen'].'&type=ban&ip='.$ip['USER_IP']);

        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '封禁了（'.input('id').'）的账号',
            'ip' => Request::ip(),
            'type' => '封禁账号',
            'create_time' => time(),
        ]);
        return json([
            'code' => 200,
            'msg' => '封禁成功'
        ]);
    }

    public function DiyKick(\think\Request $request){
        $siteConfig = Configs::gets();
        Db::connect('sqlsrv')->table('CF_USER')->where('USN',input('id'))->update([
            'CONNECT_DENY_UDATE' => strtotime(input('ban_data'))
        ]);

        Db::connect('sqlsrv')->table('CF_USER_HACK_DETECT_LOG')->insert([
            'USN' => input('id'),
            'LOG_TYPE' => 'BANISH',
            'CUR_BLOCK_CNT' => -1,
            'BLOCK_MIN' =>  strtotime(input('ban_data')),
            'BLOCK_END_DATE' => date('Y-m-d H:i:s'),
            'DETECT_LOG_SRL' => -1,
            'DETECT_REASON_CODE' => '-',
            'MEMO' => input('nick'),

        ]);

        $ip = Db::connect('sqlsrv')->table('CF_USER_AUTH')->where('USN',input('id'))->find();


        curl($siteConfig['serverURL'].'?token='.$siteConfig['serverToKen'].'&type=ban&ip='.$ip['USER_IP']);

        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '封禁了（'.input('id').'）的账号,封号时间：'.input('ban_data'),
            'ip' => Request::ip(),
            'type' => '封禁账号',
            'create_time' => time(),
        ]);
        return json([
            'code' => 200,
            'msg' => '封禁成功'
        ]);
    }

    public function Banned(\think\Request $request){
        $siteConfig = Configs::gets();
        Db::connect('sqlsrv')->table('CF_USER')->where('USN',input('id'))->update([
            'HOLD_TYPE' => 'E',
        ]);

        Db::connect('sqlsrv')->table('CF_USER_HACK_DETECT_LOG')->insert([
            'USN' => input('id'),
            'LOG_TYPE' => 'BANISH',
            'CUR_BLOCK_CNT' => -1,
            'BLOCK_MIN' =>  '',
            'BLOCK_END_DATE' => date('Y-m-d H:i:s'),
            'DETECT_LOG_SRL' => -1,
            'DETECT_REASON_CODE' => '-',
            'MEMO' => input('nick'),

        ]);


        $ip = Db::connect('sqlsrv')->table('CF_USER_AUTH')->where('USN',input('id'))->find();

        curl($siteConfig['serverURL'].'?token='.$siteConfig['serverToKen'].'&type=ban&ip='.$ip['USER_IP']);

        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '封禁了（'.input('id').'）的账号',
            'ip' => Request::ip(),
            'type' => '封禁账号',
            'create_time' => time(),
        ]);
        return json([
            'code' => 200,
            'msg' => '封禁成功'
        ]);
    }

    public function LiftBan(\think\Request $request){
        Db::connect('sqlsrv')->table('CF_USER')->where('USN',input('id'))->update([
            'HOLD_TYPE' => 'A',
            'CONNECT_DENY_UDATE' => 0
        ]);

        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '解封了（'.input('id').'）的账号',
            'ip' => Request::ip(),
            'type' => '解封账号',
            'create_time' => time(),
        ]);

        return json([
            'code' => 200,
            'msg' => '解封成功'
        ]);
    }

    public function unsealuser(\think\Request $request){
        Db::connect('sqlsrv')->table('CF_USER')->where('USN',input('id'))->update([
            'HOLD_TYPE' => 'A',
            'CONNECT_DENY_UDATE' => 0
        ]);
        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '解封账号（'.input('id').'）',
            'ip' => Request::ip(),
            'type' => '解封账号',
            'create_time' => time(),
        ]);
        return json([
            'code' => 200,
            'msg' => '解封成功'
        ]);
    }

    public function PostSendP(\think\Request $request){
        if(empty(input('item_id')) && empty(input('name'))){
            return json([
                'code' => 500,
                'msg' => '物品ID与物品名称不能为空'
            ]);
        }
        $res = BatchSend::where('itemid',input('item_id'))->count();
        if ($res !=0){
            return json([
                'code' => 500,
                'msg' => '物品ID已存在'
            ]);
        }
        BatchSend::insert([
            'itemid' => input('item_id'),
            'name' => input('name'),
            'create_time' => time()
        ]);


        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '添加批量发送物品，名称（'.input('name').'）',
            'ip' => Request::ip(),
            'type' => '批量发送',
            'create_time' => time(),
        ]);

        return json([
            'code' => 200,
            'msg' => '添加成功'
        ]);
    }

    public function AddGive(){
        if(empty(input('item_id')) && empty(input('name'))){
            return json([
                'code' => 500,
                'msg' => '物品ID与物品名称不能为空'
            ]);
        }
        $res = Db::connect('sqlsrv')->table('CF_LOGIN_EVENT_INFO')
            ->insert([
                'EVT_DESC' => input('name'),
                'ITEM_ID' => input('item_id'),
                'GIVE_START_DATE' => input('start_data'),
                'GIVE_END_DATE' => input('end_data'),
            ]);

        if ($res){
            return json([
                'code' => 200,
                'msg' => '添加成功'
            ]);
        }

    }

    public function Addhuodong(){
        if(empty(input('item_id')) && empty(input('name'))){
            return json([
                'code' => 500,
                'msg' => '物品ID与物品名称不能为空'
            ]);
        }
        $num = Huodong::count();
        if ($num >=  14){
            return json([
                'code' => 500,
                'msg' => '奖品数量不能超过14个'
            ]);
        }
        $res = Huodong::insert(
            [
                'name' => input('name'),
                'chance' => input('chance'),
                'itemid' => input('item_id'),
                'pic' => input('pic'),
                'create_time' => time(),
            ]
        );

        if ($res){
            return json([
                'code' => 200,
                'msg' => '添加成功'
            ]);
        }
    }



    public function PostSendItemAll(){
        $item = BatchSend::select();

        foreach ($item as $v){
            $user =  Db::connect('sqlsrv')->table('CF_USER')->select();
            foreach ($user as $v){
                $query = Db::connect('sqlsrv')->table('CF_USER_INVENTORY')->insert([
                    'INVENTORY_SRL' => $v['itemid'],
                    'USN' => $v['USN'],
                    'ITEM_TYPE' => '',
                    'ITEM_CODE' => '',
                    'EFF_START_DATE' => '',
                    'EFF_END_DATE' => '',
                    'INVENTORY_SRL' => '',
                    'GAUGE' => '',
                    'REG_DATE' => '',
                ]);
            }
        }




//        if (!$result) {
////            BatchSendLog::insert([
////                'email' => input('email'),
////                'create_time' => time()
////            ]);
//            return json([
//                'code' => 200,
//                'msg' => '发送成功'
//            ]);
//        } else {
//            return json([
//                'code' => 500,
//                'msg' => '发送失败'
//            ]);
//        }
    }

    public function UpAll()
    {
        if (input('type') == 'C') {
            $type = 'CF点';
        } else if (input('type') == 'G') {
            $type = 'CF点';
        } else if (input('type') == 'E') {
            $type = '经验值';
        }

        $id = BatchSendLog::insertGetId([
            'email' => '全服充值' . $type . '数量：' . input('num'),
            'create_time' => time()
        ]);

        Queue::push('app\job\GiveCurrency', ['id' => $id], 'give_currency_queue');

        return json([
            'code' => 200,
            'msg' => '已成功添加到后台队列，请稍等...'
        ]);
    }




    public function UpMoneyAll()
    {
        $res = Db::connect('cf_g4box')->table('TAccountCashMst')->select();

        $rechargeAmount = 1; // 假设充值金额为 100

        $updates = [];
        $count = 0; // 初始化计数器变量

        foreach ($res as $record) {
            $cash = $record['Cash'];
            $newCash = $cash + $rechargeAmount;
            $updates[] = [
                'UserNo' => $record['UserNo'],
                'Cash' => $newCash
            ];
        }

        $totalCount = count($updates); // 获取充值记录的总数

        foreach ($updates as $update) {
            $count++; // 每次充值前递增计数器
            ob_start(); // 启用新的缓冲区

            echo "正在充值第 " . $count . " 个用户<br>"; // 提示正在充值第几个用户

            ob_flush(); // 刷新输出缓冲区
            flush(); // 刷新输出缓冲区，确保及时显示提示信息

            usleep(500000); // 添加延迟，模拟充值过程中的耗时操作

            ob_end_flush(); // 关闭缓冲区
        }

        return ['code' => 200, 'totalCount' => $totalCount];
    }

    public function PostSendItem(\think\Request $request){


        $res= Db::connect('sqlsrv')->table('CF_MEMBER')->where('EMAIL', input('email'))
            ->find();


        if (!$res){
            return json([
                'code' => 501,
                'msg' => '账号不存在'
            ]);
        }


        $item = BatchSend::select();
        $reg_pay_sql = "EXECUTE WSP_GIVE_ITEM @p_USN = ?, @p_GiveUSN = ?, @p_ID = ?, @p_Name = '', @p_Result = 0";

        foreach ($item as $value) {
            $result = Db::connect('cf_sa')->execute($reg_pay_sql, [$res['USN'], $res['USN'], $value['itemid']]);
        }

        if (!$result) {
            BatchSendLog::insert([
                'email' => input('email'),
                'create_time' => time()
            ]);
            ControlsLog::insert([
                'username' => $request->User['username'],
                'desc' => '物品发送，邮箱（'.input('email').'）',
                'ip' => Request::ip(),
                'type' => '物品发送',
                'create_time' => time(),
            ]);
            return json([
                'code' => 200,
                'msg' => '发送成功'
            ]);
        } else {
            return json([
                'code' => 500,
                'msg' => '发送失败'
            ]);
        }
    }

    public function AjaxSendLogList(){

        $page = (int)input('page', '1', 'trim');
        $limit = (int)input('limit', '10', 'trim');
        $result = BatchSendLog::limit(($page - 1) * $limit, $limit)->order('create_time', 'DESC')->select();

        $count = BatchSendLog::count();


        return json([
            'code' => 0,
            'count' => $count,
            'data' => $result
        ]);
    }

    public function delSendP(\think\Request $request){
        if(empty(input('id'))){
            return json([
                'code' => 500,
                'msg' => '参数不能为空'
            ]);
        }
        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '删除了批量发送的物品（'.input('id').'）',
            'ip' => Request::ip(),
            'type' => '批量发送',
            'create_time' => time(),
        ]);
        BatchSend::where('id',input('id'))->delete();

        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);

    }

    public function delHuodong(){
        if(empty(input('id'))){
            return json([
                'code' => 500,
                'msg' => '参数不能为空'
            ]);
        }
        $res = Huodong::where('id',input('id'))->delete();
        if ($res){
            return json([
                'code' => 200,
                'msg' => '删除成功'
            ]);
        }else{
            return json([
                'code' => 500,
                'msg' => '删除失败'
            ]);
        }
    }

    public function delGive(){
        if(empty(input('id'))){
            return json([
                'code' => 500,
                'msg' => '参数不能为空'
            ]);
        }
        $res = Db::connect('sqlsrv')->table('CF_LOGIN_EVENT_INFO')->where('EVT_SRL',input('id'))->delete();
        if ($res){
            return json([
                'code' => 200,
                'msg' => '删除成功'
            ]);
        }else{
            return json([
                'code' => 500,
                'msg' => '删除失败'
            ]);
        }
    }

    public function delSendUser(\think\Request $request){
        if ($request->User['rank']!=1){
            return json([
                'code' => 500,
                'msg' => '权限不足'
            ]);
        }



        $res = Send::where('id',input('id'))->find();


        Send::where('id',input('id'))->delete();
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);
    }

    public function delAdmin(\think\Request $request){
        if ($request->User['rank']!=1){
            return json([
                'code' => 500,
                'msg' => '权限不足'
            ]);
        }

        if (input('id')==1){
            return json([
                'code' => 501,
                'msg' => '系统账号禁止删除'
            ]);
        }

        $res = Admins::where('id',input('id'))->find();
        if (input('id') == session('ADMIN_LOGIN_ID')){
            return json([
                'code' => 502,
                'msg' => '不能删除自己的账号'
            ]);
        }

        Admins::where('id',input('id'))->delete();
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);
    }

    public function rfItemSending(\think\Request $request){
        if ($request->User['rf']!=1){
            return json([
                'code' => 500,
                'msg' => '权限不足'
            ]);
        }


        //Db::execute('TRUNCATE TABLE cf_web_admin_give_log');
        $res = Db::connect('cf_sa')->execute('TRUNCATE TABLE cf_web_admin_give_log');


        if (!$res){
            return json([
                'code' => 200,
                'msg' => '清空成功'
            ]);
        }else{
            return json([
                'code' => 500,
                'msg' => '清空失败'
            ]);
        }

    }

    public function rfcf(\think\Request $request){

        if ($request->User['rf']!=1){
            return json([
                'code' => 500,
                'msg' => '权限不足'
            ]);
        }

        $res = Db::connect('cf_g4box')->execute('TRUNCATE TABLE TAccountCashMst');

        if (!$res){
            return json([
                'code' => 200,
                'msg' => '清空成功'
            ]);
        }else{
            return json([
                'code' => 500,
                'msg' => '清空失败'
            ]);
        }
    }

    public function rfuser(\think\Request $request){

        if ($request->User['rf']!=1){
            return json([
                'code' => 500,
                'msg' => '权限不足'
            ]);
        }

        $res = Db::connect('sqlsrv')->execute('TRUNCATE TABLE CF_MEMBER');


        if (!$res){
            return json([
                'code' => 200,
                'msg' => '清空成功'
            ]);
        }else{
            return json([
                'code' => 500,
                'msg' => '清空失败'
            ]);
        }
    }

    public function rfcha(\think\Request $request){

        if ($request->User['rf']!=1){
            return json([
                'code' => 500,
                'msg' => '权限不足'
            ]);
        }

        $res = Db::connect('sqlsrv')->execute('TRUNCATE TABLE CF_USER');


        if (!$res){
            return json([
                'code' => 200,
                'msg' => '清空成功'
            ]);
        }else{
            return json([
                'code' => 500,
                'msg' => '清空失败'
            ]);
        }
    }

    public function rfinv(\think\Request $request){

        if ($request->User['rf']!=1){
            return json([
                'code' => 500,
                'msg' => '权限不足'
            ]);
        }

        $res = Db::connect('sqlsrv')->execute('TRUNCATE TABLE CF_USER_INVENTORY');


        if (!$res){
            return json([
                'code' => 200,
                'msg' => '清空成功'
            ]);
        }else{
            return json([
                'code' => 500,
                'msg' => '清空失败'
            ]);
        }
    }

    public function delNewsAll(\think\Request $request){
        $ids = input('id');
        if (empty($ids)){
            return json([
                'code' => 500,
                'msg' => '删除选项不能为空'
            ]);
        }
        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '删除选中的新闻有（'.$ids.'）',
            'ip' => Request::ip(),
            'type' => '删除新闻',
            'create_time' => time(),
        ]);
        $ids = explode(".", $ids);
        News::whereIn('id', $ids)->delete();
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);
    }

    public function deleteNews(\think\Request $request){
        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '删除新闻（所有）',
            'ip' => Request::ip(),
            'type' => '删除新闻',
            'create_time' => time(),
        ]);
        Db::execute('TRUNCATE TABLE cf_news');
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);
    }

    public function delLog(){
        $ids = input('id');
        if (empty($ids)){
            return json([
                'code' => 500,
                'msg' => '删除选项不能为空'
            ]);
        }
        $ids = explode(".", $ids);
        RegLog::whereIn('id', $ids)->delete();
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);

    }

    public function unseal(\think\Request $request){
        $ids = input('id');
        if (empty($ids)){
            return json([
                'code' => 500,
                'msg' => '选项不能为空'
            ]);
        }
        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '解封了选择的账号（'.$ids.'）',
            'ip' => Request::ip(),
            'type' => '解封账号',
            'create_time' => time(),
        ]);

        $ids = explode(".", $ids);
        Db::connect('sqlsrv')->table('CF_USER')->whereIn('USN',$ids)->update([
            'HOLD_TYPE' => 'A'
        ]);

        return json([
            'code' => 200,
            'msg' => '解封成功'
        ]);

    }

    public function delInventory(\think\Request $request){
        $ids = input('id');
        if (empty($ids)){
            return json([
                'code' => 500,
                'msg' => '删除选项不能为空'
            ]);
        }
        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '删除了选中仓库物品（'.$ids.'）',
            'ip' => Request::ip(),
            'type' => '删除物品',
            'create_time' => time(),
        ]);
        $ids = explode(".", $ids);
        Db::connect('sqlsrv')->table('CF_USER_INVENTORY')->whereIn('INVENTORY_SRL', $ids)->delete();
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);

    }

    public function delRegAll(){
        Db::execute('TRUNCATE TABLE cf_reg_log');
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);
    }

    public function delActivityLogAll(){
        Db::execute('TRUNCATE TABLE cf_activity_log');
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);
    }

    public function delActivityAll(\think\Request $request){
        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '删除了活动（所有）',
            'ip' => Request::ip(),
            'type' => '删除活动',
            'create_time' => time(),
        ]);
        Db::execute('TRUNCATE TABLE cf_activity');
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);
    }

    public function delCdkAll(){
        Db::execute('TRUNCATE TABLE cf_cdk_log');
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);
    }

    public function delAdminLodAll(\think\Request $request){
        //AdminsLog::where(1)->delete(true);
        if ($request->User['rf']!=1){
            return json([
                'code' => 500,
                'msg' => '权限不足'
            ]);
        }
        Db::execute('TRUNCATE TABLE cf_admins_log');
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);
    }

    public function delPro(){
        $ids = input('id');
        if (empty($ids)){
            return json([
                'code' => 500,
                'msg' => '删除选项不能为空'
            ]);
        }
        $ids = explode(".", $ids);
        ShopLog::whereIn('id', $ids)->delete();
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);

    }

    public function delProAll(){
        //ShopLog::where(1)->delete(true);
        Db::execute('TRUNCATE TABLE cf_shop_log');
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);
    }

    public function AddActivity(\think\Request $request){
        if (empty(input('type')) || empty(input('title')) || empty(input('value')) || empty(input('content')) || empty(input('status')) || empty(input('argument'))){
            return json([
                'code' => 500,
                'msg' => '所有选项不能为空'
            ]);
        }

        $res = Activity::insert([
            'title' => input('title'),
            'content' => input('content'),
            'pic' => input('pic'),
            'status' => input('status'),
            'endtime' => null,
            'argument' => input('argument'),
            'type' => input('type'),
            'value' => input('value'),
            'create_time' => time(),
        ]);

        if ($res){
            ControlsLog::insert([
                'username' => $request->User['username'],
                'desc' => '添加活动（'.input('title').'）',
                'ip' => Request::ip(),
                'type' => '活动添加',
                'create_time' => time(),
            ]);
            return json([
                'code' => 200,
                'msg' => '添加活动成功'
            ]);
        }else{
            return json([
                'code' => 500,
                'msg' => '添加失败'
            ]);
        }
    }

    public function PostNews(\think\Request $request){
        if (empty(input('title')) || empty(input('sid')) || empty(input('content'))){
            return json([
                'code' => 500,
                'msg' => '所有选项不能为空'
            ]);
        }
        $res = News::insert([
            'title' => input('title'),
            'sid' => input('sid'),
            'content' => input('content'),
            'create_time' => time(),
        ]);

        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '添加新闻（'.input('title').'）',
            'ip' => Request::ip(),
            'type' => '添加新闻',
            'create_time' => time(),
        ]);


        if ($res){
            return json([
                'code' => 200,
                'msg' => '添加文章成功'
            ]);
        }

    }

    public function editActivity(\think\Request $request){
        if (empty(input('id'))){
            return json([
                'code' => 500,
                'msg' => '参数有误'
            ]);
        }
        Activity::where('id',input('id'))->update([
            'type'=>input('type'),
            'argument'=>input('argument'),
            'title'=>input('title'),
            'value'=>input('value'),
            'pic'=>input('pic'),
            'content'=>input('content'),
            'status'=>input('status'),
        ]);

        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '添加活动（'.input('title').'）',
            'ip' => Request::ip(),
            'type' => '添加活动',
            'create_time' => time(),
        ]);
        return json([
            'code' => 200,
            'msg' => '修改成功'
        ]);
    }

    public function editNews(\think\Request $request){
        if (empty(input('id'))){
            return json([
                'code' => 500,
                'msg' => '参数有误'
            ]);
        }
        News::where('id',input('id'))->update([
            'title'=>input('title'),
            'content'=>input('content'),
            'sid'=>input('sid'),
        ]);

        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '修改新闻（'.input('title').'）',
            'ip' => Request::ip(),
            'type' => '修改新闻',
            'create_time' => time(),
        ]);

        return json([
            'code' => 200,
            'msg' => '修改成功'
        ]);
    }

    public function AddProduct(\think\Request $request){
        if (empty(input('shoppic')) || empty(input('shopname')) || empty(input('item_id')) || empty(input('price')) || empty(input('currency'))){
            return json([
                'code' => 500,
                'msg' => '所有选项不能为空'
            ]);
        }

        $red = Shop::where('name',input('shopname'))->find();

        if ($red){
            return json([
                'code' => 501,
                'msg' => '商品已经存在'
            ]);
        }

        $res = Shop::insert([
            'name' => input('shopname'),
            'pic' => input('shoppic'),
            'item_id' => input('item_id'),
            'price' => input('price'),
            'type' => input('currency'),
            'status' => input('status'),
            'create_time' => time(),
        ]);

        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '添加商品（'.input('shopname').'）',
            'ip' => Request::ip(),
            'type' => '添加商品',
            'create_time' => time(),
        ]);

        if ($res){
            return json([
                'code' => 200,
                'msg' => '添加商品成功'
            ]);
        }
    }

    public function EditProducts(){
        if (empty(input('shoppic')) || empty(input('shopname')) || empty(input('item_id')) || empty(input('price')) || empty(input('currency'))){
            return json([
                'code' => 500,
                'msg' => '所有选项不能为空'
            ]);
        }
        $res = Shop::where('id',input('id'))->update([
            'name' => input('shopname'),
            'pic' => input('shoppic'),
            'item_id' => input('item_id'),
            'price' => input('price'),
            'status' => input('status'),
            'type' => input('currency'),
        ]);

        if ($res){
            return json([
                'code' => 200,
                'msg' => '修改商品成功'
            ]);
        }

    }

    public function delChe(\think\Request $request){
        $ids = input('id');
        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '删除商品ID：（'.$ids.'）',
            'ip' => Request::ip(),
            'type' => '删除商品',
            'create_time' => time(),
        ]);
        if (empty($ids)){
            return json([
                'code' => 500,
                'msg' => '删除选项不能为空'
            ]);
        }
        $ids = explode(".", $ids);
        Shop::whereIn('id', $ids)->delete();
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);

    }

    public function deleteAll(\think\Request $request){
        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '删除商品（所有）',
            'ip' => Request::ip(),
            'type' => '添加商品',
            'create_time' => time(),
        ]);
        Shop::where(1)->delete(true);
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);
    }

    public function delProducts(\think\Request $request){
        $res = Shop::where('id',input('id'))->find();
        ControlsLog::insert([
            'username' => $request->User['username'],
            'desc' => '删除商品（'.$res['name'].'）',
            'ip' => Request::ip(),
            'type' => '删除商品',
            'create_time' => time(),
        ]);
        Shop::where('id',input('id'))->delete();
        return json([
            'code' => 200,
            'msg' => '删除成功'
        ]);
    }

    public function AjaxProductsList(){

        $list = Shop::select();

        return json([
            'code' => 0,
            'data' => $list
        ]);
    }



}
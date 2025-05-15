<?php

namespace app\controller;

use app\BaseController;
use app\middleware\BaseMiddleware;
use app\middleware\SendUserU;
use app\model\Activity;
use app\model\Huodong;
use app\model\Send;
use app\model\Admins;
use app\model\AdminsLog;
use app\model\Configs;
use app\model\Feedback;
use app\model\News;
use app\model\Shop;
use think\facade\Db;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;
use think\Model;
use think\facade\Cache;
use think\captcha\facade\Captcha;
use think\View as ThinkView;

use think\facade\Http;

class Index extends BaseController
{
    protected $middleware = [
        BaseMiddleware::class,
        SendUserU::class
    ];

    public function test()
    {
        $url = 'https://com.91db0flqeq.com/com/record.html';

        // 模拟jQuery请求的参数
        $params = [
            'callback' => 'jQuery1102031093896407527755_1721989002662',
            'orderby' => 0,
            'id' => 126,
            'page' => 1,
            'lastid' => 12059070,
            'last_top' => 0,
            'key_word' => '',
            'key_msg_word' => '',
            'classid' => 0,
            'id2' => '',
            '_' => time()  // 模拟时间戳参数
        ];

        // 发送GET请求
        $response = Http::get($url, $params);

        // 获取响应内容
        $content = $response->getBody();

        // 输出或处理响应内容
        echo $content;
    }

    private function generateTimestamp()
    {
        list($usec, $sec) = explode(' ', microtime());
        return sprintf('%d%06d', $sec, (int)$usec * 1000);
    }

    public function index()
    {
        $siteConfig = Configs::gets();
        $cacheCF = 'user_cf_list_top';
        $cacheKey = 'user_list_top';
        $cacheExp = 'user_exp_list_top';
        $cacheBan = 'user_Ban_list_top';
        $cacheExpire = 60 * 60 * 24;

        // 从缓存中获取数据
        $cf = Cache::get($cacheCF);
        $list = Cache::get($cacheKey);
        $exp = Cache::get($cacheExp);
        $ban = Cache::get($cacheBan);

        // 如果缓存不存在，则进行数据库查询，并将结果写入缓存

//        if (empty($cf)) {
//            $Cash = Db::connect('cf_g4box')->table('TAccountCashMst')
//                ->order('Cash', 'DESC')
//                ->limit(10)
//                ->select();
//
//            $data = [];
//            $count = 0;
//
//            foreach ($Cash as $row) {
//                if ($count >= 10) {
//                    break;
//                }
//
//                $user = Db::connect('sqlsrv')->table('CF_USER')
//                    ->where('USN', $row['UserNo'])
//                    ->find();
//
//                if (!empty($user)) {
//                    $data[] = [
//                        'NICK' => $user['NICK'],
//                        'CF' => $row['Cash'],
//                        'LEV' => $user['LEV'],
//                        'EXP' => $user['EXP'],
//                        'GAME_POINT' => $user['GAME_POINT'],
//                        'LAST_PLAY_DATE' => $user['LAST_PLAY_DATE'],
//                    ];
//
//                    $count++;
//                }
//            }
//
//            while (count($data) < 10) {
//                $additionalData = Db::connect('cf_g4box')->table('TAccountCashMst')
//                    ->order('Cash', 'DESC')
//                    ->limit(10 - count($data))
//                    ->select();
//
//                foreach ($additionalData as $row) {
//                    if ($count >= 10) {
//                        break;
//                    }
//
//                    $user = Db::connect('sqlsrv')->table('CF_USER')
//                        ->where('USN', $row['UserNo'])
//                        ->find();
//
//                    if (!empty($user)) {
//                        $data[] = [
//                            'NICK' => $user['NICK'],
//                            'CF' => $row['Cash'],
//                            'LEV' => $user['LEV'],
//                            'EXP' => $user['EXP'],
//                            'GAME_POINT' => $user['GAME_POINT'],
//                            'LAST_PLAY_DATE' => $user['LAST_PLAY_DATE'],
//                        ];
//
//                        $count++;
//                    }
//                }
//            }
//
//            Cache::set($cacheCF, $data, $cacheExpire);
//        }


        //gp排行榜
        if (empty($list)) {
            $list = Db::connect('sqlsrv')->table('CF_USER')
                ->field('NICK,LEV,EXP,GAME_POINT,LAST_PLAY_DATE')
                ->whereNotLike('NICK', '%[GM]%')
                ->order('GAME_POINT', 'DESC')
                ->limit(10)
                ->select();

            // 将查询结果写入缓存
            Cache::set($cacheKey, $list, $cacheExpire);
        }
        //经验排行榜
        if (empty($exp)) {
            $exp = Db::connect('sqlsrv')->table('CF_USER')
                ->field('NICK,LEV,EXP,GAME_POINT,LAST_PLAY_DATE')
                ->whereNotLike('NICK', '%[GM]%')
                ->order('EXP', 'DESC')
                ->limit(10)
                ->select();

            // 将查询结果写入缓存
            Cache::set($cacheExp, $exp, $cacheExpire);
        }

        //封神榜
        if (empty($ban)) {
            $ban = Db::connect('sqlsrv')->table('CF_USER_HACK_DETECT_LOG')
                ->order('BLOCK_END_DATE', 'DESC')
                ->limit(10)
                ->select();


            // 将查询结果写入缓存
            Cache::set($cacheBan, $ban, $cacheExpire);
        }


        $news = News::order('create_time', 'DESC')->find();


        View::assign([
            'list' => $list,
            'cf' => $cf,
            'exp' => $exp,
            'ban' => $ban,
            'news' => $news
        ]);

        return View::fetch($siteConfig['template'] . '/index/index');
        //return view();
    }

    public function loginCaptcha()
    {
        ob_clean();
        return Captcha::create();
    }

    public function registerCaptcha()
    {
        ob_clean();
        return Captcha::create();
    }

    public function adminCap(){
        ob_clean();
        return Captcha::create();
    }


    public function register()
    {
        $siteConfig = Configs::gets();
        $captcha = captcha();

        if ($siteConfig['qq'] == 3) {
            $res = session('res');
            if (empty($res)) {
                return redirect("/");
            } else {
                return View::fetch($siteConfig['template'] . '/index/reg', ['captcha' => $captcha, 'res' => $res]);
            }
        } else {
            return View::fetch($siteConfig['template'] . '/index/register', ['captcha' => $captcha]);
        }


    }

    public function login()
    {
        $siteConfig = Configs::gets();
        $captcha = captcha();
        if (!session('USER_LOGIN_ID')) {
            return View::fetch($siteConfig['template'] . '/index/login', ['captcha' => $captcha]);
        } else {
            return redirect("/");
        }
    }

    public function logout()
    {
        session::delete('USER_LOGIN_ID');
        session::delete('USER_LOGIN_USN');
        return redirect("/login");
    }


    public function shop()
    {
        $siteConfig = Configs::gets();
        $limit = 9; // 每页显示的记录数

        $keyword = input('keyword'); // 获取搜索关键字

        $query = Shop::order('create_time', 'DESC')->where('status', 1);

        if ($keyword) {
            $query->where('name', 'like', '%' . $keyword . '%');
            $count = $query->count(); // 统计符合 $keyword 条件的记录数
        } else {
            $count = $query->count(); // 统计所有记录数
        }

        $list = $query->paginate($limit)->appends(['keyword' => $keyword]); // 将 keyword 参数附加到分页链接

        return view($siteConfig['template'] . '/index/shop', ['list' => $list, 'count' => $count]);
    }

    public function send()
    {

        $res = Send::where('usn', session('USER_LOGIN_USN'))->find();

        if (!$res) {
            return redirect("/");
        }


        $siteConfig = Configs::gets();

        $perPage = 12;

        $keyword = input('keyword');


        switch (input('type')) {
            case 'C':
                $type = '角色';
                break;
            case 'D':
                $type = '装备';
                break;
            case 'F':
                $type = '道具';
                break;
            case 'S':
                $type = '背包';
                break;
            case 'W':
                $type = '武器';
                break;
            default:
                $type = '';
                break;
        }


        $item = Db::connect('sqlsrv')->table('CF_ITEM_INFO')
            ->field('ITEM_ID, ITEM_CODE, NAME, ITEM_INDEX, ITEM_TYPE, SHORT_DESCR, SHORT_NAME, ITEM_CATEGORY1, ITEM_CATEGORY2, SALE_TYPE, USE_TYPE1, USE_TYPE2, USE_TYPE3, USE_TYPE5');
        //->paginate($perPage); 


        // if (!empty(input('type'))) {

        //     $item->where('ITEM_TYPE','=',input('type'));

        //     if (!empty(input('arg'))) {

        //         $item->where(input('arg'), 'like', '%' . $keyword . '%');
        //     }
        // }

        if (empty(input('type'))) {

            $item->where(input('arg'), 'like', '%' . $keyword . '%');
        } else {

            $item->where('ITEM_TYPE', '=', input('type'))->where(input('arg'), 'like', '%' . $keyword . '%');;

            // if (!empty(input('arg'))) {

            //     $item->where(input('arg'), 'like', '%' . $keyword . '%');
            // }
        }


        if ($keyword) {
            //$item->where('NAME', 'like', '%' . $keyword . '%');
            $count = $item->count();
        } else {
            $count = $item->count();
        }

        $list = $item->paginate($perPage)->appends(['keyword' => $keyword, 'type' => input('type'), 'arg' => input('arg')]);


        return View::fetch($siteConfig['template'] . '/index/send', ['items' => $list, 'count' => $count, 'type' => $type, 'typeS' => input('type')]);
    }

    public function Adminlogin()
    {
        $siteConfig = Configs::gets();
        if (!empty(session('ADMIN_LOGIN_ID'))) {
            return redirect("/manage");
        }

        return View::fetch($siteConfig['template'] . '/Adminlogin');
    }

    public function LoginPost()
    {

        if (empty(input('username')) || empty('password'))
            return json([
                'code' => 770,
                'msg' => '账号密码不能为空'
            ]);

        if (!captcha_check(input('vercode'))) {
            return json([
                'code' => 777,
                'msg' => '验证码错误'
            ]);
        }
        $res = (new Admins())->where('username', input('username'))->find();

        $ip = Request::ip();
        $cacheKey = 'login_attempts:' . $ip;
        $maxAttempts = 5; // 最大尝试次数
        $banTime = 30 * 60; // 封禁时间（30分钟）

        // 检查是否达到最大尝试次数
        if (Cache::has($cacheKey) && Cache::get($cacheKey) >= $maxAttempts) {
            return json(['code' => 503, 'msg' => '登录频繁，请稍后再试']);
        }

        if (!$res || !password_verify(input('password'), $res['password'])) {
            // 增加错误次数记录
            if (Cache::has($cacheKey)) {
                Cache::inc($cacheKey);
            } else {
                Cache::set($cacheKey, 1, $banTime);
            }

            return json(['code' => 500, 'msg' => '用户名或密码错误']);
        }

        if ($res['status'] == 2) {
            return json([
                'code' => 502,
                'msg' => '账号已被停用，请联系管理员咨询'
            ]);
        }

        AdminsLog::insert([
            'users' => $res['username'],
            'ip' => $ip,
            'create_time' => time()
        ]);

        session('ADMIN_LOGIN_ID', $res['id']);

        // 登录成功后删除错误次数记录
        if (Cache::has($cacheKey)) {
            Cache::rm($cacheKey);
        }

        return json(['code' => 1, 'msg' => '登陆成功，您好' . $res['username'], 'data' => ['mail' => $res['username']]]);
    }

    public function feedback()
    {
        $siteConfig = Configs::gets();
        if (!empty(session('USER_LOGIN_USN'))) {
            $userinfo = Db::connect('sqlsrv')->table('CF_MEMBER')
                ->where('USN', session('USER_LOGIN_USN'))
                ->where('USER_ID', session('USER_LOGIN_ID'))
                ->find();

        }

        $email = empty(session('USER_LOGIN_USN')) ? '' : $userinfo['EMAIL'];
        $ok = Feedback::limit(10)->order('create_time', 'DESC')->select();
        $countList = [];
        foreach ($ok as $item) {
            $usn = $item->usn;
            if (!isset($countList[$usn])) {
                $countList[$usn] = 1;
            } else {
                $countList[$usn]++;
            }
        }

        return View::assign([
            'email' => $email,
            'ok' => $ok,
            'countList' => $countList
        ])->fetch($siteConfig['template'] . '/index/feedback');

    }


    public function retrieve()
    {
        $siteConfig = Configs::gets();
        return View::fetch($siteConfig['template'] . '/index/retrieve');
    }

    public function news()
    {
        $siteConfig = Configs::gets();
        $limit = 10; // 每页显示的记录数


        $query = News::order('create_time', 'DESC');

        $count = $query->count(); // 统计所有记录数

        $list = $query->paginate($limit);

        return view($siteConfig['template'] . '/index/news', ['list' => $list, 'count' => $count]);
    }

    public function article($id = null)
    {
        $siteConfig = Configs::gets();
        if (empty(intval($id))) {
            return redirect("/");
        }
        $res = News::where('id', intval($id))->find();
        //当没有数据的时候跳回首页防止报错
        if (!$res) {
            return redirect("/");
        }
        View::assign('res', $res);
        return View::fetch($siteConfig['template'] . '/index/article');
    }

    public function activity()
    {
        $siteConfig = Configs::gets();
        $limit = 10; // 每页显示的记录数

        $query = Activity::order('create_time', 'DESC');

        $count = $query->count(); // 统计所有记录数

        $list = $query->paginate($limit);
        return view($siteConfig['template'] . '/index/activity', ['list' => $list, 'count' => $count]);

    }

    public function BanChinaName()
    {
        $siteConfig = Configs::gets();
        $data = Db::connect('sqlsrv')->table('CF_USER')
            ->where('HOLD_TYPE', 'E')
//            ->where(function ($query) {
//                $query->where(function ($subQuery) {
//                    $subQuery->whereRaw("NICK COLLATE Chinese_PRC_CS_AS_KS_WS like '%[^a-zA-Z0-9]%'")
//                        ->whereOrRaw("NICK LIKE '%[^ -~]%'")
//                        ->whereNotIn('NICK', ['*', '-', '_', '.']);
//                });
//            })
            ->select();

        foreach ($data as $item) {
            Db::connect('sqlsrv')->table('CF_USER')
                ->where('USN', $item['USN']) // 假设 id 是主键字段名
                ->update(['HOLD_TYPE' => 'A']);

            //$ip = Db::connect('sqlsrv')->table('CF_USER_AUTH')->where('USN', $item['USN'])->find();

            //curl($siteConfig['serverURL'].'?token='.$siteConfig['serverToKen'].'&type=ban&ip='.$ip['USER_IP']);

//            Db::connect('sqlsrv')->table('CF_USER_HACK_DETECT_LOG')->insert([
//                'USN' => $item['USN'],
//                'LOG_TYPE' => 'BANISH',
//                'CUR_BLOCK_CNT' => -1,
//                'BLOCK_MIN' => '',
//                'BLOCK_END_DATE' => date('Y-m-d H:i:s'),
//                'DETECT_LOG_SRL' => -1,
//                'DETECT_REASON_CODE' => '-',
//                'MEMO' => $item['NICK'],
//            ]);
        }


        return json([
            'code' => 200,
            'msg' => '执行成功'
        ]);

    }

    public function a20240506()
    {
        $res = Huodong::order('create_time', 'DESC')
            ->select();
        View::assign('res', $res);
        return View();
    }


}

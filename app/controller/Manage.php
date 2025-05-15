<?php
/**
 *
 * User: 会飞的鱼
 * Date: 2023/7/29
 * QQ: 137691250
 * Email: <137691250@qq.com>
 */

namespace app\controller;
use app\middleware\AuthU;
use app\middleware\BaseMiddleware;
use app\model\Activity;
use app\model\Admins;
use app\model\BatchSend;
use app\model\Cdk;
use app\model\Configs;
use app\model\Huodong;
use app\model\News;
use app\model\Product;
use app\model\RegLog;
use app\model\Shop;
use think\facade\Db;
use think\facade\View;
use think\facade\Cache;
use think\facade\Session;

use think\Request;

class Manage
{
    private $BT_KEY = "8YqTpweckt6y6v1BUZtydoyZ93GElkCZ";    //接口密钥
    private $BT_PANEL = "http://103.239.244.2:8888/";            //面板地址

    protected $middleware = [
        AuthU::class,
        BaseMiddleware::class
    ];
    public function logout()
    {
        //session('ADMIN_LOGIN_ID', null);
        session::delete('ADMIN_LOGIN_ID');
        return redirect("/login");
    }

    public function index(){
        return view();
    }

    public function system(){
        return View::fetch('manage/system/theme');
    }

    public function template(){
        return View::fetch('manage/system/error');
    }



    public function home()
    {

        $onlineCf = Db::connect('sqlsrv')->table('CF_MIN_CU')
            ->where('SERVER',1)
            ->find();
        view::assign([
            'last_sysinfo'=> cache('last_sysinfo'),
            'onlineCf' => $onlineCf['CONNECT_CNT'],
            'NumUser' => RegLog::count(),
            'shopNum' => Shop::count()
        ]);
        return view();
    }
    public function layout(){
        return view();
    }
    public function web(){
        return view();
    }
    public function CfUser(){
        return view();
    }
    public function AddCfuser(){
        return view();
    }
    public function Cfitem(){
        return view();
    }
    public function Inventory(){
        return view();
    }
    public function BuyProduct(){
        return view();
    }
    public function EditCfCha(){
        $res = Db::connect('sqlsrv')->table('CF_USER')->where('USN',input('id'))
            ->find();
        View::assign('res',$res);
        return view();
    }

    public function upcf(){
        $res = Db::connect('sqlsrv')->table('CF_MEMBER')->where('USN',input('id'))
            ->find();
        $ress = Db::connect('cf_g4box')->table('TAccountCashMst')->where('UserNo',input('id'))
            ->find();
        View::assign([
            'USN' => $res['USN'],
            'EMAIL' => $res['EMAIL'],
            'CF' => empty($ress['Cash']) ? 0 : $ress['Cash']
        ]);
        return view();
    }
    public function SendItem(){
        View::assign('item_id',input('id'));
        return view();
    }
    public function ItemSending(){
        return view();
    }
    public function Characters(){
        return view();
    }
    public function cdk(){
        return view();
    }
    public function AddCdk(){
        return view();
    }
    public function AddNews(){
        return view();
    }
    public function News(){
        return view();
    }
    public function AddProduct(){
        return view();
    }
    public function editProducts(){
        $res = Shop::where('id',input('id'))->find();
        View::assign('res',$res);
        return view();
    }
    public function Products(){
        return view();
    }
    public function adminUser(){
        $this->checkPower();
        return view();
    }
    public function SendUser(){
        $this->checkPower();
        return view();
    }
    public function AddAdmin(){
        return view();
    }
    public function AddSendUser(){
        return view();
    }

    public function RegLog(){
        return view();
    }

    public function Brushing(){
        return view();
    }

    public function Clear(){
        return view();
    }

    public function AdminLog(){
        return view();
    }

    public function resetpwd(){
        return view();
    }

    public function editAdmin(){
        $info = Admins::where('id',input('id'))->find();
        View::assign('info',$info);
        return view();
    }

    public function showNewCdkey($key)
    {
        $content = cache("cdKeyCache:" . $key);
        if (!$content) {
            return "请返回数据表查询";
        }
        View::assign('list', $content);
        return view();
    }

    public function CdkRecord(){
        return view();
    }

    public function BanUser(){
        return view();
    }

    public function Feedback(){
        return view();
    }

    public function GameShop(){
        return view();
    }

    public function editNews($id){
        if (empty(intval($id))){
            return redirect("/manage#/News");
        }
        $res = News::where('id',$id)->find();
        View::assign('res',$res);
        return view();

    }

    public function BatchSending(){
        $res = BatchSend::order('create_time', 'DESC')->select();
        View::assign('res',$res);
        return view();
    }

    public function GiveAway(){
        $res = Db::connect('sqlsrv')->table('CF_LOGIN_EVENT_INFO')
            ->order('GIVE_END_DATE', 'DESC')
            ->select();
        View::assign('res',$res);
        return view();
    }
    public function TaskSet(){
        return view();
    }

    public function ControlsLog(){
        return view();
    }

    public function AddActivity(){
        return view();
    }

    public function ActivityLog(){
        return view();
    }

    public function Activity(){
        return view();
    }

    public function DiyKick(){
        $res = Db::connect('sqlsrv')->table('CF_USER')->where('USN',input('id'))->find();
        View::assign('res',$res);
        return view();
    }

    public function editAct($id){
        if (empty(intval($id))){
            return redirect("/manage#/News");
        }
        $res = Activity::where('id',$id)->find();
        View::assign('res',$res);
        return view();
    }

    public function DrawSet(){
        return view();
    }

    public function GameInfo(){
        return view();
    }

    public function UserOnline(){
        return view();
    }

    public function CfUserGm(){
        return view();
    }

    public function menu(\think\Request $request){



        //菜单
        $result = [
            'code' => 0,
            'data' => [
                [
                    'name' => 'home',
                    'title' => '主页',
                    'icon' => 'layui-icon layui-icon-home',
                    'list' => [
                        [
                            'name' => 'home1',
                            'title' => '控制面板',
                            'jump' => '/',
                        ]
                    ],
                ], [
                    'name' => 'shop',
                    'title' => '商城模块',
                    'icon' => 'layui-icon layui-icon-picture',
                    'list' => [
                        [
                            'name' => 'shop',
                            'title' => '添加商品',
                            'jump' => 'AddProduct',
                        ],[
                            'name' => 'shop',
                            'title' => '商品管理',
                            'jump' => 'Products',
                        ],[
                            'name' => 'shop',
                            'title' => '购买记录',
                            'jump' => 'BuyProduct',
                        ],
                    ],
                ], [
                    'name' => 'user',
                    'title' => '用户模块',
                    'icon' => 'layui-icon layui-icon-engine',
                    'list' => [
                        [
                            'name' => 'user',
                            'title' => '注册记录',
                            'jump' => 'RegLog',
                        ],[
                            'name' => 'user',
                            'title' => '封禁用户',
                            'jump' => 'BanUser',
                        ],[
                            'name' => 'UserOnline',
                            'title' => '在线用户',
                            'jump' => 'UserOnline',
                        ],
                    ],
                ],[
                    'name' => 'cdk',
                    'title' => 'CDK模块',
                    'icon' => 'layui-icon layui-icon-gift',
                    'list' => [
                         [
                            'name' => 'cdk',
                            'title' => 'CDK管理',
                            'jump' => 'Cdk',
                        ],[
                            'name' => 'cdk',
                            'title' => '使用记录',
                            'jump' => 'CdkRecord',
                        ]
                    ],
                ],[
                    'name' => 'item',
                    'title' => '物品管理',
                    'icon' => 'layui-icon layui-icon-gift',
                    'list' => [
                        [
                            'name' => 'item',
                            'title' => '物品大全',
                            'jump' => 'CfItem',
                        ],[
                            'name' => 'item',
                            'title' => '发送记录',
                            'jump' => 'ItemSending',
                        ]
                    ],
                ], [
                    'name' => 'other',
                    'title' => '其他模块',
                    'icon' => 'layui-icon layui-icon-chart',
                    'list' => [
                        [
                            'name' => 'item',
                            'title' => '用户仓库',
                            'jump' => 'Inventory',
                        ],[
                            'name' => 'item',
                            'title' => '角色管理',
                            'jump' => 'Characters',
                        ],[
                            'name' => 'item',
                            'title' => '刷取物品',
                            'jump' => 'Brushing',
                        ],[
                            'name' => 'BatchSending',
                            'title' => '批量发送',
                            'jump' => 'BatchSending',
                        ],[
                            'name' => 'GameInfo',
                            'title' => '对局信息',
                            'jump' => 'GameInfo',
                        ]
//                        [
//                            'name' => 'item',
//                            'title' => '游戏商城',
//                            'jump' => 'GameShop',
//                        ]
                    ],
                ], [
                    'name' => 'activity',
                    'title' => '活动配置',
                    'icon' => 'layui-icon layui-icon-chart',
                    'list' => [
                        [
                            'name' => 'activity',
                            'title' => '活动列表',
                            'jump' => 'Activity',
                        ],[
                            'name' => 'activitylog',
                            'title' => '领取记录',
                            'jump' => 'ActivityLog',
                        ]
                    ],
                ], [
                    'name' => 'system',
                    'title' => '系统模块',
                    'icon' => 'layui-icon layui-icon-chart',
                    'list' => [
                        [
                            'name' => 'feedback',
                            'title' => '举报申诉',
                            'jump' => 'Feedback',
                        ]
                    ],
                ]
            ],
            'config' => [
                'logo' => '<i class="layui-icon layui-icon layui-icon-headset" style="font-weight:bold;font-size:20px;"></i><span style="font-weight: bold;font-size: 20px"> 操你妈系统</span>',
            ],
        ];
        if ($request->User['rank'] == 1) {

            $result['data'][] = [
                'name' => 'news',
                'title' => '新闻模块',
                'icon' => 'layui-icon layui-icon-gift',
                'list' => [
                    [
                        'name' => 'news',
                        'title' => '发布新闻',
                        'jump' => 'AddNews',
                    ],[
                        'name' => 'news',
                        'title' => '新闻管理',
                        'jump' => 'News',
                    ],
                ],
            ];

            $result['data'][2]['list'][] = [
                'name' => 'user',
                'title' => '用户管理',
                'jump' => 'CfUser',
            ];

            $result['data'][2]['list'][] = [
                'name' => 'userGm',
                'title' => 'GM列表',
                'jump' => 'CfUserGm',
            ];



            $result['data'][7]['list'][] = [
                'name' => 'system',
                'title' => '系统设置',
                'jump' => 'web',
            ];
            $result['data'][7]['list'][] = [
                'name' => 'system',
                'title' => '清空数据',
                'jump' => 'Clear',
            ];
            $result['data'][7]['list'][] = [
                'name' => 'giveaway',
                'title' => '注册赠送',
                'jump' => 'GiveAway',
            ];
            $result['data'][7]['list'][] = [
                'name' => 'taskset',
                'title' => '任务配置',
                'jump' => 'TaskSet',
            ];
            $result['data'][7]['list'][] = [
                'name' => 'drawset',
                'title' => '扭蛋配置',
                'jump' => 'DrawSet',
            ];
            $result['data'][7]['list'][] = [
                'name' => 'Controlslog',
                'title' => '操作记录',
                'jump' => 'ControlsLog',
            ];
            $result['data'][7]['list'][] = [
                'name' => 'system',
                'title' => '登录日记',
                'jump' => 'AdminLog',
            ];
            $result['data'][7]['list'][] = [
                'name' => 'system',
                'title' => '后台账号',
                'jump' => 'adminUser',
            ];
            $result['data'][7]['list'][] = [
                'name' => 'system',
                'title' => '物品权限',
                'jump' => 'SendUser',
            ];
            $result['data'][7]['list'][] = [
                'name' => 'system',
                'title' => '抽奖活动',
                'jump' => 'huodong',
            ];
        }
        return json($result);
    }

    public function load(){
        $count = $this->GetNetWork();
        $reglog = RegLog::order('create_time', 'desc')->limit(0,10)->select();
        $reghtml = [];
        foreach ($reglog as $v){
            $reghtml[] = '<tr>
                                <td><font color="red">'.$v['username'].'</font></td>
                                <td><span>'.$v['email'].'</span></td>
                                <td><p>'.$v['ip'].'</p></td>
                            </tr>';
        }


        $CfUser = Db::connect('sqlsrv')->table('CF_USER')
            ->field('USN,NICK,LOWER_NICK,AUTHORITY,GAME_POINT,LEV,EXP,HOLD_TYPE,REG_DATE,LAST_PLAY_DATE')
            ->where('LAST_PLAY_DATE', '<>', '3000-12-31 23:59:59.000')
            ->order('LAST_PLAY_DATE', 'DESC')
            ->limit(0,10)
            ->select();

        $userhtml = [];
        foreach ($CfUser as $v){
            $userhtml[] = '<tr>
                                <td>'.$v['NICK'].'</td>
                                <td><span class="getface">'.$v['LAST_PLAY_DATE'].'</span></td>
                            </tr>';
        }

        $onlineCf = Db::connect('sqlsrv')->table('CF_MIN_CU')
            ->where('SERVER',1)
            ->find();

        $result = [
            'code' => 0,
            'data' => [],
            'msg' => ''
        ];

        if (empty($onlineCf)) {
            $result['msg'] = 'Failed to fetch onlineCf data';
        } else {
            $result = [
                'code' => 0,
                'data' => [
//                'mem' => round($count['mem']['memRealUsed'] / $count['mem']['memTotal'] * 100, 1) . '%',
//                'memRealUsed' => $count['mem']['memRealUsed'],
//                'memTotal' => $count['mem']['memTotal'],
//                'mems' => 'layui-progress-bar',
//                'cpu' => $count['cpu'][0] . '%',
//                'cpus' => 'layui-progress-bar',
//                'disk' => $count['disk'][0]['size'][3],
//                'diska' => $count['disk'][0]['size'][0],
//                'diskb' => $count['disk'][0]['size'][1],
//                'disks' => 'layui-progress-bar',
//                'up' => $count['up'],
//                'down' => $count['down'],
//                'load' => $count['load']['one'] . '%',
//                'loads' => 'layui-progress-bar',
                    'mem' => '100%',
                    'memRealUsed' => '100',
                    'memTotal' => '100',
                    'mems' => 'layui-progress-bar',
                    'cpu' => '100%',
                    'cpus' => 'layui-progress-bar',
                    'disk' => '100',
                    'diska' => '100',
                    'diskb' => '100',
                    'disks' => 'layui-progress-bar',
                    'up' => '100',
                    'down' => '100',
                    'load' => '100%',
                    'loads' => 'layui-progress-bar',
                    'NumUser' => Db::connect('sqlsrv')->table('CF_MEMBER')->count(),
                    'NumItem' => Db::connect('sqlsrv')->table('CF_ITEM_INFO')->count(),
                    'onlineCf' => $onlineCf['CONNECT_CNT'],
                    'shopNum' => Shop::count(),
                    'ssbfb'=>$reghtml,
                    'xdls'=>$userhtml,
                ],
                'msg' => ''
            ];
        }



        cache('last_sysinfo', $result['data']);
        return json($result);
    }

    public function huodong(){
        $res = Huodong::order('create_time', 'DESC')
            ->select();
        $Prizes = Huodong::field('id,name, chance,pic,itemid')
            ->select()
            ->toArray();
        $Prizes = array_sum(array_column($Prizes, 'chance'));
        View::assign([
            'res'   =>  $res,
            'Prizes'    =>  $Prizes
        ]);
        return View();
    }

    public function GetNetWork()
    {
        $url = $this->BT_PANEL . $this->config("GetNetWork");

        $p_data = $this->GetKeyData();

        $result = $this->HttpPostCookie($url, $p_data);

        $data = json_decode($result, true);
        return $data;
    }

    /**
     * 加载宝塔数据接口
     * @param  [type] $str [description]
     * @return [type]      [description]
     */
    private function config($str)
    {
        require_once('BtConfig.php');
        return $config[$str];
    }

    /**
     * 发起POST请求
     * @param String $url 目标网填，带http://
     * @param Array|String $data 欲提交的数据
     * @return string
     */
    private function HttpPostCookie($url, $data, $timeout = 60)
    {
        //定义cookie保存位置
        $cookie_file = './RtrS9n%#PJtV.DQtWf7@/' . md5($this->BT_PANEL) . '.cookie';
        if (!file_exists($cookie_file)) {
            $fp = fopen($cookie_file, 'w+');
            fclose($fp);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    /**
     * 构造带有签名的关联数组
     */
    public function GetKeyData()
    {
        $now_time = time();
        $p_data = array(
            'request_token' => md5($now_time . '' . md5($this->BT_KEY)),
            'request_time' => $now_time
        );
        return $p_data;
    }


    function checkPower()
    {
        $request = request();
        if($request->User['rank']!==1){
            exit(View::fetch('manage/system/error'));
        }
    }
}
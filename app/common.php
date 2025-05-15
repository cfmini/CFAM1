<?php
use think\facade\Config;
use think\facade\Filesystem;
// 应用公共文件
function checkEmail($email)

{

    $pregEmail = "/([a-z0-9]*[-_\.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[\.][a-z]{2,3}([\.][a-z]{2})?/i";

    return preg_match($pregEmail, $email);

}

function checkQQEmail($email)

{

    $pattern = '/^[1-9][0-9]{4,10}@qq.com$/';

    return preg_match($pattern, $email);

}
function getIiemImg($item_index,$item_code){
    $strlen = strlen($item_index);
    if($strlen == 1){
        $item_index = '00'.$item_index;
    }
    if($strlen == 2){
        $item_index = '0'.$item_index;
    }
    $file = '/static/images/ITEMICON/ITEMICON_'.$item_index.'.PNG';

    return $file;

}
function cfItemType($t){
    $data = [
        'C' => '角色',
        'D' => '装备',
        'W' => '武器',
        'F' => '道具',
        'S' => '背包',
    ];
    return $data[$t] ?? $t;
}
function cfItemType1($t,$t2){
    $data = [
        'W' => [
            'M' => '主武器',
            'S' => '副武器',
            'K' => '近身武器',
            'D' => '投抛武器',
        ],
        'F' => [
            '2' => '装备',
        ],
    ];
    return $data[$t][$t2] ?? $t2;
}
function cfItemType2($t,$t2){
    $data = [
        'C' => '角色',
        'D' => [
            'name' => '装备',
            'SF' => '脸部',
            'SB' => '背部',
            'SH' => '头部',
            'SS' => '肩膀',
            'SW' => '腰部',
            'STL' => '腰部',
            'SFTP' => '透明眼镜',
            'SHTP' => '透明头盔',
            'TF' => '<font color="red">无效物品</font>',
        ],
        'W' => [
            'name' => '武器',
            'R' => '步枪',
            'SR' => '狙击枪',
            'SM' => '冲锋枪',
            'M' => '机枪',
            'S' => '散弹枪',
            'HE' => '手雷',
            'P' => '手枪',
            'SG' => '烟雾弹',
            'FB' => '闪光弹',
            'K' => '近身武器',

        ],
        'F' => [
            ''
        ],
    ];
    return $data[$t][$t2] ?? $t2;
}
function generateSurvivalCDK($length = 8) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $cdk = '';

    for ($i = 0; $i < $length; $i++) {
        $randomIndex = mt_rand(0, strlen($characters) - 1);
        $cdk .= $characters[$randomIndex];
    }

    return $cdk;
}

function post_request($url, $postdata) {
    $data = http_build_query($postdata);

    $options    = array(
        'http' => array(
            'method'  => 'POST',
            'header'  => "Content-type: application/x-www-form-urlencoded",
            'content' => $data,
            'timeout' => 5
        )
    );
    $context = stream_context_create($options);
    $result    = file_get_contents($url, false, $context);
    if(strpos($http_response_header[0], '200') === false){
        $result = array(
            "result" => "success",
            "reason" => "request geetest api fail"
        );
        return json_encode($result);
    }else{
        return $result;
    }
}


function get_rand($proArr) {
    $result = '';

    //概率数组的总概率精度
    $proSum = array_sum($proArr);

    //概率数组循环
    foreach ($proArr as $key => $proCur) {
        $randNum = mt_rand(1, $proSum);
        if ($randNum <= $proCur) {
            $result = $key;
            break;
        } else {
            $proSum -= $proCur;
        }
    }
    unset ($proArr);

    return $result;
}

function curl($url){ //Curl GET
    $ch = curl_init();     // Curl 初始化
    $timeout = 30;     // 超时时间：30s
    $ua='Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36';// 伪造抓取 UA
    $ip = mt_rand(11, 191) . "." . mt_rand(0, 240) . "." . mt_rand(1, 240) . "." . mt_rand(1, 240);
    curl_setopt($ch, CURLOPT_URL, $url);// 设置 Curl 目标
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);// Curl 请求有返回的值
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);// 设置抓取超时时间
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// 跟踪重定向
    curl_setopt($ch, CURLOPT_REFERER, 'https://www.baidu.com/');//模拟来路
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:'.$ip, 'CLIENT-IP:'.$ip));  //伪造IP
    curl_setopt($ch, CURLOPT_USERAGENT, $ua);// 伪造ua
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);// https请求 不验证证书和hosts
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);//强制协议为1.0
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );//强制使用IPV4协议解析域名
    $content = curl_exec($ch);
    curl_close($ch);// 结束 Curl
    return $content;// 函数返回内容
}

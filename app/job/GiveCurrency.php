<?php
/**
 *
 * User: 会飞的鱼
 * Date: 2023/7/29
 * QQ: 137691250
 * Email: <137691250@qq.com>
 */
namespace app\job;

use app\model\BatchSendLog;
use think\queue\Job;
use think\facade\Db;

class GiveCurrency
{
    public function fire(Job $job, $data)
    {

        $job->delete();


        $user = Db::connect('sqlsrv')->table('CF_MEMBER')->select();

        foreach ($user as $v) {
            $sql = "EXECUTE WSP_GIVE_CURRENCY @p_USN = ?, @p_GiveUSN = ?, @p_Type = ?, @p_Ammount = ?, @p_Result = 0";
            Db::connect('cf_sa')->execute($sql, [$v['USN'], $v['USN'], input('type'), input('num')]);
        }

        BatchSendLog::where('id', $data['id'])->update([
            'status' => 1
        ]);

        // 如果任务执行失败，可以使用下面的方式重新入队
        // $job->release($delay);
    }

}
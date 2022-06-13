<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Utils\Logger;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\DbConnection\Db;

class UserNewsPraiseLogService
{
    public function add($user_id = 0,$device_code = '',$user_news_id = 0)
    {
        if($user_id == 0){
            UtilsTool::exception("未登录，不能操作",302);
        }

        Db::beginTransaction();
        try {
            Db::table("mf_user_news_praise_log")->insert([
                'user_news_id'=>$user_news_id,
                'user_id'=>$user_id,
                'device_code'=>$device_code,
                'create_time'=>date("Y-m-d H:i:s"),
            ]);
            Db::table("mf_user_news")->where("id",$user_news_id)->increment('praise_total');
            Db::commit();
        }
        catch (\Exception $e)
        {
            Logger::init()->error($e->getMessage());
            Db::rollback();
            UtilsTool::exception("已经点赞成功哦");
        }

    }
}
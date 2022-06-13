<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\MfShop;
use Mwenju\Common\Model\MfUserNews;
use Mwenju\Common\Model\MfUserShopFollow;
use Mwenju\Common\Rpc\UserNewsServiceInterface;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\DbConnection\Db;
use Hyperf\RpcServer\Annotation\RpcService;

/**
 * Class UserNewsService
 * @package App\Common\Service
 * @RpcService(name="UserNewsService", protocol="jsonrpc", server="jsonrpc")
 */
class UserNewsService implements UserNewsServiceInterface
{
    public function getList($user_id = 0,$shop_id = 0,$page = 0,$limit = 30){

        if($shop_id > 0){
            $shop_ids = [$shop_id];
        }else{
            // 关注过所属有门店ID
            $shop_ids = MfUserShopFollow::where("user_id",$user_id)->pluck("shop_id")->toArray();
        }
        if(count($shop_ids) == 0) return [];
        $shop_ids[] = 0;
        $data = Db::table("mf_user_news as a")->selectRaw("a.*,c.face_img")
            ->leftJoin('mf_shop as b','b.id','=','a.shop_id')
            ->leftJoin('mf_user as c','c.id','=','b.user_id')
            ->whereIn("a.shop_id",$shop_ids)
            ->where("a.is_delete",0)
            ->orderBy("a.create_time","desc")
            ->limit($limit)
            ->offset($page)
            ->get()->each(function ($item,$index){
                if(!empty($item->img_urls))
                {
                    $item->img_urls = array_map('img_url',explode(",",$item->img_urls));
                }
                $item->face_img = img_url($item->face_img,'','http://img.mwenju.com/uploads/2022/0311/16469853316571.jpg');
            });
        $this->updateReadTotal($data);
        $this->checkPraise($user_id,$data);
        return $data;
    }

    public function getListByShopId($shop_id = 0,$page = 0,$limit = 30){
        $map[] = ['is_delete','=',0];
        if($shop_id > 0){
            $map[] = ['shop_id','=',$shop_id];
        }
        $list = MfUserNews::where($map)
            ->orderBy("create_time","desc")
            ->limit($limit)
            ->offset($page)
            ->get()->each(function ($item,$index){
                if(!empty($item->img_urls))
                {
                    $item->img_urls = array_map(["App\Common\Utils\UtilsTool","img_url"],explode(",",$item->img_urls));
                }
            });
        return $list;
    }

    public function create($shop_id = 0,$title = '',$content = '',$imgs = ""){
        ShopStoreApplyService::checkState($shop_id);
        $today_count = MfUserNews::where("shop_id",$shop_id)->where("create_time",'>=',date("Y-m-d 00:00:00"))->count();
        $USER_NEWS_PUSH_LIMIT = UtilsTool::config_value("USER_NEWS_PUSH_LIMIT");
        if($today_count > $USER_NEWS_PUSH_LIMIT){
            UtilsTool::exception("一天只能推送{$USER_NEWS_PUSH_LIMIT}次");
        }
        MfUserNews::insert([
            'shop_id'=>$shop_id,
            'source'=>MfShop::where("id",$shop_id)->value("cname"),
            'title'=>$title,
            'content'=>$content,
            'create_time'=>date("Y-m-d H:i:s"),
            'img_urls'=>$imgs
        ]);
    }

    private function updateReadTotal($data = [])
    {
        if(count($data) == 0) return;
        $ids = [];
        foreach ($data as $v)
        {
            $ids[] = $v->id;
        }
        if(count($ids) > 0){
            Db::table("mf_user_news")->whereIn("id",$ids)->increment("read_total");
        }
    }

    private function checkPraise(int $user_id,&$data)
    {
        $ids = [];
        foreach ($data as $v)
        {
            $ids[] = $v->id;
        }
        $dz = [];
        $res = Db::table("mf_user_news_praise_log")->whereIn("user_news_id",$ids)->where('user_id',$user_id)->get();
        if($res){
            foreach ($res as $v){
                $dz[$v->user_news_id] = 1;
            }
        }
        foreach ($data as $k=>$v) {
            $data[$k]->is_praise = isset($dz[$v->id])?1:0;
        }
    }
}
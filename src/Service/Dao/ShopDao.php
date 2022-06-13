<?php


namespace Mwenju\Common\Service\Dao;


use Mwenju\Common\Model\MfShop;
use Hyperf\Database\Model\Builder;

class ShopDao extends Base
{
    public function getList($param,$page,$limit)
    {
        $audit_status   = $param['audit_status']??"";
        $status         = $param['status']??"";
        $keyword        = $param['keyword']??"";
        $province_code  = $param['province_code']??"";
        $city_code      = $param['city_code']??"";
        $area_code      = $param['area_code']??"";
        $level_id       = $param['level_id']??"";

        $map = [];
        if (strlen($audit_status) > 0){
            $map[] = ['audit_status','=',$audit_status];
        }
        if (strlen($status) > 0){
            $map[] = ['status','=',$status];
        }
        if (strlen($province_code) > 0){
            $map[] = ['province_code','=',$province_code];
        }
        if (strlen($city_code) > 0){
            $map[] = ['city_code','=',$city_code];
        }
        if (strlen($area_code) > 0){
            $map[] = ['area_code','=',$area_code];
        }
        if (strlen($level_id) > 0){
            $map[] = ['level_id','=',$level_id];
        }
        $query = MfShop::where($map);
        if (!empty($keyword)){
            $query->where(function(Builder $q) use ($keyword){
                return $q->where("cname",'like',"%$keyword%")
                    ->orWhere("link_man","like","%$keyword%")
                    ->orWhere("link_mobile","like","%$keyword%")
                    ->orWhere("addr","like","%$keyword%")
                    ->orWhereHas("user",function (Builder $q1) use ($keyword){
                        $q1->where("mobile","like","%$keyword%")
                            ->orWhere("token","=","$keyword")
                        ;
                    });
            });
        }
        $query->orderBy("id","desc");
        return $this->pagination($query,$page,$limit);
    }

}
<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\MfShopAddress;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\DbConnection\Db;
use Hyperf\RpcServer\Annotation\RpcService;

/**
 * Class ShopAddressService
 * @package App\Common\Service
 * @RpcService(name="ShopAddressService",protocol="jsonrpc",server="jsonrpc")
 */
#[RpcService("ShopAddressService","jsonrpc","jsonrpc")]
class ShopAddressService extends BaseService
{

    public static function getAddresListByShopId($shop_id = 0)
    {
        return Db::table('mf_shop_address as a')
            ->selectRaw('a.*,a.id address_id')
            ->where('a.shop_id',$shop_id)
            ->get()->each(function ($item,$index){
                $item->addr_detail_str = ShopAddressService::getAddrDetailStrAttr($item);
                $item->province_name = ShopAddressService::getNameByCode($item->province_code);
                $item->city_name = ShopAddressService::getNameByCode($item->city_code);
                $item->area_name = ShopAddressService::getNameByCode($item->area_code);
            });
    }

    public static function getLastOrderAddress($shop_id = 0)
    {
        $sql = "SELECT * from mf_shop_order where id = (SELECT MAX(id) from mf_shop_order WHERE shop_id=?)";
        $res = Db::select($sql,[$shop_id]);
        return $res ? $res[0] : null;
    }

    public function getDefaultDepot($shop_id = 0)
    {
        $depot_id = 1;
        $res = Db::table("mf_shop_address")->where('shop_id',$shop_id)->first();
        if($res)
        {
            $pRow = Db::table('tb_province')->where('code',$res->province_code)->first();
            $depot_id = $pRow->depot_id?$pRow->depot_id:1;
        }
        return $depot_id;
    }

    public static function getAreaParent($area_code = '')
    {
        $citycode = Db::table('tb_area')->where('code',$area_code)->value('citycode');
        if(!$citycode) UtilsTool::exception('地区码有误');
        $provincecode = Db::table('tb_city')->where('code',$citycode)->value('provincecode');
        return [$area_code,$citycode,$provincecode];
    }
    public static function getAreaName($area_code = '')
    {
        $area = Db::table('tb_area')->where('code',$area_code)->first();
        $city = Db::table('tb_city')->where('code',$area->citycode)->first();
        $province = Db::table('tb_province')->where('code',$city->provincecode)->first();
        return $province->name.$city->name.$area->name;
    }
    public static function getAddrDetailStrAttr($data)
    {
        return	self::getNameByCode($data->province_code).
            self::getNameByCode($data->city_code).
            self::getNameByCode($data->area_code).
            $data->addr_detail;
    }

    public static function getNameByCode($code = ''){
        $redis = redis();
        $area = $redis->get('AREA_DATA');
        if(!$area)
        {
            $p = Db::table('tb_province')->get();
            $c = Db::table('tb_city')->get();
            $a = Db::table('tb_area')->get();

            foreach ($p as $v)
            {
                $area[$v->code] = $v->name;
            }
            foreach ($c as $v)
            {
                $area[$v->code] = $v->name;
            }
            foreach ($a as $v)
            {
                $area[$v->code] = $v->name;
            }

            $redis->set('AREA_DATA',json_encode($area));
        }
        else{
            $area = json_decode($area,true);
        }
        return isset($area[$code])?$area[$code]:'';
    }

    public function getDefaultAddress($param = [])
    {
        $id = $param['shop_id']??0;
        return MfShopAddress::where('shop_id',$id)
            ->orderBy('is_default','desc')->first();
    }

}
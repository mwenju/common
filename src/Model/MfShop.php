<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
use Hyperf\ModelCache\Cacheable;
use Hyperf\ModelCache\CacheableInterface;

/**
 * @property int $id 
 * @property int $old_id 
 * @property int $user_id 
 * @property string $cname 
 * @property string $addr 
 * @property string $link_man 
 * @property string $link_mobile 
 * @property string $face_img 
 * @property string $desction 
 * @property string $shop_tags 
 * @property string $trade_types 
 * @property int $status 
 * @property int $is_show 
 * @property int $sort_num 
 * @property string $home_url 
 * @property string $pay_pwd 
 * @property string $lng_lat 
 * @property int $fendan_admin_id 
 * @property string $logistics_cname 
 * @property string $logistics_send_addr 
 * @property string $logistics_type 
 * @property int $level_id 
 * @property string $level_last_update_time 
 * @property int $is_forbid 
 * @property int $invite_shop_id 
 * @property string $business_license_url 
 * @property string $create_time 
 * @property string $audit_remark 
 * @property string $audit_time 
 * @property int $audit_user_id 
 * @property string $province_code 
 * @property string $city_code 
 * @property string $area_code 
 */
class MfShop extends Model implements CacheableInterface
{
    use Cacheable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_shop';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected array $fillable = [];

    public function account()
    {
        return $this->hasOne("App\\Common\\Model\\MfShopAccount","shop_id","id");
    }
    public function user()
    {
        return $this->hasOne("App\\Common\\Model\\MfUser","id","user_id");
    }
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected array $casts = ['id' => 'integer', 'old_id' => 'integer', 'user_id' => 'integer', 'status' => 'integer', 'is_show' => 'integer', 'sort_num' => 'integer', 'fendan_admin_id' => 'integer', 'level_id' => 'integer', 'is_forbid' => 'integer', 'invite_shop_id' => 'integer', 'audit_user_id' => 'integer'];
}
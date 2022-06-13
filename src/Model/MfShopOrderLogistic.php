<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $order_id 
 * @property int $send_num 
 * @property int $total_num 
 * @property string $logistics_comp_cname 
 * @property string $logistics_code 
 * @property string $addr 
 * @property string $logistics_send_addr 
 * @property string $consignee 
 * @property string $mobile 
 * @property string $logistics_money 
 * @property int $pay_type 
 * @property int $add_user_id 
 * @property int $admin_id 
 * @property int $add_time 
 * @property string $send_beizhu 
 * @property int $last_do_time 
 * @property string $get_beizhu 
 * @property int $get_user_id 
 * @property int $get_time 
 * @property int $get_num 
 * @property int $is_peihuo 
 * @property int $print_hits 
 * @property string $create_time 
 * @property string $delivery_time 
 * @property int $addr_type 
 * @property string $subsidy_money 
 */
class MfShopOrderLogistic extends Model
{
    public function order()
    {
        return $this->belongsTo("App\Common\Model\MfShopOrder",'order_id','id');
    }
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_shop_order_logistics';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected array $fillable = [];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected array $casts = ['id' => 'integer', 'order_id' => 'integer', 'send_num' => 'integer', 'total_num' => 'integer', 'pay_type' => 'integer', 'add_user_id' => 'integer', 'admin_id' => 'integer', 'add_time' => 'integer', 'last_do_time' => 'integer', 'get_user_id' => 'integer', 'get_time' => 'integer', 'get_num' => 'integer', 'is_peihuo' => 'integer', 'print_hits' => 'integer', 'addr_type' => 'integer'];
}
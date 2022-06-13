<?php

namespace Mwenju\Common\Model;

/**
 * @property int $id
 * @property int $shop_id
 * @property int $supplier_id
 * @property string $order_code
 * @property string $shop_name
 * @property string $supplier_name
 * @property string $paid_price
 * @property string $total_price
 * @property string $start_time
 * @property string $end_time
 * @property int $audit_status
 * @property string $create_time
 * @property string $create_by
 * @property int $create_by_user_id
 * @property string $sale_total_price
 * @property string $return_total_price
 * @property string $credit_total_price
 * @property string $service_fee_price
 * @property string $freight_total_price
 * @property string $bond_rate
 * @property string $service_fee_rate
 * @property string $bid_total_price
 */
class YunSettlement extends Model
{
    public ?string $table = "yun_settlement";
}
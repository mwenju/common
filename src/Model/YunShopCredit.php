<?php

namespace Mwenju\Common\Model;

/**
 * @property int $id
 * @property int $shop_id
 * @property int $supplier_id
 * @property string $credit_limit_money
 * @property string $enable_money
 * @property string $start_time
 * @property string $end_time
 * @property string $remarks
 * @property string $files
 * @property string $create_by
 * @property string $last_update_time
 * @property string $last_update_by
 * @property string $create_time
 * @property int $audit_status
 * @property int $payer
 */
class YunShopCredit extends Model
{
    protected ?string $table = 'yun_shop_credit';
}
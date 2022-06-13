<?php

namespace Mwenju\Common\Model;

/**
 * @property int $id
 * @property int $shop_id
 * @property int $supplier_id
 * @property string $why_info
 * @property string $create_by
 * @property string $create_time
 * @property string $add_num
 * @property int $add_type
 */
class YunShopCreditLog extends Model
{
    protected ?string $table = 'yun_shop_credit_log';
}
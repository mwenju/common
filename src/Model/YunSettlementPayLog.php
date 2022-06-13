<?php

namespace Mwenju\Common\Model;

/**
 * @property int $id
 * @property int $settlement_id
 * @property string $paid_price
 * @property string $create_time
 * @property string $create_by
 * @property string $remarks
 * @property string $img_urls
 * @property int $admin_id
 */
class YunSettlementPayLog extends Model
{
    public ?string $table = "yun_settlement_pay_log";
}
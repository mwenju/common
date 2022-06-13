<?php


namespace Mwenju\Common\Utils;


use Mwenju\Common\Service\AliSmsService;
use Mwenju\Common\Service\CouponService;
use Hyperf\AsyncQueue\Annotation\AsyncQueueMessage;

class Sms
{

    #[AsyncQueueMessage]
    public static function send($mobile='',$template_id='',$data=[],$ext=[])
    {
        return AliSmsService::send($mobile,$template_id,$data,$ext);
    }
}
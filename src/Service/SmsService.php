<?php


namespace Mwenju\Common\Service;

use Mwenju\Common\Utils\Sms;
use Hyperf\RpcServer\Annotation\RpcService;

/**
 * Class SmsService
 * @package App\Common\Service
 * @RpcService(name="SmsService",protocol="jsonrpc",server="jsonrpc")
 */
#[RpcService("SmsService","jsonrpc","jsonrpc")]
class SmsService
{
    public function send($mobile='',$template_id='',$data=[],$ext=[])
    {
        return Sms::send($mobile,$template_id,$data,$ext);
    }
}
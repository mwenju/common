<?php


namespace Mwenju\Common\Utils;


use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Utils\ApplicationContext;
use Hyperf\HttpServer\Contract\ResponseInterface;

class JsonResponse
{
    public static function ajaxSuccess($data = [],$msg = '提交成功')
    {
        return ApplicationContext::getContainer()->get(ResponseInterface::class)->json([
            'err_code'=>0,
            'msg'=>$msg,
            'data'=>$data
        ]);
    }

    public static function ajaxError($msg = '',$code = 300,$data = [])
    {
        return ApplicationContext::getContainer()->get(ResponseInterface::class)
            ->json([
            'err_code'=>$code,
            'msg'=>$msg,
            'data'=>$data
        ]);
    }
}
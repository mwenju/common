<?php


namespace Mwenju\Common\Event;


use Mwenju\Common\Pojo\Param;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Utils\ApplicationContext;

class AfterLogin
{

    public $token;
    public $device_code;
    public $device_type;
    public $current_version;
    public $client_id;
    public $login_ip;
    public $user_agent;
    public $uri;
    public $mobile;
    public $user_id;
    public $param;
    public function __construct($param = [])
    {
        if (!empty($param)){
            foreach ($param as $k=>$v)
            {
                $this->$k = $v;
            }
        }

        $request = ApplicationContext::getContainer()->get(RequestInterface::class);
        $header = $request->getHeaders();
        $this->user_agent = isset($header['user-agent'][0])?$header['user-agent'][0]:'';
        $this->param = json_encode($param);
        $this->uri = $request->getUri();
        $this->login_ip = get_client_ip();
    }
}
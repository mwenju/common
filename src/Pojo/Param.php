<?php


namespace Mwenju\Common\Pojo;


use Mwenju\Common\Utils\Logger;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Utils\ApplicationContext;

class Param
{
    private $token;
    private $device_code;
    private $device_type;
    private $current_version;
    private $client_id;
    private $login_ip;
    private $user_agent;
    private $uri;
    private $mobile;
    private $user_id;
    private $param;

    public function __toString()
    {
        // TODO: Implement __toString() method.
        return json_encode(get_class_vars(Param::class));
    }


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
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token): void
    {
        $this->token = $token;
    }

    /**
     * @return mixed
     */
    public function getDeviceCode()
    {
        return $this->device_code;
    }

    /**
     * @param mixed $device_code
     */
    public function setDeviceCode($device_code): void
    {
        $this->device_code = $device_code;
    }

    /**
     * @return mixed
     */
    public function getDeviceType()
    {
        return $this->device_type;
    }

    /**
     * @param mixed $device_type
     */
    public function setDeviceType($device_type): void
    {
        $this->device_type = $device_type;
    }

    /**
     * @return mixed
     */
    public function getCurrentVersion()
    {
        return $this->current_version;
    }

    /**
     * @param mixed $current_version
     */
    public function setCurrentVersion($current_version): void
    {
        $this->current_version = $current_version;
    }

    /**
     * @return mixed
     */
    public function getClientId()
    {
        return $this->client_id;
    }

    /**
     * @param mixed $client_id
     */
    public function setClientId($client_id): void
    {
        $this->client_id = $client_id;
    }

    /**
     * @return mixed
     */
    public function getLoginIp()
    {
        return get_client_ip();
    }

    /**
     * @param mixed $login_ip
     */
    public function setLoginIp($login_ip): void
    {
        $this->login_ip = $login_ip;
    }

    /**
     * @return mixed
     */
    public function getUserAgent()
    {
        return $this->user_agent;
    }

    /**
     * @param mixed $user_agent
     */
    public function setUserAgent($user_agent): void
    {
        $this->user_agent = $user_agent;
    }

    /**
     * @return mixed
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param mixed $uri
     */
    public function setUri($uri): void
    {
        $this->uri = $uri;
    }

    /**
     * @return mixed
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param mixed $mobile
     */
    public function setMobile($mobile): void
    {
        $this->mobile = $mobile;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @param mixed $user_id
     */
    public function setUserId($user_id): void
    {
        $this->user_id = $user_id;
    }

    /**
     * @return mixed
     */
    public function getParam()
    {
        return $this->param;
    }

    /**
     * @param mixed $param
     */
    public function setParam($param): void
    {
        $this->param = $param;
    }


}
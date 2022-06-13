<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Event\AfterLogin;
use Mwenju\Common\Model\MfAdmin;
use Mwenju\Common\Model\MfShop;
use Mwenju\Common\Model\MfShopAccount;
use Mwenju\Common\Model\MfShopAddress;
use Mwenju\Common\Model\MfUser;
use Mwenju\Common\Model\MfUserDetail;
use Mwenju\Common\Model\MfUserLoginLog;
use Mwenju\Common\Pojo\Param;
use Mwenju\Common\Pojo\User;
use Mwenju\Common\Service\Formatter\UserFormatter;
use Mwenju\Common\Utils\JsonResponse;
use Mwenju\Common\Utils\Logger;
use Mwenju\Common\Utils\RedisTool;
use Mwenju\Common\Utils\Sms;
use Mwenju\Common\Utils\UtilsTool;
use Mwenju\Common\Utils\UtilsUserLogin;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Hyperf\RpcServer\Annotation\RpcService;
use function Swoole\Coroutine\Http\get;

/**
 * Class UserService
 * @package App\Common\Service
 * @RpcService(name="UserService",protocol="jsonrpc",server="jsonrpc")
 */
#[RpcService("UserService","jsonrpc","jsonrpc")]
class UserService
{
    private $token;

    #[Inject]
    protected ValidatorFactoryInterface $validationFactory;

    #[Inject]
    private EventDispatcherInterface $eventDispatcher;

    #[Inject]
    private UserFormatter $userFormatter;

    public function setToken($token = '')
    {
        $this->token = $token;
    }

    public function login($input){
        $mobile     = $input['mobile'];
        $code       = $input['code'];
        $shop_id    = isset($input['shop_id'])?intval($input['shop_id']):0;
        $this->checkRegCode($mobile,$code);

        if(!empty($input['encryptedData'])){
            $wxService = new WxService($input['device_type']);
            $wxService->setAppId(UtilsTool::config_value("api.uwx_miniprogram.appid"));
            $wxService->setAppsecret(UtilsTool::config_value("api.uwx_miniprogram.appsecret"));
            $userData = $wxService->decryptData($input['openid'],$input['encryptedData'],$input['iv']);
            Logger::init()->info('WX_USER_DATA:',$userData);
        }

        $userInfo = MfUser::where("mobile",$mobile)->first();
        if($userInfo->is_delete > 0){
            UtilsTool::exception("该账号异常，联系客服");
        }
        if(!$userInfo){
            $userInfo =  $this->reg($input);

        }else
        {

            if(empty($userInfo->token)){
                $userInfo->token = md5(UtilsTool::guid());
            }
            $userInfo->last_ip = get_client_ip();
            $userInfo->save();
            redis()->del('SEND_SMS_TIMES_CODE_'.$mobile);

            $afterLogin = new AfterLogin($input);
            $afterLogin->user_id = $userInfo->id;
            $afterLogin->token = $userInfo->token;
            $this->eventDispatcher->dispatch($afterLogin);
        }
        $token = $userInfo->token;
        $user_id = $userInfo->id;
        return [$token,$user_id];
    }

    public function checkReg($input = [])
    {
        $validator = $this->validationFactory->make(
            $input,
            [
                'mobile' => 'numeric|required|digits:11',
                'link_mobile' => 'numeric|required|digits:11',
                'shop_name' => 'required',
                'link_name' => 'required',
                'area_code' => 'required',
                'addr_detail' => 'required',
                'business_license_url' => 'required',
            ],
            [
                'mobile.required' => '手机号必填',
                'mobile.numeric'  => '手机号格式有误',
                'mobile.digits'  => '手机号格式有误',
                'link_mobile.required' => '请填写联系电话',
                'link_mobile.numeric' => '联系人手机号格式有误',
                'link_mobile.digits' => '联系人手机号格式有误',
                'shop_name.required' => '店铺名称必填',
                'link_name.required' => '请填写联系人',
                'area_code.required' => '请选择地区',
                'addr_detail.required' => '请填写详细地址',
                'business_license_url.required' => '请上传营业执照',
            ]
        );

        if($validator->fails())
        {
            UtilsTool::exception($validator->errors()->first());
        }
//        $this->checkRegCode($input['mobile'],$input['code'],false);
    }

    public function addShop($input)
    {
        try {
            $this->checkReg($input);
            if(MfUser::where("mobile",$input['mobile'])->count()>0){
                UtilsTool::exception("手机号已注册");
            }
            $user_id = $this->reg($input);
            $shop_id = MfShop::insertGetId([
                'user_id'=>$user_id,
                'cname'=>$input['shop_name'],
                'link_man'=>$input['link_name'],
                'link_mobile'=>$input['link_mobile'],
                'business_license_url'=>$input['business_license_url'],
                'invite_shop_id'=>0,
                'addr'=>$input['addr_detail'],
                'area_code'=>$input['area_code']??'',
                'city_code'=>$input['city_code']??'',
                'province_code'=>$input['province_code']??"",
                'logistics_cname'=>$input['logistics_cname']??"",
                'logistics_send_addr'=>$input['logistics_send_addr']??"",
                'create_time'=>date("Y-m-d H:i:s")
            ]);
            MfShopAccount::insert([
                'user_id'=>$user_id,
                'shop_id'=>$shop_id
            ]);
            // 创建收获地址
            MfShopAddress::insert([
                'shop_id'=>$shop_id,
                'user_id'=>$user_id,
                'area_code'=>$input['area_code']??'',
                'city_code'=>$input['city_code']??'',
                'province_code'=>$input['province_code']??"",
                'is_default'=>1,
                'link_mobile'=>$input['link_mobile'],
                'addr_detail'=>$input['addr_detail'],
                'link_name'=>$input['link_name']
            ]);
        }catch (\Exception $e)
        {
            return arrayError($e->getMessage());
        }
        return arraySuccess("创建成功");;
    }

    public function reg($input)
    {
        $user_id = MfUser::insertGetId([
            'mobile'=>$input['mobile'],
            'nick_name'=>!empty($input['nick_name'])?trim($input['nick_name']):'',
            'pwd'=>!empty($input['pwd'])?UtilsTool::md7($input['pwd']):"",
            'wx_openid'=>!empty($input['wx_openid'])?$input['wx_openid']:'',
            'device_type'=>!empty($input['device_type'])?$input['device_type']:"",
            'last_client_id'=>!empty($input['client_id'])?$input['client_id']:"",
            'reg_ip'=> $input['reg_ip'] ?? get_client_ip(),
            'token'=>md5(UtilsTool::guid()),
            'create_time'=>date("Y-m-d H:i:s"),
            'face_img'=>'/face/'.rand(1,80).'.jpg'
        ]);

        MfUserDetail::insert([
            'user_id'=>$user_id,
            'sex'=>!isset($input['sex'])?0:$input['sex'],
            'school'=>!empty($input['school'])?$input['school']:"",
            'real_name'=>!empty($input['real_name'])?$input['real_name']:"",
        ]);

        return $user_id;
    }

    /**
     * @param array $input
     * {
    "openid": "OPENID",
    "nickname": NICKNAME,
    "sex": 1,
    "province":"PROVINCE",
    "city":"CITY",
    "country":"COUNTRY",
    "headimgurl":"https://thirdwx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/46",
    "privilege":[ "PRIVILEGE1" "PRIVILEGE2"     ],
    "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
    }
     */
    public function wxAuth($input = [])
    {
        $userInfo = MfUser::where("mobile",$input['mobile'])->first();
        if($userInfo){
            if(UtilsTool::config_value('SINGLE_LOGIN') > 0 || empty($userInfo->token))
            {
                $userInfo->token = md5(time().UtilsTool::get_rand(6)); // 是否支持单点登录
                if(!empty($input["client_id"])){
                    $userInfo->last_client_id = $input["client_id"];
                }
                $userInfo->save();
            }
            $token = $userInfo->token;
            $user_id = $userInfo->id;
        }else
        {
            $token = md5(time().UtilsTool::get_rand(6));
            try {
                Db::beginTransaction();
                $user_id = Db::table("mf_user")->insertGetId([
                    'mobile'=>$input['mobile'],
                    'nick_name'=>!empty($input['nickname'])?trim($input['nickname']):'匿名用户',
                    'pwd'=>!empty($input['pwd'])?UtilsTool::md7($input['pwd']):"",
                    'wx_openid'=>!empty($input['openid'])?$input['openid']:'',
                    'wx_unionid'=>!empty($input['unionid'])?$input['unionid']:'',
                    'device_type'=>!empty($input['device_type'])?$input['device_type']:"",
                    'last_client_id'=>!empty($input['openid'])?$input['openid']:"",
                    'face_img'=>!empty($input['headimgurl'])?$input['headimgurl']:"face/".rand(1,80).".jpg",
                    'reg_ip'=>get_client_ip(),
                    'last_ip'=>get_client_ip(),
                    'token'=>$token,
                    'create_time'=>date("Y-m-d H:i:s"),
                ]);
                Db::table("mf_user_detail")->insert([
                    'user_id'=>$user_id,
                    'sex'=>!empty($input['sex'])?$input['sex']:0,
                ]);
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                UtilsTool::exception($e->getMessage());
            }
        }
        return [$token,$user_id];

    }

    public function sendSmsCode($input = [],$isReg = false)
    {
        $validator = $this->validationFactory->make(
            $input,
            [
//                'device_code' => 'required',
                'mobile' => 'required|numeric|digits:11',
            ],
            [
//                'device_code.required' => '设备识别异常',
                'mobile.required' => '手机号必填',
                'mobile.digits' => '手机号格式有误',
                'mobile.numeric' => '手机号格式有误',
            ]
        );

        if($validator->fails())
        {
            UtilsTool::exception($validator->errors()->first());
        }
        $device_code    = $input['device_code'];
        $mobile         = $input['mobile'];
        $redis          = redis();
        if($redis->get('SEND_SMS_TIMES_'.$mobile))
        {
            UtilsTool::exception('操作频繁，请稍后操作');
        }
        // 相同IP一小时限制10条
        if($redis->get(get_client_ip()) >= UtilsTool::config_value('api.sms_ip_limit'))
        {
            UtilsTool::exception('当前设备IP操作频繁，请稍后操作');
        }
        // 相同IP一小时限制10条
        if (!empty($input['device_type']) && $input['device_type'] != 'miniprogram') {
            if ($redis->get($device_code) >= UtilsTool::config_value('api.sms_ip_limit')) {
                UtilsTool::exception('当前设备操作频繁，请稍后操作');
            }
        }
        // 检查手机号
        $check_user = MfUser::where('mobile',$mobile)->first();

        if($check_user && $check_user->is_delete>0){
            UtilsTool::exception('该号码已注册过且账号异常，联系客服');
        }
        if(!$isReg && !$check_user)
        {
            UtilsTool::exception('该手机号还未注册店铺');
        }
        if($isReg && $check_user)
        {
            $check_shop = MfShop::where("user_id",$check_user->id)->first();
            if($check_shop)
            {
                if($check_shop->status == 0 )
                {
                    UtilsTool::exception('正在审核中，清耐心等待');
                }
                UtilsTool::exception('该手机号已注册不能重复注册');
            }
        }

        if($redis->get('SEND_SMS_TIMES_CODE_'.$mobile))
        {
            $code = $redis->get('SEND_SMS_TIMES_CODE_'.$mobile);
        }
        else
        {
            $code = UtilsTool::get_rand(4);
        }
        if(strpos(UtilsTool::config_value("SUPER_MOBILE"),$mobile) !== false)
        {
            $code = '8888'; // TEST
        }
        else
        {
            Sms::send($mobile,$isReg?'REG_CODE':'LOGIN_CODE',['code'=>$code]);
        }

        $redis->setex('SEND_SMS_TIMES_'.$mobile,3,$code);

        $redis->setex('SEND_SMS_TIMES_CODE_'.$mobile,(int)UtilsTool::config_value("api.sms_code_life_time"),$code);

        if($redis->get(get_client_ip()))
        {
            $redis->incr(get_client_ip());
        }
        else
        {
            $redis->setex(get_client_ip(),3600,1);
        }
        if (!empty($input['device_type']) && $input['device_type'] != 'miniprogram'){
            if($redis->get($device_code))
            {
                $redis->incr($device_code);
            }
            else
            {
                $redis->setex($device_code,3600,1);
            }
        }

    }

    public function checkRegCode($mobile = '',$code = '',$del = false)
    {

        if(empty($code))
        {
            UtilsTool::exception('验证码不能为空');
        }

        if(RedisTool::init()->get('SEND_SMS_TIMES_CODE_'.$mobile))
        {
            $cache_code = RedisTool::init()->get('SEND_SMS_TIMES_CODE_'.$mobile);
        }
        else
        {
            UtilsTool::exception('验证码已经过期');
        }

        if($cache_code != $code)
        {
            UtilsTool::exception('验证码有误');
        }
        if ($del) RedisTool::init()->del('SEND_SMS_TIMES_CODE_'.$mobile);
    }

    public function getInfo($user_id = 0){
        $userInfo = MfUser::find($user_id);
        if(is_null($userInfo)) return [];
        $userInfo = [
            'user_id'=>$userInfo->id,
            'nick_name'=>is_null($userInfo->nick_name)?"匿名用户":$userInfo->nick_name,
            'token'=>$userInfo->token,
            'mobile'=>$userInfo->mobile,
            'create_time'=>$userInfo->create_time,
            'http_face_img'=>UtilsTool::img_url($userInfo->face_img),
            'face_img'=>$userInfo->face_img
        ];
        $info = MfUserDetail::where('user_id',$user_id)->first();
        if(is_null($info)){
            MfUserDetail::insert([
                'user_id'=>$user_id
            ]);
            $info = [];
        }else{
            $info = [
                'real_name'=>is_null($info->real_name)?'匿名用户':$info->real_name,
                'sex'=>$info->sex,
                'school'=>is_null($info->school)?"":$info->school,
                'birthday'=>$info->birthday>0?date("Y-m-d",strtotime($info->birthday)):""
            ];
        }
        return array_merge($userInfo,$info);
    }

    public function update($user_id = 0,$input = []){

        MfUser::where("id",$user_id)->update([
            'face_img'=>$input['face_img'],
            'nick_name'=>$input['nick_name'],
        ]);

        if(!empty($input['birthday'])){
            $input['birthday'] = date("Y-m-d H:i:s",strtotime($input['birthday']));
        }
        MfUserDetail::where('user_id',$user_id)->update([
            'birthday'=>$input['birthday'],
            'school'=>$input['school'],
            'sex'=>$input['sex'],
            'real_name'=>$input['real_name']
        ]);
    }

    /**
     * 注销账号
     * @param int $user_id
     */
    public function delete($user_id = 0)
    {
        $user = MfUser::find($user_id);
        RedisTool::init()->del($user->token);
        $user->is_delete = 1;
        $user->token = null;
        $user->save();
    }

    /**
     * 恢复账号
     * @param int $user_id
     */
    public function recovery($user_id = 0)
    {
        MfUser::where("id",$user_id)->update([
            'is_delete'=>0,
        ]);
    }

    public function loginInfo($token):User
    {
        $userLogin = new User();
        if(!empty($token))
        {
            $tokenData = UtilsTool::redis()->get("TOKEN_".$token);
            if($tokenData){
                $userLogin = unserialize($tokenData);
            }
            else
            {
                $mfuser = MfUser::where("token",$token)->first();
                if ($mfuser){
                    $userLogin = $this->userFormatter->base($mfuser);
                    UtilsTool::redis()->set("TOKEN_".$token,serialize($userLogin));
                }
            }
        }
        return $userLogin;
    }

    public function getArea($tokenInfo = '')
    {
        $loginInfo = $tokenInfo?$tokenInfo:$this->loginInfo();
        if(!$loginInfo) return [0,0,0];

        return [$loginInfo->getProvinceCode(),$loginInfo->getCityCode(),$loginInfo->getAreaCode()];
    }

    /**
     * 验证码自主注册
     * @param string $mobile
     * @param string $code
     * @param int $shop_id
     */
    public function regBySmsCode($mobile = '',$code = '',$shop_id = 0)
    {
        $this->checkRegCode($mobile,$code);

        $user = MfUser::where("mobile",$mobile)->first();

        if (!$user)
        {
            $user = $this->reg(['mobile'=>$mobile]);
        }
        di(UserCardService::class)->autoBind($shop_id,$user->id,$mobile);
    }

    public function loginLog(AfterLogin $param)
    {
        $log = new MfUserLoginLog();
        $log->user_agent    = $param->user_agent;
        $log->login_ip      = $param->login_ip;
        $log->mobile        = $param->mobile;
        $log->device_code   = $param->device_code;
        $log->device_type   = $param->device_type;
        $log->token         = $param->token;
        $log->client_id     = $param->client_id;
        $log->user_id     = $param->user_id;
        $log->param         = $param->param;
        $log->create_time   = date("Y-m-d H:i:s");
        return $log->save();
    }

    /**
     * 忘记密码重置密码
     * @param string $mobile
     * @param string $new_pwd
     * @throws \Hyperf\Di\Exception\Exception
     */
    public function resetPwdByMobile($mobile = '', $new_pwd = '')
    {
        if (empty($new_pwd)) {
            UtilsTool::exception("密码不能为空");
        }
        $user = MfUser::where("mobile", $mobile)->first();
        if (!$user) {
            UtilsTool::exception("手机号不存在");
        }
        $user->pwd = md7($new_pwd);
        $user->save();
    }

    /**
     * 修改密码
     * @param int $user_id 用户ID
     * @param string $new_pwd
     * @throws \Hyperf\Di\Exception\Exception
     */
    public function changePwdByUserId($user_id = 0, $new_pwd = '')
    {
        if (empty($new_pwd)) {
            UtilsTool::exception("密码不能为空");
        }
        $user = MfUser::find($user_id);
        if (!$user) {
            UtilsTool::exception("手机号不存在");
        }
        $user->pwd = md7($new_pwd);
        $user->save();
    }
}
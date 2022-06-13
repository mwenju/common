<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Service\Dao\BrandDao;
use Hyperf\Di\Annotation\Inject;
use Hyperf\RpcServer\Annotation\RpcService;

/**
 * Class BrandService
 * @package App\Common\Service
 * @RpcService(name="BrandService",protocol="jsonrpc",server="jsonrpc")
 */
#[RpcService("BrandService","jsonrpc","jsonrpc")]
class BrandService extends BaseService
{

    #[Inject]
    private BrandDao $brandDao;

    public function create($param = [])
    {
        $this->brandDao->create($param);
        return arraySuccess("创建成功");
    }

    public function update($id,$param = [])
    {
        $this->brandDao->update($id,$param);
        return arraySuccess("更新成功");
    }
}
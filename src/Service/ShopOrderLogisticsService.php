<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\MfShopOrder;
use Mwenju\Common\Service\Dao\ShopOrderLogisticsDao;
use Mwenju\Common\Service\Formatter\ShopOrderLogisticsFormatter;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\RpcServer\Annotation\RpcService;

/**
 * 授信记录
 * Class ShopOrderLogisticsService
 * @package App\Common\Service
 * @RpcService(name="ShopOrderLogisticsService",protocol="jsonrpc",server="jsonrpc")
 */
#[RpcService("ShopOrderLogisticsService","jsonrpc","jsonrpc")]
class ShopOrderLogisticsService extends BaseService
{

    #[Inject]
    private ShopOrderLogisticsDao $shopOrderLogisticsDao;

    #[Inject]
    private ShopOrderLogisticsFormatter $formatter;

    public function getList($param = [])
    {
        [$total,$list] = $this->shopOrderLogisticsDao->getList($param);
        return ['total'=>$total,'rows'=>$this->formatter->formatList($list)];
    }
    public function getInfo($param = [])
    {
        $info = $this->shopOrderLogisticsDao->getInfo($param['id']??0);
        return $this->formatter->base($info);
    }
    public function selectList()
    {
        return $this->shopOrderLogisticsDao->compList();
    }

    public function submit($param = [])
    {
        $param['is_peihuo'] = 0;
        $param['delivery_time'] = date("Y-m-d H:i:s");
        $model = $this->shopOrderLogisticsDao->update($param);
        $orderModel = MfShopOrder::find($model->order_id);
        $orderModel->freight_price = $model->logistics_money;
        $orderModel->save();
        return arraySuccess("提交成功");
    }
}
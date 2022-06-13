<?php


namespace Mwenju\Common\Service;

use Mwenju\Common\Service\Dao\StockTakeOrderDao;
use Mwenju\Common\Service\Formatter\StockTakeOrderFormatter;
use Hyperf\Di\Annotation\Inject;
use Hyperf\RpcServer\Annotation\RpcService;

#[RpcService("StockTakeOrderService","jsonrpc","jsonrpc")]
class StockTakeOrderService extends BaseService
{
    #[Inject]
    private StockTakeOrderDao $stockTakeOrderDao;
    #[Inject]
    private StockTakeOrderFormatter $formatter;

    public function getList(array $param)
    {
        list($page,$limit) = $this->pageFmt($param);
        [$total,$list] = $this->stockTakeOrderDao->find($param,$page,$limit);

        return ['total'=>$total,'rows'=>$this->formatter->formatList($list)];
    }
}
<?php

namespace Mwenju\Common\Service;

use Mwenju\Common\Service\Dao\ProfitLossDao;
use Mwenju\Common\Service\Formatter\ProfitLossFormatter;
use Hyperf\Di\Annotation\Inject;
use Hyperf\RpcServer\Annotation\RpcService;

#[RpcService("ProfitLossService","jsonrpc","jsonrpc")]
class ProfitLossService extends BaseService
{
    #[Inject]
    private ProfitLossDao $profitLossDao;

    #[Inject]
    private ProfitLossFormatter $formatter;

    public function getList($param = []): array
    {
        list($page,$limit) = $this->pageFmt($param);
        [$total,$list] = $this->profitLossDao->find($param,$page,$limit);

        return ['total'=>$total,'rows'=>$this->formatter->formatList($list)];
    }
}
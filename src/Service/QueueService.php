<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Event\AfterLogin;
use Mwenju\Common\Model\MfUserLoginLog;
use Mwenju\Common\Pojo\Param;
use Mwenju\Common\Utils\Logger;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\AsyncQueue\Annotation\AsyncQueueMessage;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Task\Annotation\Task;
use Hyperf\Utils\Coroutine;

class QueueService
{
    #[Inject]
    private EsService $esService;

    #[AsyncQueueMessage]
    public function updateIndex($param)
    {
        $this->esService->index($param);
        UtilsTool::logger("queue")->notice("更新索引",$param);
    }

    #[AsyncQueueMessage]
    public function delDoc($param)
    {
        $this->esService->delDoc($param);
        UtilsTool::logger("queue")->notice("删除索引文档",$param);
    }

    #[AsyncQueueMessage]
    public function deleteIndex()
    {
        UtilsTool::logger("queue")->notice("删除索引");
        return $this->esService->delete();
    }

    #[AsyncQueueMessage]
    public function createIndex()
    {
        UtilsTool::logger("queue")->notice("新建索引");
        return $this->esService->create();
    }

    #[AsyncQueueMessage]
    public function updateSearchWord($keyword = '',$shop_id = 0)
    {
        SearchService::updateSearchWord($keyword,$shop_id);
        UtilsTool::logger("queue")->notice("更新搜索关键词:",[$keyword]);
    }

    #[AsyncQueueMessage]
    public static function bindByOrder($order_id)
    {
        (new CouponService())->bindByOrder($order_id);
    }

}
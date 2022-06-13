<?php


namespace Mwenju\Common\Service;


abstract class BaseService
{
    public function pageFmt($param = [])
    {
        $page = $param['page']??1;
        $rows = $param['rows']??10;;
        $page = $page <= 0? 1: intval($page);
        $rows = $rows <= 0?10:intval($rows);
        $pageStart  = ($page - 1) * $rows;
        return [$pageStart,$rows];
    }
}
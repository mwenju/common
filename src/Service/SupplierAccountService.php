<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\TbProductStockLog;
use Mwenju\Common\Model\TbSupplier;
use Mwenju\Common\Model\TbSupplierAccount;
use Mwenju\Common\Model\TbSupplierAccountLog;
use Hyperf\Database\Model\Model;
use Hyperf\DbConnection\Db;
use Mwenju\Common\Utils\Logger;
use Hyperf\Di\Exception\Exception;

class SupplierAccountService
{
    /**
     * do_type_id:变动类型，1-入库，2-出库，3-盘盈，4-盘亏，5-退货,6-报损，7-厂家退货
     */
    public function updateAccount()
    {
        Db::table("tb_product_stock_log as a")->selectRaw("a.*,b.product_name,b.product_unit")
            ->leftJoin("tb_product as b","a.product_id",'=','b.id')
            ->where("a.is_ok",0)->orderBy("a.id")->chunkById(50,function ($rows){
            foreach ($rows as $row)
            {
                $this->changeAccountByStock($row);
            }
        });
    }

    /**
     * 根据库存更新供应商资金
     * do_type:变动类型，1-入库，2-出库，3-盘盈，4-盘亏，5-退货，6-厂家退货,7-退货入库
     * @param $row
     * @throws Exception
     */
    public function changeAccountByStock($row)
    {
        if (!is_object($row)){

            $stock_log_id = $row;

            $row = TbProductStockLog::where("id",$stock_log_id)->where("is_ok",0)->first();

        }else{

            $stock_log_id = $row->id;
        }
        // 云仓业务 不更新账号资金
        $is_new = TbSupplier::findFromCache($row->supplier_id)->is_new;
        if ($is_new == 2){
            Logger::init()->info("云仓厂家资金更新跳过：".$row->supplier_id);
            Db::table("tb_product_stock_log")->where("id",$stock_log_id)->update(["is_ok"=>1]);
            return;
        }

        Db::beginTransaction();

        try {

            $sa = Db::table("tb_supplier_account")->where("supplier_id",$row->supplier_id)->first();

            if(!$sa) {
                Db::table("tb_supplier_account")->insert(['supplier_id' => $row->supplier_id]);

                $sa = Db::table("tb_supplier_account")->where("supplier_id",$row->supplier_id)->first();
            }
            $why_info = "";

            $add_money = abs($row->add_num * $row->bid_price);

            $enable_money = $sa->enable_money?$sa->enable_money:0;

            $all_money = $sa->all_money?$sa->all_money:0;

            $all_out_money = $sa->all_out_money?$sa->all_out_money:0;

            $i = 1;

            if($row->do_type == 1 || $row->do_type == 7)
            {

                Db::update("update tb_supplier_account set all_money = all_money + ? where supplier_id=?",[$add_money,$row->supplier_id]);

                // 加权平均价更新
                $history_stock_num = Db::table("tb_product_stock")->where('product_id',$row->product_id)->sum("stock_num") - $row->add_num;

                $history_bid_price = Db::table("tb_product_stock")->where('product_id',$row->product_id)->value('bid_price');

                Logger::init()->info("UPDATE_BIDPRICE_INFO_{$row->product_id}:".json_encode([$history_stock_num,$history_bid_price,$row->add_num,$row->bid_price]));

                $now_stock_num = $history_stock_num+$row->add_num;

                if($now_stock_num != 0)
                {
                    $new_bid_price = bcdiv(bcadd(bcmul($history_stock_num,$history_bid_price,4),bcmul($row->add_num,$row->bid_price,4),4),$now_stock_num,4);

                    Logger::init()->info("UPDATE_BIDPRICE_INFO_{$row->product_id}:".$new_bid_price);

                    $new_bid_price = bcdiv(($history_stock_num*$history_bid_price+($row->add_num*$row->bid_price)),$now_stock_num,4);

                    Logger::init()->info("UPDATE_BIDPRICE_INFO_{$row->product_id}:".json_encode([
                            'history_price_total'=>bcmul($history_stock_num,$history_bid_price,4),
                            'new_add_price_total'=>bcmul($row->add_num,$row->bid_price,4),
                            'new_num_total'=>bcadd($history_stock_num,$row->add_num,4),
                            'new_bid_price'=>$new_bid_price
                        ]));

                    Db::table("tb_product_stock")->where('product_id',$row->product_id)->update(["bid_price"=>$new_bid_price]);

                    Db::table("tb_product")->where('id',$row->product_id)->update(["bid_price"=>$new_bid_price]);

                    Db::table("mf_depot_product")->where('product_id',$row->product_id)->update(["now_bid_price"=>$new_bid_price]);
                }
                $why_info = $row->do_type == 7?"退货入库：":"入库：";
                $why_info .= $row->product_name.$row->add_num.$row->product_unit;
                $all_money = $all_money+$add_money;

                //更新预警采购单
                Db::table("tb_buy_order")->where("product_id",$row->product_id)->where("order_status",0)->update([
                    'order_status'=>2,
                    'receive_num'=>$row->add_num
                ]);
            }
            else
            {
                if($row->do_type == 2 or $row->do_type == 4)
                {
                    $all_out_money = $all_out_money+$add_money;

                    $enable_money = $enable_money+$add_money; // 增加可提现金额

                    Db::table("tb_supplier_account")->where("supplier_id",$row->supplier_id)
                        ->update([
                            "enable_money"=>$enable_money,
                            "all_out_money"=>$all_out_money
                        ]);

                    $why_info = $row->do_type==2?"出库:":"盘亏:";

                    $why_info .= $row->product_name.$row->add_num.$row->product_unit;

                    if($row->do_type==2)
                    {
                        Db::update("update tb_product set real_sale_num = real_sale_num + ? where id=?",[abs($row->add_num),$row->product_id]);
                    }

                }
                elseif ($row->do_type == 3 or $row->do_type == 5)
                {
                    Db::update("update tb_supplier_account set enable_money = enable_money - ?,all_out_money = all_out_money - ? where supplier_id = ?",[$add_money,$add_money,$row->supplier_id]);

                    $i = -1;

                    $why_info = $row->do_type==3?"盘盈:":"退货:";

                    $why_info .= $row->product_name.$row->add_num.$row->product_unit;
                }
                elseif ($row->do_type == 6){

                    Db::table("tb_supplier_account")->where("supplier_id",$row->supplier_id)
                        ->decrement("all_money",$add_money);

                    $i = -1;

                    $all_money = $all_money - $add_money;

                    $why_info = '厂家退货:';

                    $why_info .= $row->product_name.$row->add_num.$row->product_unit;
                }
            }

            Db::table("tb_supplier_account_log")->insert([
                'supplier_id'=>$row->supplier_id,
                'freeze_money'=>$sa->freeze_money?$sa->freeze_money:0,
                'all_out_money'=>$all_out_money,
                'add_money'=>$add_money * $i,
                'all_money'=>$all_money,
                'enable_money'=>$enable_money,
                'add_type'=>$row->do_type == 6?12:$row->do_type,
                'create_time'=>date("Y-m-d H:i:s"),
                'why_info'=>$why_info
            ]);
            Db::table("tb_product_stock_log")->where("id",$stock_log_id)->update(["is_ok"=>1]);
            Db::commit();
        }catch (\Exception $e)
        {
            Db::rollback();
            Logger::init()->error("UPDATE_ACCOUNT_ERR:".$e->getMessage());
            throw new Exception($e->getMessage(),$e->getCode());
        }
    }

    public function change($supplier_id = 0,$add_money = 0,$add_type = 0,$why_info = '',$admin_id = 0,$user_id = 0)
    {
        $supplierAccount = TbSupplierAccount::where("supplier_id",$supplier_id)->first();
        $supplierAccountLog = new TbSupplierAccountLog();
        $supplierAccountLog->supplier_id    = $supplier_id;
        $supplierAccountLog->admin_id       = $admin_id;
        $supplierAccountLog->user_id        = $user_id;
        $supplierAccountLog->add_money      = $add_money;
        $supplierAccountLog->add_type       = $add_type;
        $supplierAccountLog->enable_money   = $supplierAccount->enable_money;
        $supplierAccountLog->freeze_money   = $supplierAccount->freeze_money;
        $supplierAccountLog->all_money      = $supplierAccount->all_money;
        $supplierAccountLog->all_out_money  = $supplierAccount->all_out_money;
        $supplierAccountLog->why_info       = $why_info;
        $supplierAccountLog->create_time    = date("Y-m-d H:i:s");
        $supplierAccountLog->save();
        $supplierAccount->enable_money      = $supplierAccount->enable_money+$add_money;
        $supplierAccount->save();
    }
}
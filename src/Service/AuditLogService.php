<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\MfAdmin;
use Mwenju\Common\Model\MfAdminRole;
use Mwenju\Common\Model\YunAudit;
use Mwenju\Common\Model\YunAuditLog;
use Hyperf\DbConnection\Db;
use Hyperf\RpcServer\Annotation\RpcService;

/**
 * 采购单
 * Class AuditLogService
 * @package App\Common\Service
 * @RpcService(name="AuditLogService",protocol="jsonrpc",server="jsonrpc")
 */
#[RpcService("AuditLogService","jsonrpc","jsonrpc")]
class AuditLogService extends BaseService
{
    public function add($param = [])
    {
        $model              = $param['model']??'';
        $model_id           = $param['model_id']??0;
        $audit_status       = $param['audit_status']??0;
        $audit_user_id      = $param['audit_user_id']??0;
        $audit_remark       = $param['audit_remark']??"";
        $next_role_id       = $param['next_role_id']??0;
        $next_role_name     = $param['next_role_name']??"";
        $audit_time         = date("Y-m-d H:i:s");

        $admin = MfAdmin::where('user_id',$audit_user_id)->first();
        if (!$admin)
            return arrayError("您不是管理员不能审核");

        $audit_admin_id     = $admin->id;
        $audit_admin_name   = $admin->real_name;

        $row = Db::table($model)->find($model_id);
        if ($row->audit_status > 0)
        {
            return arrayError("已完结，不能重复审核");
        }
        $return_status  = 0;
        $sort_num       = 0;

        // 未配置审核流，则直接返回
        if (YunAudit::where("model",$model)->count() == 0)
        {
            Db::table($model)->where("id",$model_id)->update(['audit_status'=>$audit_status]);
            return arraySuccess("操作成功",['audit_status'=>$audit_status]);
        }
        else
        {
            // 检测当前是否初审
            $latest = YunAuditLog::where('model',$model)->where("model_id",$model_id)->latest("id")->first();

            if ($latest)
            {
                $audit = YunAudit::where("model",$model)->where("admin_role_id",$admin->role_ids)->where("sort_num",$latest->sort_num+1)->first();
                if (!$audit)
                    return arrayError("您没有权限审核");
                //判断是否存在下个节点
                $next_audit = YunAudit::where("model",$model)->where("sort_num",$audit->sort_num+1)->first();

                if ($latest->sort_num+1 != $audit->sort_num)
                {
                    return arrayError("当前未流转到此节点操作哦");
                }
                if ($next_audit)
                {
                    $sort_num = $audit->sort_num;
                }
                if ($audit_status == 2 || ($audit_status == 1 && !$next_audit))
                {
                    $return_status = $audit_status;
                    Db::table($model)->where("id",$model_id)->update(['audit_status'=>$audit_status]);
                }
            }
            else
            {
                $audit_status = 0; // 首次提交人默认0
                $next_audit = YunAudit::where("model",$model)->where("sort_num",1)->first();
            }

            if ($next_audit)
            {
                $next_role_id = $next_audit->admin_role_id;
                $next_role_name = MfAdminRole::where("id",$next_role_id)->value("cname");
            }
        }

        YunAuditLog::insertGetId([
            'model'=>$model,
            'model_id'=>$model_id,
            'audit_status'=>$audit_status,
            'audit_admin_id'=>$audit_admin_id,
            'audit_user_id'=>$audit_user_id,
            'audit_admin_name'=>$audit_admin_name,
            'audit_remark'=>$audit_remark,
            'audit_time'=>$audit_time,
            'next_role_id'=>$next_role_id,
            'next_role_name'=>$next_role_name,
            'sort_num'=>$sort_num
        ]);

        return arraySuccess("操作成功",['audit_status'=>$return_status]);
    }

    public function getLastAuditStatus($param = [])
    {
        $model              = $param['model']??'';
        $model_id           = $param['model_id']??0;
        $audit_status       = Db::table($model)->where("id",$model_id)->value("audit_status");
        $latest = YunAuditLog::where('model',$model)->where("model_id",$model_id)->latest("id")->first();
        if ($audit_status == 0 && $latest)
        {
            return $latest->audit_status;
        }
        return $audit_status;
    }

    public function getList($param = [])
    {
        $model              = $param['model']??'';
        $model_id           = $param['model_id']??0;
        $data = YunAuditLog::where('model',$model)->where("model_id",$model_id);
        $total = $data->count();
        $list = $data->orderBy("id","asc")->get()->each(function ($item,$index){
            $item->audit_status_str = trans("audit.status_".$item->audit_status);
        });
        return ['total'=>$total,'rows'=>$list];
    }
}
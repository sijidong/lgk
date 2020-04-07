<?php

namespace app\admin\controller;

use app\admin\model\UserCashWater;
use app\common\controller\Backend;
use app\common\model\User;

/**
 * 提币申请
 *
 * @icon fa fa-circle-o
 */
class UserCashWithdraw extends Backend
{
    
    /**
     * UserCashWithdraw模型对象
     * @var \app\admin\model\UserCashWithdraw
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\UserCashWithdraw;
        $this->view->assign("statusList", $this->model->getStatusList());
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    public function update_status()
    {
        $ids = $this->request->get('ids');
        $type = $this->request->get('type');
        if ($type == 'pass') {
            UserCashWater::where('detail_id', $ids)->update([
                'type' => USER_CASH_WATER_WITHDRAW_SUCCESS
            ]);
            $this->model->where('id',$ids)->update(['status' => 1]);
            $this->success('通过成功！');
        } else {
            $row = $this->model->get($ids);
//            $water_data = MiningWater::where('detail_id', $row->id)->find();
//            $user_data = User::where('id', $row->user_id)->find();
            UserCashWater::where('detail_id', $ids)->update([
                'type' => USER_CASH_WATER_WITHDRAW_SUCCESS,
                'money' => $row->money
            ]);
//            MiningWater::create([
//                'from_user_id' => $water_data->user_id,
//                'user_id' => $water_data->user_id,
//                'money_type' => $water_data->money_type,
//                'money' => $water_data->money,
//                'balance' => $user_data->mining_money + $row->number,
//                'mark' => $water_data->mark,
//                'type' => MINING_WATER_WITHDRAW_REJECT
//            ]);
            User::where('id', $row->user_id)->setInc('money' ,$row->money);
            $this->model->where('id',$ids)->update(['status' => 2]);
            $this->success('拒绝成功！');
        }
    }
}

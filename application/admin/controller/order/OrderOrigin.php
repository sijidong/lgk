<?php

namespace app\admin\controller\order;

use app\admin\model\WaterOrigin;
use app\common\controller\Backend;
use app\common\model\User;
use think\Db;

/**
 * 用户订单
 *
 * @icon fa fa-circle-o
 */
class OrderOrigin extends Backend
{
    
    /**
     * OrderOrigin模型对象
     * @var \app\admin\model\OrderOrigin
     */
    protected $model = null;
    protected $relationSearch = true;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\OrderOrigin;
        $this->view->assign("payTypeList", $this->model->getPayTypeList());
        $this->view->assign("statusList", $this->model->getStatusList());
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->with(["user"])
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->with(["user"])
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    public function updateStatus()
    {
        $id = $this->request->get('ids');

        $data = \app\admin\model\OrderOrigin::where('id',$id)->where('status',2)
//            ->where('sell_user_id',$this->auth->getUser()->id)
            ->find();
        if (empty($data)) {
            $this->error('放行的订单不存在，请确定订单是否已支付或是已经放行！');
        }

        $type = $this->request->get('type');
        try {
            if ($type == 'pass') {
                Db::transaction(function () use ($id,$data){
                    \app\common\model\User::where('id',$data->user_id)->setInc('origin_buy',$data->number);
                    $to_user_origin_buy_balance = getDynamicBalance($data->user_id);
                    WaterOrigin::create([
                        'detail_id' => $id,
                        'user_id' => $data->user_id,
                        'type' => WATER_ORIGIN_BUY,
                        'money' => $data->number,
                        'balance'=> $to_user_origin_buy_balance + $data->number
                    ]);
                    \app\admin\model\OrderOrigin::where('id',$id)->update(['status' => 3,'finish_time' => datetime()]);

                    $market_data = \app\admin\model\OrderMarket::where('id',$data->order_market_id)->find();

                    $handling_rate = $market_data->handling_rate;
                    //成功扣除手续费
                    $handling = round($handling_rate * $data->number / 100,2);
                    \app\admin\model\OrderMarket::where('id',$data->order_market_id)->setDec('handling_fee',$handling);

                    //奖励买家
                    $deal_bonus_fee = getConfigValue('deal_bonus_fee');
                    $handling = round($deal_bonus_fee * $data->number / 100,2);
                    $trade_center = User::where('id',$data->user_id)->value('trade_center');
                    if (!empty($handling) && !empty($trade_center)) {
                        $to_user_origin_dynamic_balance = getDynamicBalance($data->user_id);
                        //同时需要奖励玩家交易费用
                        WaterOrigin::create([
                            'detail_id' => $id,
                            'user_id' => $data->user_id,
                            'type' => WATER_ORIGIN_BUY_BONUS,
                            'money' => $handling,
                            'balance' => $to_user_origin_dynamic_balance + $handling
                        ]);
                        incBalance($data->user_id,'origin_dynamic',$handling);
                    }

                    //如果市场单的余额为0，置为下架
                    $exist = \app\admin\model\OrderOrigin::where('order_market_id',$market_data->id)->whereIn('status','1,2')->find();
                    if ($market_data->balance == 0 && empty($exist)) {
                        \app\admin\model\OrderMarket::where('id',$market_data->id)->update(['status' => 0,'finish_time' => datetime()]);
                    }
                });
            } else {
                OrderOrigin::where('id',$data->id)->update(['status' => 0]);
                OrderMarket::where('id',$data->order_market_id)->setInc('balance', $data->number);
            }
        } catch (\Exception $e) {
            $this->error(__('unknow_error'));
        }

        $this->success();
    }

    public function cancel($ids)
    {
        $oder_data = SalesOrder::where('id', $ids)->where('status',1)->find();
        if ( !$oder_data)
            $this->error(__('订单或许已被取消或已支付！'));

        SalesOrder::where('id', $ids)->update(['status' => 0]);
        DogList::where('id', $oder_data->dog_list_id)->update(['status' => '0']);

        $this->success();
    }
}

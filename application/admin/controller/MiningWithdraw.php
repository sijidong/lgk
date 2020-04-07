<?php

namespace app\admin\controller;

use app\admin\controller\mining\MiningUserWallet;
use app\admin\model\MiningWater;
use app\admin\model\WaterCoin;
use app\admin\model\WaterStock;
use app\common\controller\Backend;
use app\common\model\User;
use app\common\model\WalletTransferOut;
use fast\Http;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Log;

/**
 * 提币申请
 *
 * @icon fa fa-circle-o
 */
class MiningWithdraw extends Backend
{
    
    /**
     * MiningWithdraw模型对象
     * @var \app\admin\model\MiningWithdraw
     */
    protected $model = null;
    protected $relationSearch = true;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\MiningWithdraw;
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
                ->order($sort, $order)
                ->with(['wallet'])
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->with(['wallet'])
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                if ($row->status !=0) {
                    $this->error('该笔提币已经审核！');
                }
                if (!empty($params['hash']) && $row->status == 0) {
                    $row = $this->model->get($ids);
                    $params['status'] = 1;
                    $this->model->where('id',$ids)->update(['status' => 1]);
                }
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    public function update_status($ids = '')
    {
        $ids = $this->request->get('ids') ?? $ids;
        $type = $this->request->get('type') ?? 'pass';
        $row = $this->model->get($ids);
        $status = \app\admin\model\WalletTransferOut::where('id',$row->detail_id)->value('status');

        if ($row->status != 0 && $row->status != 1) {
            $this->error('该笔提币已审核！');
        }
        if ($row->status == 1 && $status != 0)
        {
            $this->error('该笔提币还正在确认中！');
        }
        if ($type == 'pass') {
            //下面为充归提：************
            if ($row->status == 0) {
                $result = WalletTransferOut::create([
                    'coinid' => 1,
                    'toaddress' => $row->address,
                    'addtime' => datetime(),
                    'status' => 5,
                    'num' => $row->amount,
                    'user_id' => $row->user_id
                ]);
                $this->model->where('id',$ids)->update(['status' => 1,'detail_id' => $result->id,'update_time' => datetime()]);
            } else {
                $this->model->where('id',$ids)->update(['status' => 3,'update_time' => datetime()]);
            }

//            $params['address'] = $row->address;
//            $params['amount'] = $row->amount;
//            $params['userid'] = $row->user_id;
//            $params['coinid'] = 1;
//            $params['remark'] = $ids;
//            $result = http_post_json('127.0.0.1:10603/v1/lgk/withdraw',json_encode($params));
            /************************/
            $this->success('通过成功！');
        } else {
            $row = $this->model->get($ids);
            WaterCoin::create([
                'detail_id' => $row->id,
                'user_id' => $row->user_id,
                'type' => WATER_COIN_WITHDRAW_REJECT,
                'money' => $row->number,
                'balance' => getUserValue($row->user_id,'coin') + $row->number
            ]);

            incBalance($row->user_id,'coin',$row->number);
            $this->model->where('id',$ids)->update(['status' => 2,'update_time' => datetime()]);
            $this->success('拒绝成功！');
        }
    }

    public function multi_update_status($ids = '')
    {
        if (empty($ids)) $this->error('没有指定提币账单！');
        $ids = explode(',', $ids);
        if (empty($ids) || empty($ids[0])) $this->error('没有指定提币账单！');
        foreach ($ids as $value) {
            $data = $this->model->where('id', $value)->find();
            if ($data->status != '0') {
                continue;
            }
            $result = WalletTransferOut::create([
                'coinid' => 1,
                'toaddress' => $data->address,
                'addtime' => datetime(),
                'status' => 5,
                'num' => $data->amount,
                'user_id' => $data->user_id
            ]);
            $this->model->where('id', $value)->update(['status' => 1,'detail_id' => $result->id,'update_time' => datetime()]);
        }

        $this->success('批量审核成功！');
    }
}

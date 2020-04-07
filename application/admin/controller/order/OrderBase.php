<?php

namespace app\admin\controller\Order;

use app\admin\model\MiningStatistics;
use app\admin\model\WaterBase;
use app\common\controller\Backend;
use app\common\model\User;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 用户订单
 *
 * @icon fa fa-circle-o
 */
class OrderBase extends Backend
{
    
    /**
     * OrderBase模型对象
     * @var \app\admin\model\OrderBase
     */
    protected $model = null;
    protected $relationSearch = true;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\OrderBase;
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
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
//                    if ($params['status'] == 4) {
//                        User::where('id',$row->user_id)->setInc('base',$params['number']);
//                        WaterBase::create([
//                            'detail_id' => $row->id,
//                            'user_id' => $row->user_id,
//                            'type' => WATER_BASE_BUY,
//                            'money' => $params['number']
//                        ]);
//                    }
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

    public function review()
    {
        $row = $this->request->get('ids');
        $type = $this->request->get('type');

        $oder_data = \app\admin\model\OrderBase::where('id', $row)->where('status',3)->find();
        if ( !$oder_data)
            $this->error(__('No Results were found'));

        if ($type == 'pass') {
            $balance = User::where('id',$oder_data->user_id)->value('base');
            User::where('id',$oder_data->user_id)->setInc('base',$oder_data->number);
            WaterBase::create([
                'detail_id' => $oder_data->id,
                'user_id' => $oder_data->user_id,
                'type' => WATER_BASE_BUY,
                'money' => $oder_data->number,
                'balance' => $balance + $oder_data->number
            ]);
            \app\admin\model\OrderBase::where('id', $row)->where('status',3)->update(['status' => 4]);
        } else {
            $create_time = date('Y-m-d',strtotime($oder_data->create_time));
            MiningStatistics::where('create_time',$create_time)->setInc('base',$oder_data->number);
            \app\admin\model\OrderBase::where('id', $row)->where('status',3)->update(['status' => 0]);
        }

        $this->success();
    }
}

<?php

namespace app\admin\controller\mining;

use app\admin\model\MiningWater;
use app\admin\model\WalletToken;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 用户区块钱包
 *
 * @icon fa fa-circle-o
 */
class MiningUserWallet extends Backend
{
    
    /**
     * MiningUserWallet模型对象
     * @var \app\admin\model\MiningUserWallet
     */
    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\MiningUserWallet;

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
//                ->with(["user"])
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
//                ->with(["user"])
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach ($list as &$value) {
                $value['address'] = WalletToken::where('uid',$value['user_id'])->where('token_id',$value['mining_type_id'])->value('address');
                $value['mining_type_id_str'] = \app\admin\model\MiningType::where('id',$value['mining_type_id'])->value('coin_type');
            }
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
                    $tmp = $params['balance'];
                    $params = [];
                    $params['balance'] = $tmp;
                    if (!empty($params['balance'])) {
                        $params['balance'] = $row->balance + $params['balance'];
                        $coin_type = \app\admin\model\MiningType::where('id',$row->mining_type_id)->value('coin_type');
                        MiningWater::create([
//                    'detail_id' => $order_data->id,
                            'user_id' => $row->user_id,
                            'type' => MINING_WATER_WATER_BACKEND,
                            'coin_type' => $coin_type,
                            'money' =>  $tmp,
                        ]);
                        $result = $row->allowField(true)->save($params);
                    }
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
}

<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use app\common\model\Category;
use fast\Tree;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class User extends Backend
{
    
    /**
     * User模型对象
     * @var \app\admin\model\User
     */
    protected $model = null;
    protected $relationSearch = true;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\User;
        $this->view->assign("tradeCenterList", $this->model->getTradeCenterList());
        $this->view->assign("realAuthList", $this->model->getRealAuthList());
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
        $can_money = $this->auth->check('user/user/money');

//        $can_info = $this->auth->check('user/user/info');
        $this->assignconfig('can_money', $can_money);
//        $this->assignconfig('can_info', $can_info);
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
                ->with(["useraddress"])
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->with(["useraddress"])
                ->limit($offset, $limit)
                ->select();

            foreach ($list as $value) {
                $produce_stock = $value->stock;
                $value->produce_level =  \app\admin\model\RuleStatic::where('from','<=',$produce_stock)
                        ->where('to','>',$produce_stock)->value('id') ?? 5;
                $value->produce_level = $value->produce_level - 1;
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (\Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
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
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (\Exception $e) {
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

    public function detail($ids = null)
    {
        $result = [];
        $tree = Tree::instance();
        $data = $this->model->field('id,mobile,pid')->select();
        getChild($data,$ids,$result,-1);
        $result[] = $this->model->field('id,mobile,pid')->find($ids);
        $tree->init(collection($result)->toArray());

        $this->categorylist = $tree->getTreeList($tree->getTreeArray($ids), 'mobile');
        $data['data'] = $this->categorylist;

        $this->assign('data',$data);
//        $categorydata = [0 => ['type' => 'all', 'name' => __('None')]];
//        foreach ($this->categorylist as $k => $v) {
//            $categorydata[$v['id']] = $v;
//        }
//        $typeList = Category::getTypeList();
//        $this->view->assign("flagList", $this->model->getFlagList());
//        $this->view->assign("typeList", $typeList);
//        $this->view->assign("parentList", $categorydata);
//
//
//        $this->request->filter(['strip_tags']);
////        if ($this->request->isAjax()) {
//            $search = $this->request->request("search");
//            $type = $this->request->request("type");
//
//            //构造父类select列表选项数据
//            $list = [];
//
//            foreach ($this->categorylist as $k => $v) {
//                if ($search) {
//                    if ($v['type'] == $type && stripos($v['name'], $search) !== false || stripos($v['nickname'], $search) !== false) {
//                        if ($type == "all" || $type == null) {
//                            $list = $this->categorylist;
//                        } else {
//                            $list[] = $v;
//                        }
//                    }
//                } else {
//                    if ($type == "all" || $type == null) {
//                        $list = $this->categorylist;
//                    } elseif ($v['type'] == $type) {
//                        $list[] = $v;
//                    }
//                }
//            }
//
//            $total = count($list);
//            $result = array("total" => $total, "rows" => $list);
//
//            return json($result);
////        }

        return $this->view->fetch();
    }
}

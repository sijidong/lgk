<?php

namespace app\admin\model;

use think\Model;


class MineUser extends Model
{

    

    

    // 表名
    protected $table = 'mine_user';
    
    // 自动写入时间戳字段
//    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
//    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'manage_type_text',
        'status_text'
    ];
    

    
    public function getManageTypeList()
    {
        return ['self' => __('Manage_type self'), 'platform' => __('Manage_type platform')];
    }

    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1'), '2' => __('Status 2'), '3' => __('Status 3')];
    }


    public function getManageTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['manage_type']) ? $data['manage_type'] : '');
        $list = $this->getManageTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}

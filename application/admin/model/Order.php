<?php

namespace app\admin\model;

use think\Model;


class Order extends Model
{

    

    

    // 表名
    protected $table = 'order';
    
    // 自动写入时间戳字段
//    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
//    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'pay_type_text',
        'status_text',
        'manage_type_text'
    ];
    

    
    public function getPayTypeList()
    {
        return ['0' => __('Pay_type 0'), '1' => __('Pay_type 1'), '2' => __('Pay_type 2')];
    }

    public function getStatusList()
    {
        return ['0' => __('Status 0'), '2' => __('Status 2'), '3' => __('Status 3'), '10' => __('Status 10')];
    }

    public function getManageTypeList()
    {
        return ['self' => __('Manage_type self'), 'platform' => __('Manage_type platform')];
    }


    public function getPayTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pay_type']) ? $data['pay_type'] : '');
        $list = $this->getPayTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getManageTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['manage_type']) ? $data['manage_type'] : '');
        $list = $this->getManageTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}

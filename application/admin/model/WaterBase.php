<?php

namespace app\admin\model;

use think\Model;


class WaterBase extends Model
{

    

    

    // 表名
    protected $table = 'water_base';
    
    // 自动写入时间戳字段
//    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
//    protected $createTime = false;
//    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text'
    ];

    public function user()
    {
        return $this->belongsTo('user', 'user_id')->setEagerlyType(0);
    }

    public function getTypeList()
    {
        return ['0' => __('Type 0'), '1' => __('Type 1'), '2' => __('Type 2'), '7' => __('Type 7'), '8' => __('Type 8')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}

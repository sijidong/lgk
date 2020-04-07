<?php

namespace app\admin\model;

use think\Model;


class WaterOriginDetail extends Model
{

    

    

    // 表名
    protected $table = 'water_origin_detail';
    
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
    

    
    public function getTypeList()
    {
        return ['0' => __('Type 0'), '1' => __('Type 1'), '2' => __('Type 2'), '7' => __('Type 7'), '8' => __('Type 8'), '10' => __('Type 10'), '11' => __('Type 11'), '12' => __('Type 12'), '13' => __('Type 13'), '18' => __('Type 18'), '19' => __('Type 19'), '20' => __('Type 20'), '21' => __('Type 21'), '22' => __('Type 22'), '23' => __('Type 23'), '24' => __('Type 24'), '25' => __('Type 25'), '26' => __('Type 26')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}

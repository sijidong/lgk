<?php

namespace app\common\model;

use think\Cache;
use think\Model;

/**
 * 地区数据模型
 */
class CountryMobilePrefix extends Model
{

    // 表名,不含前缀
    protected $table = 'country_mobile_prefix';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;
    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    // 追加属性
    protected $append = [
    ];
}

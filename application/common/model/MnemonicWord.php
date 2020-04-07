<?php

namespace app\common\model;

use think\Cache;
use think\Model;

/**
 * 地区数据模型
 */
class MnemonicWord extends Model
{

    // 表名,不含前缀
    protected $table = 'mnemonic_word';
    // 自动写入时间戳字段
//    protected $autoWriteTimestamp = false;
    // 定义时间戳字段名
//    protected $createTime = false;
    protected $updateTime = false;
    // 追加属性
    protected $append = [
    ];
}

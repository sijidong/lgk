<?php

namespace app\admin\model;

use think\Model;


class MiningUserWallet extends Model
{

    

    

    // 表名
    protected $table = 'mining_user_wallet';
    
    // 自动写入时间戳字段
//    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
//    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    public function address()
    {
        return $this->hasOne('WalletToken', 'uid','user_id')->setEagerlyType(0);
    }

}

<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\Route;
Route::rule('api/goods/goodsDetail/:id','api/goods/goodsDetail');
Route::rule('api/order/orderDetail/:order_id','api/order/orderDetail');
Route::rule('api/article/getNewsDetail/:id','api/article/getNewsDetail');
Route::rule('api/article/getAnnouncementDetail/:id','api/article/getAnnouncementDetail');
Route::rule('api/order/orderDetail/:order_id','api/order/orderDetail');
Route::rule('api/address/getCity/:city_id','api/address/getCity');
Route::rule('api/address/getDistrict/:district_id','api/address/getDistrict');
return [
    //别名配置,别名只能是映射到控制器且访问时必须加上请求的方法
    '__alias__'   => [
    ],
    '__domain__'=>[
        'api'      => 'api  ',
    ],
    //变量规则
    '__pattern__' => [
    ],
//        域名绑定到模块
//        '__domain__'  => [
//            'admin' => 'admin',
//            'api'   => 'api',
//        ],
];

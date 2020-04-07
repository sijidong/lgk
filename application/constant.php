<?php
/**
 * 定义常量.
 * User: Sam
 * Date: 2019/4/2
 * Time: 10:13
 */
define('COIN_1','USDT');
define('COIN_2','OBE');

//Water Origin
define('WATER_ORIGIN_BACKEND',0);
define('WATER_ORIGIN_BUY', 2);
define('WATER_ORIGIN_RECHARGE_TRANSFER_IN', 5);             //充值转入
define('WATER_ORIGIN_RECHARGE_TRANSFER_OUT', 6);            //充值转出
define('WATER_ORIGIN_TRANSFER_IN', 7);          //动态转入
define('WATER_ORIGIN_TRANSFER_OUT', 8);         //动态转出

define('WATER_ORIGIN_BUY_STOCK_DYNAMIC', 9);
define('WATER_ORIGIN_BUY_STOCK', 10);
define('WATER_ORIGIN_MARKET_SELL', 11);
define('WATER_ORIGIN_MARKET_SELL_CANCEL', 12);
define('WATER_ORIGIN_MARKET_SELL_HANDLING_FEE', 13);
define('WATER_ORIGIN_MARKET_SELL_CANCEL_HANDLING_FEE', 14);
define('WATER_ORIGIN_RELEASE', 20);         //产酒指数释放
define('WATER_ORIGIN_DYNAMIC', 21);         //分享
define('WATER_ORIGIN_ONE', 22);         //分享1极
define('WATER_ORIGIN_TWO', 23);         //分享2极
define('WATER_ORIGIN_THREE', 24);         //分享3及
define('WATER_ORIGIN_FOUR', 22);         //分享4及
define('WATER_ORIGIN_BUY_BONUS', 26);       //购买市场奖励
define('WATER_ORIGIN_CONVERT_SHOP', 27);       //原酒转换积分
define('WATER_ORIGIN_BACKEND_DYNAMIC',30);
define('WATER_ORIGIN_BACKEND_BUY',31);
define('WATER_ORIGIN_BACKEND_STATIC',32);
define('WATER_ORIGIN_STATIC_TEXT', '静态产出额度');
define('WATER_ORIGIN_DYNAMIC_TEXT', '动态产出额度');

//Water Base
define('WATER_BASE_BACKEND', 0);
define('WATER_BASE_BUY', 1);
define('WATER_BASE_BUY_STOCK', 2);
define('WATER_BASE_TRANSFER_IN', 7);
define('WATER_BASE_TRANSFER_OUT', 8);

//Water Stock
define('WATER_STOCK_BACKEND', 0);
define('WATER_STOCK_BUY_STOCK',1);
define('WATER_STOCK_INTEREST',2);
define('WATER_STOCK_RELEASE',3);
define('WATER_STOCK_CONVERT',4);
define('WATER_STOCK_WITHDRAW_APPEAL',5);
define('WATER_STOCK_WITHDRAW_SUCCESS',6);
define('WATER_STOCK_WITHDRAW_REJECT',7);
define('WATER_STOCK_CAR_COST', 8);
define('WATER_STOCK_RELEASE_PROFIT',20);
define('WATER_STOCK_CONVERT_SHOP',21);

//Water Shop
define('WATER_SHOP_BACKEND',0);
define('WATER_SHOP_STATIC',1);
define('WATER_SHOP_DYNAMIC',2);
define('WATER_SHOP_BUY',3);

//user_water
define('USER_WATER_BACKEND', 0);
define('USER_WATER_BUSINESS', 1);       //分享津贴
define('USER_WATER_MANAGE', 2);       //辅导津贴
define('USER_WATER_LEADER',3);       //管理津贴
define('USER_WATER_SERVICE',4);       //服务返点

//Water Coin
define('WATER_COIN_BACKEND', 0);
define('WATER_COIN_RECHARGE',1);
define('WATER_COIN_WITHDRAW',2);
define('WATER_COIN_WITHDRAW_REJECT',3);
define('WATER_COIN_TRANSFER_IN',6);
define('WATER_COIN_TRANSFER_OUT',7);
define('WATER_COIN_CAR_COST',11);
define('WATER_COIN_CAR_RETURN',12);
define('WATER_COIN_CAR_RECHARGE',13);

//define('USER_WATER_ORDER_COST', 7);
define('USER_WATER_TRANSFER_IN',7);     //转入
define('USER_WATER_TRANSFER_OUT',8);    //转出
define('USER_WATER_MONEY_CONVERT_COIN',10);         //兑入币
define('USER_WATER_COIN_CONVERT_MONEY',11);         //兑出币
define('USER_WATER_RELEASE',15);            //每日释放
define('USER_WATER_MONEY_CONVERT_PEAS',16);            //现金券兑换豆
define('USER_WATER_PEAS_CONVERT_MONEY',17);            //豆兑换现金券

//user cash water
define('USER_CASH_WATER_BACKEND', 0);
define('USER_CASH_WATER_WITHDRAW_APPEAL', 1);
define('USER_CASH_WATER_WITHDRAW_SUCCESS', 2);
define('USER_CASH_WATER_WITHDRAW_REJECT', 3);
define('USER_CASH_WATER_RECOMMEND', 4);            //现金推荐
define('USER_CASH_WATER_RECOMMEND_ALLOWANCE', 5);            //现金推荐同级补助
define('USER_CASH_WATER_COST', 6);            //现金花费


//Mining water
define('MINING_WATER_WATER_BACKEND', 0);    //后台充值
define('MINING_WATER_RECHARGE',1);      //充值
define('MINING_WATER_WITHDRAW_APPEAL', 2);
define('MINING_WATER_WITHDRAW_REJECT', 3);
define('MINING_WATER_WITHDRAW_SUCCESS', 4);
//define('MINING_WATER_RECOMMEND', 4);
//define('MINING_WATER_RECOMMEND_EQUAL', 5);
define('MINING_WATER_COST', 10);         //购买矿机
define('MINING_WATER_MORTGAGE', 11);         //质押
define('MINING_WATER_MORTGAGE_RELEASE', 12);         //解放质押

define('MINING_WATER_MINING', 20);               //挖矿收益
define('MINING_WATER_INVITE', 21);               //动态收益
define('MINING_WATER_TEAM', 22);               //团队收益
define('MINING_WATER_EQUAL_LEVEL', 23);               //同级收益
define('MINING_WATER_CALCULATION_BONUS', 24);         //算力奖励

//define('MINING_WATER_TRUSTEESHIP',10);    //托管收益
//define('MINING_WATER_DIG_EQUAL',11);    //托管平级收益
//define('MINING_WATER_DIG_ALLOWANCE',12);    //托管平级收益
//define('MINING_WATER_MINING',15);       //托管收益
//Bonus Water
define('BONUS_WATER_WATER_BACKEND', 0);
define('BONUS_WATER_BUSINESS', 1);
define('BONUS_WATER_MANAGE', 2);
define('BONUS_WATER_LEADER',3);       //管理津贴
define('BONUS_WATER_SERVICE',4);       //服务返点
define('BONUS_WATER_RELEASE',   7);         //分红权释放
define('BONUS_WATER_ADD', 10);

//Order
define('ORDER_CANCEL', 0);          //订单取消
define('ORDER_UNPAID',1);           //未支付
define('ORDER_WAIT_SEND',2);           //未支付
define('ORDER_WAIT_RECEIVE',3);           //未支付
define('ORDER_FINISH', 10);          //已完成

//MineUser
define('MINE_USER_WAITE',0);      //待挖矿
define('MINE_USER_MINING',1);         //挖矿中
define('MINE_USER_MORTGAGE_RELEASE',2);      //准备抵押
define('MINE_USER_MORTGAGE',3);         //质押
define('MINE_USER_EXPIRE',4);         //已过期

define('MINE_USER_MANAGE_PLATFORM','platform');      //矿机平台托管
define('MINE_USER_MANAGE_SELF','self');          //矿机自我管理
//PATH
define('UPLOADS_PATH','uploads' . DS);
define('USER_REAL_INFO',UPLOADS_PATH. 'userRealInfo'.DS);
define('APPEAL_PATH', UPLOADS_PATH . 'appealOrder'. DS);
define('PAYMENT_PATH', UPLOADS_PATH . 'payment'. DS);
define('PAYINFO_PATH', UPLOADS_PATH. 'payInfo' . DS);
define('AVATAR_PATH', UPLOADS_PATH . 'avatar' . DS);
define('AVATAR_THUMB_PATH', UPLOADS_PATH . 'avatar_thumb' . DS);

//User water
define('USER_WATER_MONEY_CONVERT_COIN_MARK', '源葆豆/币：');
define('USER_WATER_TRANSFER_OUT_MARK', '收款人：%d');
define('USER_WATER_TRANSFER_IN_MARK', '转款人：%d');
define('USER_WATER_BUSINESS_MARK','当前代理等级：%s');
define('USER_WATER_MANAGE_MARK','当前津贴比例：%d');
define('USER_WATER_RELEASE_MARK','当前释放比例：%d%s');
define('USER_WATER_LEADER_MARK','当前招商级别：%s');
define('USER_WATER_SERVICE_MARK','返点比例：%d%s');
define('USER_WATER__MONEY_CONVERT_PEAS_MARK', '现金券/源葆豆：');

//Bonus water
define('BONUS_WATER_BUSINESS_MARK','当前代理等级：%s');
define('BONUS_WATER_MANAGE_MARK','当前津贴比例：%d');
define('BONUS_WATER_LEADER_MARK','当前招商级别：%s');
define('BONUS_WATER_SERVICE_MARK','返点比例：%d%s');
define('BONUS_WATER_RELEASE_MARK','当前释放比例：%d%s');

//Order mark
define('ORDER_MSG','因你用代理价购买的商品，你将获得￥%d分红权上限');

define('ORDER_CANCEL_TIME',7200);
define('ORDER_FREE_TIME', 7200);
define('PAGE_LIMIT', 15);
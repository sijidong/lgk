<?php

namespace app\api\controller;

use app\admin\model\MineUser;
use app\admin\model\MiningType;
use app\admin\model\MiningUserWallet;
use app\admin\model\OrderStock;
use app\admin\model\RuleStatic;
use app\admin\model\RuleUserLevel;
use app\admin\model\UserLeaderRule;
use app\admin\model\UserLevelRule;
use app\admin\model\UserPayinfo;
use app\admin\model\UserRealInfo;
use app\admin\model\WaterOrigin;
use app\admin\model\WaterStock;
use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use app\common\model\Config;
use app\common\model\MnemonicWord;
use fast\Random;
use think\Cache;
use think\Env;
use think\Image;
use think\Log;
use think\Validate;

/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = ['login', 'mobilelogin', 'register', 'resetpwd', 'changeemail', 'changemobile', 'third'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
        $this->User_model = model('User');
    }

    /**
     * 会员中心
     */
    public function index()
    {
        $user_info['level_name'] = RuleUserLevel::where('id', $this->auth->getUser()->rule_user_level_id)->value('name') ?? '普通会员';
        $user_info['level'] = $this->auth->getUser()->rule_user_level_id;
        $user_info['nickname'] = $this->auth->getUser()->nickname;
        $user_info['mobile'] = $this->auth->getUser()->mobile;
        $user_info['avatar'] = $this->auth->getUser()->avatar;
        $user_info['email'] = $this->auth->getUser()->email;
        $produce_stock = $this->auth->getUser()->stock + $this->auth->getUser()->origin_static;
        $user_info['produce_level'] = RuleStatic::where('from','<=',$produce_stock)->where('to','>',$produce_stock)->value('id');
        $user_info['produce_level'] = $user_info['produce_level'] - 1;

        $this->success('', $user_info);
    }

    /**
     * 我的收益
     */
    public function profit()
    {
        $user_id = $this->auth->getUser()->id;
        $data['user']['origin'] =  number_format($this->auth->getUser()->origin_buy + $this->auth->getUser()->origin_dynamic + $this->auth->getUser()->origin_recharge,2,'.','');
        $data['user']['base'] =  $this->auth->getUser()->base;
        $data['user']['stock'] =  number_format($this->auth->getUser()->stock + $this->auth->getUser()->origin_static,2,'.','');
//        print_r($data);exit
//        return json($data);
        $data['profit']['yesterday_mining'] = WaterStock::where('user_id',$user_id)->whereTime('create_time','today')->
        where('type',WATER_STOCK_RELEASE_PROFIT)->sum('money');
        $data['profit']['yesterday_num_mining'] = WaterStock::where('user_id',$user_id)->whereTime('create_time','today')->
        where('type',WATER_STOCK_INTEREST)->sum('money');

        $data['profit']['total_mining'] =  WaterStock::where('user_id',$user_id)->
        where('type',WATER_STOCK_RELEASE_PROFIT)->sum('money');

        $data['profit']['yesterday_team_mining'] = WaterOrigin::where('user_id',$user_id)->whereTime('create_time','today')->
        whereIn('type',[WATER_ORIGIN_DYNAMIC,WATER_ORIGIN_ONE,WATER_ORIGIN_TWO,WATER_ORIGIN_THREE,WATER_ORIGIN_FOUR])->sum('money');

        $this->success('ok',$data);
    }

    /**
     * 我的
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function info()
    {
        $user_id = $this->auth->getUser()->id;
        $data['service_contact'] = Config::where('name','service_contact')->value('value');
        $data['level_name']= UserLevelRule::where('id',$this->auth->getUser()->level)->value('name');
        $data['username'] = $this->auth->getUser()->username;
        $data['next_level'] = '你已是最高级别';
        $data['rate'] = '1';
        $begin_calculation = UserLevelRule::where('id',$this->auth->getUser()->level)->value('calculation_begin') ?? 0;
        $next_level = UserLevelRule::where('id',($this->auth->getUser()->level + 1))->find();
        if (!empty($next_level)) {
            $user_data = \app\admin\model\User::field('id,pid,level')->select();
            $result = [];
            getChildId($user_data, $user_id,$result, -1);
            $result[] = $user_id;
            $user_calculation = MineUser::whereIn('user_id',$result)->sum('calculation');
            $diff = $next_level->calculation_begin -  $user_calculation;
            $data['next_level'] = '总算力：'.$user_calculation.'T，再购买'.$diff.'T即可升级';
            $data['rate'] = round(($user_calculation - $begin_calculation) / ($next_level->calculation_begin - $begin_calculation),2);
        }

        $this->success('ok',$data);
    }

    /**
     * 获取邀请页面数据
     * @throws \think\Exception
     */
    public function invite()
    {
        //TODO
        $user_id = $this->auth->getUser()->id;
        $user_data = \app\common\model\User::field('id,pid,rule_user_level_id')->select();
        $result = [];
        getChildId($user_data, $user_id, $result, -1);
        $min_stock = RuleStatic::order('to','asc')->value('to');
        $real_result = [];
        $team_stock = 0;
        foreach ($result as $value) {
            $stock = OrderStock::field('sum(number) as bb')->where('user_id',$value)->group('user_id')->find();
            if (!empty($stock)
                && $stock->bb >= $min_stock
            ) {
                $real_result[] = $value;
                $interest = WaterStock::where('user_id',$value)->where('type',WATER_STOCK_INTEREST)->sum('money');
                $team_stock += $stock->bb + $interest;
            }
        }

        $data['team_num'] = count($real_result);
        $data['team_profit'] = nf($team_stock);
//        $data['direct_push'] = \app\common\model\User::where('pid', $user_id)->count();

        $data['direct_push'] = \app\common\model\User::join('order_stock','order_stock.user_id = user.id')
            ->field('sum(order_stock.number) as aa,order_stock.id as bb,user.id,rule_user_level_id,username,avatar')
            ->where('pid', $user_id)
            ->group('order_stock.user_id')
            ->having('aa >= '.$min_stock)
            ->count();
//        $sum = ::where('user_id',$user_id)->find();
        $sum = OrderStock::where('user_id',$user_id)->find();
        $data['invitecode'] = empty($sum) ? '' : $this->auth->getUser()->invitecode;
//        $data['invitecode'] = $this->auth->getUser()->invitecode;
        $this->success('ok', $data);
    }

    /**
     * 我的直推
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function myDirectPush()
    {
        $user_id = $this->auth->getUser()->id;
        $page = $this->request->get('page') ?? 1;
        $min_stock = RuleStatic::order('to','asc')->value('to');
        $direst_push_data = \app\common\model\User::join('order_stock','order_stock.user_id = user.id')
            ->field('sum(order_stock.number) as aa,user.id,rule_user_level_id,username,avatar')
            ->where('pid', $user_id)
            ->page($page, 20)
            ->group('order_stock.user_id')
            ->having('aa >= '.$min_stock)
            ->select();

        $user_level_rule = RuleUserLevel::column('name','id');

//        $data['direct_push'] = count($direst_push_data);
        $team_stock = 0;
        foreach ($direst_push_data as $value)
        {
            $value->level_name = $user_level_rule[$value->rule_user_level_id] ?? '普通会员';

//            $value->direct_push = \app\common\model\User::where('pid', $value->id)->count();
            $value->direct_push = \app\common\model\User::join('order_stock','order_stock.user_id = user.id')
                ->field('sum(order_stock.number) as aa,user.id')
                ->where('pid', $value->id)
//                ->page($page, 20)
                ->group('order_stock.user_id')
                ->having('aa >= '.$min_stock)
                ->count();
            $value->achievement = MineUser::where('user_id',$value->id)->sum('calculation');
            $value->stock = $value->aa;
            $user_info = RuleStatic::where('from','<=',$value->aa)
                ->where('to','>',$value->aa)->value('id');
            $value->produce_level = $user_info - 1;
            $team_stock += $value->aa;
            unset($value->id);
        }
        $data['team_stock'] = $team_stock;

        $data['data'] = $direst_push_data;

        $this->success('ok', $data);

    }


    /**
     * 修改用户名
     */
    public function alterNickname()
    {
        $nickname = $this->request->post('nickname');
        \app\common\model\User::where('id', $this->auth->getUser()->id)->update(['nickname' => $nickname]);

        $this->success('修改昵称成功！');
    }

    /**
     * 头像上传
     */
    public function avatar()
    {
        if (empty($this->request->post()))
        {
            $data['avatar'] = $this->auth->getUser()->avatar;
        }

        $file = $this->request->file('image');

        if (!empty($file)) {
            $info = $file->validate(['size' => 4194204])->move(AVATAR_PATH);

//        $image = Image::open(AVATAR_PATH.$info->getSaveName());
//
//        $image->thumb(150, 150)->save(AVATAR_THUMB_PATH.$info->getSaveName());
            if (!$info) {
                $this->error($file->getError());
            }
            $image = DS . AVATAR_PATH . $info->getSaveName();
            \app\common\model\User::where('id',$this->auth->getUser()->id)->update(['avatar' => $image]);
        }

        $this->success('修改成功！');
    }

    /**
     * 会员登录
     *
     * @param string $account  账号
     * @param string $password 密码
     */
    public function login()
    {
        $account = $this->request->request('account');
        $password = $this->request->request('password');
        if (!$account || !$password) {
            $this->error(__('Invalid parameters'));
        }
        $ret = $this->auth->login($account, $password);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 手机验证码登录
     *
     * @param string $mobile  手机号
     * @param string $captcha 验证码
     */
    public function mobilelogin()
    {
        $mobile = $this->request->request('mobile');
        $captcha = $this->request->request('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (!Sms::check($mobile, $captcha, 'mobilelogin')) {
            $this->error(__('Captcha is incorrect'));
        }
        $user = \app\common\model\User::getByMobile($mobile);
        if ($user) {
            if ($user->status != 'normal') {
                $this->error(__('Account is locked'));
            }
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user->id);
        } else {
            $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, []);
        }
        if ($ret) {
            Sms::flush($mobile, 'mobilelogin');
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注册会员
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $email    邮箱
     * @param string $mobile   手机号
     */
    public function register()
    {
//        $username = $this->request->request('username');
        $password = $this->request->request('password');
        $deal_password = $this->request->request('deal_password');
        $email = $this->request->request('email');
        $mobile = $this->request->request('mobile') ?? '';

        //邀请人
        $invitecode = $this->request->post('invitecode');
        $pid = \app\common\model\User::where("invitecode", $invitecode)->value('id') ?? 0;
        if (empty($pid) && !empty(\app\common\model\User::find())) {
            $this->error("推荐人不存在");
        }

        if (!$password || !$deal_password) {
            $this->error(__('Invalid parameters'));
        }

        $captcha = trim($this->request->request('captcha'));
        if ($mobile) {
            if ($mobile && !Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
            $captcha_check = Sms::check($mobile, $captcha, 'register');
        } else {
            if ($email && !Validate::is($email, "email")) {
                $this->error(__('Email is incorrect'));
            }
            $captcha_check = Ems::check($email, $captcha, 'register');
        }
        if (!$captcha_check) {
            $this->error('验证码不正确!');
        }
//        //验证码
//        if ($mobile) {
//            $captcha_check = Sms::check($mobile, $captcha, 'register');
//        } else {
//            $captcha_check = Emslib::check($email, $captcha, 'register');
//        }


        //邀请码
        $username = empty($mobile) ? $email : $mobile;
//        $invitecode = '';
        $exist = true;
        while ($exist) {
            $invitecode = Random::alnum(5);
            $exist = \app\common\model\User::where('invitecode', $invitecode)->find();
        }

        $path = \app\common\model\User::where('id',$pid)->value('path');
        $extend = [
            'invitecode' => $invitecode,
            'pid' => $pid,
            'deal_password' => $deal_password,
            'real_auth' => 2,
            'avatar' => '/avatar.png',
            'nickname' => $this->request->post('nickname') ?? '',
            'path' => '-'.$pid.$path
        ];     //扩展
        $ret = $this->auth->register($username, $password, $email, $mobile, $extend);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            if ($this->auth->getUser()->id) {
//                file_get_contents('127.0.0.1:8069/eth/newaddress?uid='.$this->auth->getUser()->id.'&key=ek6PpFk97gCayFNe');
            }
            $this->success(__('Sign up successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注销登录
     */
    public function logout()
    {
        $this->auth->logout();
        $this->success(__('Logout successful'));
    }

    /**
     * 修改会员个人信息
     *
     * @param string $avatar   头像地址
     * @param string $username 用户名
     * @param string $nickname 昵称
     * @param string $bio      个人简介
     */
    public function profile()
    {
        $user = $this->auth->getUser();
        $username = $this->request->request('username');
        $nickname = $this->request->request('nickname');
        $bio = $this->request->request('bio');
        $avatar = $this->request->request('avatar', '', 'trim,strip_tags,htmlspecialchars');
        if ($username) {
            $exists = \app\common\model\User::where('username', $username)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Username already exists'));
            }
            $user->username = $username;
        }
        $user->nickname = $nickname;
        $user->bio = $bio;
        $user->avatar = $avatar;
        $user->save();
        $this->success();
    }

    /**
     * 修改邮箱
     *
     * @param string $email   邮箱
     * @param string $captcha 验证码
     */
    public function changeemail()
    {
        $user = $this->auth->getUser();
        $email = $this->request->post('email');
        $captcha = $this->request->request('captcha');
        if (!$email || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
        if (\app\common\model\User::where('email', $email)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Email already exists'));
        }
        $result = Ems::check($email, $captcha, 'changeemail');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->email = 1;
        $user->verification = $verification;
        $user->email = $email;
        $user->save();

        Ems::flush($email, 'changeemail');
        $this->success();
    }

    /**
     * 修改手机号
     *
     * @param string $email   手机号
     * @param string $captcha 验证码
     */
    public function changemobile()
    {
        $user = $this->auth->getUser();
        $mobile = $this->request->request('mobile');
        $captcha = $this->request->request('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (\app\common\model\User::where('mobile', $mobile)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Mobile already exists'));
        }
        $result = Sms::check($mobile, $captcha, 'changemobile');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->mobile = 1;
        $user->verification = $verification;
        $user->mobile = $mobile;
        $user->save();

        Sms::flush($mobile, 'changemobile');
        $this->success();
    }

    /**
     * 第三方登录
     *
     * @param string $platform 平台名称
     * @param string $code     Code码
     */
    public function third()
    {
        $url = url('user/index');
        $platform = $this->request->request("platform");
        $code = $this->request->request("code");
        $config = get_addon_config('third');
        if (!$config || !isset($config[$platform])) {
            $this->error(__('Invalid parameters'));
        }
        $app = new \addons\third\library\Application($config);
        //通过code换access_token和绑定会员
        $result = $app->{$platform}->getUserInfo(['code' => $code]);
        if ($result) {
            $loginret = \addons\third\library\Service::connect($platform, $result);
            if ($loginret) {
                $data = [
                    'userinfo'  => $this->auth->getUserinfo(),
                    'thirdinfo' => $result
                ];
                $this->success(__('Logged in successful'), $data);
            }
        }
        $this->error(__('Operation failed'), $url);
    }

    /**
     * 重置密码
     *
     * @param string $mobile      手机号
     * @param string $newpassword 新密码
     * @param string $captcha     验证码
     */
    public function resetpwd()
    {
        $type = $this->request->request("type");
        $mobile = $this->request->request("mobile");
        $email = $this->request->request("email");
        $newpassword = $this->request->request("newpassword");
        if (!$newpassword) {
            $this->error(__('Invalid parameters'));
        }
        /* */
        if (strlen($newpassword) < 6) {
            $this->error('密码长度太短！');
        }
        //用户模式下没有验证码
        $captcha = $this->request->request("captcha");
        if ($type != 'user' && empty($captcha)){
            $this->error(__('Invalid parameters'));
        }
        /* */

        if ($type == 'mobile') {
            if (!Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
            $user = \app\common\model\User::getByMobile($mobile);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Sms::check($mobile, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Sms::flush($mobile, 'resetpwd');
        } else if($type == 'user'){
            $oldpassword = $this->request->request("oldpassword");
            $user = $this->auth->getUser();
            $ret = $this->auth->changepwd($newpassword, $oldpassword, false);
            if (!$ret) {
                $this->error($this->auth->getError());
            }
        } else{
            if (!Validate::is($email, "email")) {
                $this->error(__('Email is incorrect'));
            }
            $user = \app\common\model\User::getByEmail($email);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Ems::check($email, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Ems::flush($email, 'resetpwd');
        }
        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 重置交易密码
     */
    public function resetDealpwd()
    {
        $type = $this->request->request("type");
        if ($this->request->post()) {
            if ($type == 'email') {
                $email = $this->auth->getUser()->email;
                $phone_code = $this->request->post('captcha');

                $captcha_check = Ems::check($email,$phone_code, 'resetdealpwd');
                if (!$captcha_check) {
                    $this->error('验证码不正确!');
                }
                $newpassword = $this->request->post("new_deal_password");
                if (!$newpassword) {
                    $this->error(__('Invalid parameters'));
                }
                $ret = \app\common\model\User::where('id',$mobile = $this->auth->getUser()->id)->update(['deal_password' => $newpassword]);
//            $ret = $this->User_model->update(['deal_password' => $newpassword]);
//            if ($ret) {
                $this->success('修改成功！');
            } else {
                $mobile = $this->auth->getUser()->mobile;
                $phone_code = $this->request->post('captcha');

                $captcha_check = Sms::check($mobile,$phone_code, 'resetdealpwd');
                if (!$captcha_check) {
                    $this->error('验证码不正确!');
                }
                $newpassword = $this->request->post("new_deal_password");
                if (!$newpassword) {
                    $this->error(__('Invalid parameters'));
                }
                $ret = \app\common\model\User::where('id',$mobile = $this->auth->getUser()->id)->update(['deal_password' => $newpassword]);
//            $ret = $this->User_model->update(['deal_password' => $newpassword]);
//            if ($ret) {
                $this->success('修改成功！');
            }

//            } else {
//                $this->error('s');
//            }
        } else {
            if ($type == 'email') {
                $data['email'] =$this->auth->getUser()->email;
                $this->success($data);
            } else {
                $data['mobile'] = $this->auth->getUser()->mobile;
                $this->success($data);
            }
        }
    }

    public function alterDealpwd()
    {
        $deal_password = $this->request->post('deal_password');
        $new_deal_password = $this->request->post('new_deal_password');

        if ($deal_password != $this->auth->getUser()->deal_password) {
            $this->error('原始资金密码错误！');
        }

        \app\common\model\User::where('id',$this->auth->getUser()->id)->update(['deal_password' => $new_deal_password]);

        $this->success('修改成功！');
    }
    /**
     * 实名认证
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function realNameAuth()
    {
        $user_id = $this->auth->getUser()->id;

        if($this->request->post())
        {
            $validate = Validate::make([
                'name|名字' => 'require',
                'id_card|身份证' => 'require|min:18|max:20',
//                'captcha|验证码' => 'require|captcha'
//                'id_image' => 'image',
                'image1' => 'require',
                'image2' => 'require'
            ]);
            if (!$validate->check($this->request->post()))
                $this->error($validate->getError());

            $name = $this->request->post('name');
            $id_card = $this->request->post('id_card');

            $data = UserRealInfo::where('user_id', $user_id)->where('status','<>','2')->find();
            if (!empty($data)) {
                if ($data->status == '0') {
                    $this->error('你的认证资料在审核中，请等待审核！');
                }
                $this->error('你已通过认证！');
//                UserRealInfo::where('user_id', $user_id)->update(compact('name','id_card', 'status'));
            } else{
                $res = UserRealInfo::where('id_card',$id_card)->where('status','<>','2')->find();
                if (!empty($res)) {
                    $this->error('该身份证用户已存在！');
                }
//                $api_url = 'https://api.storeapi.net/pyi/62/169';
//                $appid  =   '223';// 在后台我的应用查看;
//                $secret =   '80a28bd4e5cded589969dec6ba87af18';// 在后台我的应用查看;
//                $data = array(
//                    'appid'=>  $appid,
//                    'bank_id'=>  $id_card,
//                    'bank_name'=>  $name,
//                    'format'=>  'json',
//                );
//                ksort($data); //按照键名对数组排序，为数组值保留原来的键。
//                $md5String = '';
//                foreach($data as $key=>$val){
//                    if(strlen($val)>0){ //过滤空值
//                        $md5String.=$key.$val;
//                    }
//                }
//                $sign = md5($md5String.$secret);
//                $data['sign'] = $sign;
//                $sendUrl = $api_url.'?'.http_build_query($data); //把数据转换成url参数形式，a=b&c=d&e=f
//                $result = file_get_contents($sendUrl);
//                if (empty($result)) {
//                    Log::error('RealName network error');
//                    $this->error('网络错误！请稍后再尝试');
//                }
//                $result = json_decode($result, true);
                $is_real = 0;
                $area = '';
                $birthday = '';
//                if (!empty($result['codeid'])) {
//                    if ($result['codeid'] == '10000') {
//                        if (!empty($result['retdata']['bank_status'])) {
//                            if ($result['retdata']['bank_status'] == '01') {
//                                $is_real = 1;
//                                $area = $result['retdata']['bank_area'];
//                                $birthday = $result['retdata']['bank_birthday'];
//                            }
//                        }
////                    Log::error($result['message']);
////                    $this->error('实名功能维护中！请稍后再尝试');
//                    }
//                }
//                $file = $this->request->file('id_image');
//                if (empty($file)) {
//                    $this->error('未上传文件或超出服务器上传限制');
//                }
                $id_image = [];
//                foreach ($file as $value) {
////                    $info = $value->validate(['size' => 4194204])->move(USER_REAL_INFO);
////                    if (!$info) {
////                        $this->error($value->getError());
////                    }
////                    $id_image[] = '/'.IMAGE_PATH . $info->getSaveName();
////                }
                $a_image = $this->request->post('image1');
                $b_image = $this->request->post('image2');
//                $id_image = implode(',',$isd_image);
//                $this->User_model->where('id', $user_id)->update(['new_payinfo' => 1]);
                UserRealInfo::create(compact('name','id_card','user_id','area','birthday','a_image','b_image'));
                \app\common\model\User::where('id',$this->auth->getUser()->id)->update(['real_auth' => 1]);
            }

            $this->success('提交成功');
        }

        $data = UserRealInfo::field('name,id_card,status,a_image,b_image')->where('user_id', $user_id)
            ->whereIn('status','0,1,2')->find();
        if ($data) {
            $data->id_card = substr_replace($data->id_card, '********', 6,8);
        }
        $this->success('',$data);
    }

    /**
     * 我的银行卡列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPayInfoList()
    {
        $this->loadadminlang('user/user_payinfo');
        $user_id = $this->auth->getUser()->id;
        $data = UserPayinfo::field('id,type,account,image,name')->where('user_id', $user_id)
            ->whereIn('status', '1,0')
            ->order('create_time','desc')->select();

        $this->success('ok', $data);
    }


    /**
     * 添加银行卡
     */
    public function addPayInfo()
    {
        $validate = Validate::make([
            'name' => 'require',
            'account' => 'require',
            'type' => 'require|in:bank,alipay,wechat',
        ]);
        if (!$validate->check($this->request->post()))
            $this->error($validate->getError());

        if ($this->auth->getUser()->deal_password != $this->request->post('deal_password')) {
            $this->error('交易密码错误！');
        }
        $user_id = $this->auth->getUser()->id;
        $type = $this->request->post('type');
        $account = $this->request->post('account');
        $name = $this->request->post('name');
        $phone = $this->request->post('phone');
        $bank = $this->request->post('bank');
        $res = UserPayinfo::where('type', $type)->where('user_id', $user_id)->whereIn('status', ['0','1'])->find();
        if (!empty($res)) {
            if ($res->status == '1') {
                $this->error('已添加相应的收款信息！');
            }
        }
        $status = 1;
        if ($type == 'bank') {
            $result = UserPayinfo::create(compact('bank',  'user_id', 'account', 'type', 'name', 'phone','status'));
        } else {
            $file = $this->request->file('image');
            if (empty($file)) {
                $this->error('未上传文件');
            }
            $info = $file->validate(['size' => 4194204])->move(PAYINFO_PATH);
            if (!$info) {
                $this->error($file->getError());
            }
            $image = '/'.PAYINFO_PATH . $info->getSaveName();
            $result = UserPayinfo::create(compact('image', 'user_id', 'account', 'type', 'name', 'phone','status'));
        }
//        \app\common\model\User::where('id', $user_id)->update(['new_payinfo' => '1']);
        if (empty($result)) {
            $this->error('添加失败');
        }
        $this->success('添加成功');
    }

    public function editPayInfo()
    {
        $validate = Validate::make([
            'name' => 'require',
            'account' => 'require',
            'type' => 'require|in:bank,alipay,wechat',
        ]);
        if (!$validate->check($this->request->post()))
            $this->error($validate->getError());

        if ($this->auth->getUser()->deal_password != $this->request->post('deal_password')) {
            $this->error('交易密码错误！');
        }
        $user_id = $this->auth->getUser()->id;
        $id = $this->request->post('id');
        $type = $this->request->post('type');
        $account = $this->request->post('account');
        $name = $this->request->post('name');
        $phone = $this->request->post('phone');
        $bank = $this->request->post('bank');
        $res = UserPayinfo::where('type', $type)->where('user_id', $user_id)
            ->where('id',$id)->where('status', 1)->find();
        if (empty($res)) {
            $this->error('收款不存在！');
        }

        if ($type == 'bank') {
            UserPayinfo::where('id',$id)->update(compact('bank',  'user_id', 'account', 'type', 'name', 'phone'));
        } else {
            $image = $res->image;
            $file = $this->request->file('image');
            if (!empty($file)) {
                $info = $file->validate(['size' => 4194204])->move(PAYINFO_PATH);
                if (!$info) {
                    $this->error($file->getError());
                }
                $image = '/'.PAYINFO_PATH . $info->getSaveName();
            }

            UserPayinfo::where('id',$id)->update(compact('image', 'user_id', 'account', 'type', 'name', 'phone'));
        }

        $this->success('添加成功');
    }
    /**
     * 删除银行卡
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function deletePayInfo()
    {
//        $this->error('为保证资金安全，如想更换或删除收款信息，请联系客服，谢谢！');
        $user_id = $this->auth->getUser()->id;
        $id = $this->request->post('id');
        UserPayinfo::where('user_id',$user_id)->delete($id);
        $this->success('删除成功');
    }

    /**
     * 获取银行卡详情
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPayInfoDetail()
    {
        $pay_id = $this->request->get('id');
        $user_id = $this->auth->getUser()->id;
        $data = UserPayinfo::where('id', $pay_id)->where('user_id', $user_id)->find();
        if (empty($data)) {
            $this->error('找不到用户信息！');
        }

        $this->success('ok', $data);
    }

}

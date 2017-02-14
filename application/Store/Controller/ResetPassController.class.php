<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2017/2/5
 * Time: 14:19
 */

namespace Store\Controller;



use Common\Model\SmslogModel;
use Merchant\Model\MemberShopModel;
use Web\Controller\BaseController;

class ResetPassController extends BaseController
{
    private $member_shop_model, $smslog_model;
    public function __construct()
    {
        parent::__construct();
        $this->member_shop_model = new MemberShopModel();
        $this->smslog_model = new SmslogModel();
    }

    public function get_pwd() {

        $this->display();
    }

    public function backpasswd() {
        $username = I('post.username');
        $code = I('post.code');
        $password = I('post.password');

        if (strlen($password) < 5) exit($this->returnApiError(BaseController::FATAL_ERROR, '密码长度过段'));

        $where['user_login'] = $username;
        $member_shopInfo = $this->member_shop_model->where($where)->find();
        if(!$member_shopInfo) {
            exit($this->returnApiError(BaseController::FATAL_ERROR, '查无此用户'));
        }
        $result = $this->smslog_model->checkcode($username, $code);
        if (!$result) exit($this->returnApiError(BaseController::FATAL_ERROR, '验证码错误或过期'));

        $update = $this->member_shop_model->where($where)->setField('user_pass', sp_password($password));
        if ($update === false) exit($this->returnApiError(BaseController::FATAL_ERROR, '密码修改失败'));

        exit($this->returnApiSuccess());
    }
}
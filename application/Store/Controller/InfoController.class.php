<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/12/6
 * Time: 10:55
 */

namespace Store\Controller;
use Merchant\Model\MemberShopModel;
use Think\Controller;

class InfoController extends Controller
{
    private $member_shopmodel;

    public function __construct()
    {
        parent::__construct();
        $this->member_shopmodel = new MemberShopModel();
    }

    /*
     * 账号设置
     */
    public function setting(){
        $info = checkLogin();
        if(empty($info)) {
            echo "<script>alert('请登录');</script>";
            $this->display('Index/index');
        }
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 重置登录密码
     */
    public function resetpass() {
        $info = checkLogin();
        if(empty($info)) {
            echo "<script>alert('请登录');</script>";
            $this->display('Index/index');exit;
        }
        if(IS_POST) {
//            $id = $info['id'];
            $postdata = get_data(1);
            if(empty($postdata['password'])) {
                echo "<script>alert('原密码为空');</script>";
                $this->display();exit;
            }
            if(empty($postdata['password_new'])) {
                echo "<script>alert('新密码为空');</script>";
                $this->display();exit;
            }
            if(empty($postdata['password_again'])) {
                echo "<script>alert('重复新密码为空');</script>";
                $this->display();exit;
            }

            if($postdata['password_new'] != $postdata['password_again']) {
                echo "<script>alert('两次输入新密码不一致');</script>";
                $this->display();exit;
            }
            if(strlen($postdata['password_new']) < 6 || strlen($postdata['password_new']) > 16) {
                echo "<script>alert('请输入6-16位密码');</script>";
                $this->display();exit;
            }
            $where['user_login'] = $info['username'];
            $where['user_pass'] = sp_password($postdata['password']);
            $rst = $this->member_shopmodel->getInfo($where);
            if(!$rst) {
                echo "<script>alert('登录密码错误');</script>";
                $this->display();exit;

            }
            $res = $this->member_shopmodel->editPass($rst['id'], $postdata['password_new']);
            if($res) {
                session('password', $postdata['password']);
                echo "<script>alert('修改成功!');</script>";
                $info['password'] = sp_password($postdata['password']);
                $this->assign('info', $info);
                $this->display('Info/setting');exit;
            }
        }
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 重置支付密码
     */
    public function resetpay(){
        $info = checkLogin();
        if(empty($info)) {
            echo "<script>alert('请登录');</script>";
            $this->display('Index/index');
        }
        if(IS_POST) {
            $id = $info['id'];
            $postdata = get_data(1);
            if(empty($postdata['password'])) {
                echo "<script>alert('原密码为空');</script>";
                $this->display();exit;
            }
            if(empty($postdata['password_new'])) {
                echo "<script>alert('新密码为空');</script>";
                $this->display();exit;
            }
            if(empty($postdata['password_again'])) {
                echo "<script>alert('重复新密码为空');</script>";
                $this->display();exit;
            }

            if($postdata['password_new'] != $postdata['password_again']) {
                echo "<script>alert('两次输入新密码不一致');</script>";
                $this->display();exit;
            }
            if(strlen($postdata['password_new']) < 6 || strlen($postdata['password_new']) > 16) {
                echo "<script>alert('请输入6-16位密码');</script>";
                $this->display();exit;
            }
            /*$where['user_login'] = $info['username'];
            $where['user_pass'] = $info['password'];
            $rst = $this->member_shopmodel->getInfo($where);
            if(!$rst) {
                echo "<script>alert('登录密码错误');</script>";
                $this->display();exit;

            }*/
            $res = $this->member_shopmodel->editPay($info['id'], $postdata['password_new']);
            if($res) {
                echo "<script>alert('修改成功!');</script>";
                $this->assign('info', $info);
                $this->display('Info/setting');exit;
            }
        }
        $this->assign('info', $info);
        $this->display();
    }

    public function imageUp() {
        $info = checkLogin();
        if(empty($info)) {
            echo "<script>alert('请登录');</script>";
            $this->display('Index/index');
        }
        if(IS_POST) {
            $imgurl = upload_img();
            if(!$imgurl) {
                echo "<script>alert('图片上传失败');</script>";
                $this->display();exit;
            }
            $rst = $this->member_shopmodel->where(array('id' => $info['id']))->save(array('user_img' => $imgurl[0]));
            if(!$rst) {
                echo "<script>alert('修改失败');</script>";
                $this->display();exit;
            }
            $info['img'] = setUrl($imgurl[0]);
            session('img',$info['img']);
            $this->assign('info', $info);
            $this->display('Info/setting');exit;
        }


        $this->assign('info', $info);
        $this->display();
    }
}
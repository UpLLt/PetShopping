<?php
namespace Web\Controller;

use App\Model\DocumentsModel;
use Common\Model\AddressModel;
use Common\Model\MempetModel;
use Common\Model\OrderModel;
use Common\Model\OrderPetModel;
use Common\Model\OrderProductModel;
use Common\Model\PetTypeModel;
use Common\Model\ProductModel;
use Common\Model\ProductOptionModel;
use Common\Model\PushmsgModel;
use Common\Model\RegionModel;
use Common\Model\SmslogModel;
use Consumer\Model\MemberModel;
use Common\Model\PetModel;
use Think\Controller;

/**
 * 账号管理 用户注册 登录界面
 * Class IndexController
 * @package Web\Controller
 */
class MemberController extends BaseController
{
    protected $member_model;
    protected $smslog_model;
    protected $mempet_model;
    protected $pettype_model;
    protected $address_model;
    protected $region_model;
    protected $product_model;
    protected $product_option_model;
    protected $order_model;
    protected $order_product_model;
    protected $order_pet_model;
    protected $push_msgmodel;
    protected $documents_model;
    public function __construct()
    {
        parent::__construct();
        $this->member_model = new MemberModel();
        $this->smslog_model = new SmslogModel();
        $this->mempet_model = new MempetModel();
        $this->pettype_model= new PetTypeModel();
        $this->address_model= new AddressModel();
        $this->region_model = new RegionModel();
        $this->product_model= new ProductModel();
        $this->product_option_model = new ProductOptionModel();
        $this->order_model = new OrderModel();
        $this->order_product_model = new OrderProductModel();
        $this->order_pet_model = new OrderPetModel();
        $this->push_msgmodel = new PushmsgModel();
        $this->documents_model = new DocumentsModel();
    }


    /**
     * 登录界面
     */
    public function login()
    {
        $this->display();
    }

    /**
     * 注册短信接口
     */
    public function sendsms()
    {
        $mobile = I('post.phone');

        $this->checkparam([$mobile]);
        if (strlen($mobile) != 11)
            exit($this->returnApiError(BaseController::FATAL_ERROR, '手机号码错误'));
        if ($this->member_model->checkUserName($mobile) > 0) exit($this->returnApiError(BaseController::FATAL_ERROR, '该帐号已注册'));
        $code = $this->get_code(6, 1);
        $content = '【咪咻】你的验证码是:' . $code . '，15分钟内有效。';
        $Validtime = 60 * 15;

        $data = [
            'code' => $code,
            'content' => $content,
            'mobile' => $mobile,
            'create_time' => time(),
            'end_time' => time() + $Validtime,
        ];

        vendor("Cxsms.Cxsms");
        $options = C('SMS_ACCOUNT');
        $Cxsms = new \Cxsms($options);
        $result = $Cxsms->send($mobile, $content);
        if ($result && $result['returnsms']['returnstatus'] == 'Success') {
            $adds = $this->smslog_model->add($data);
            if ($adds) exit($this->returnApiSuccess());
            else
                exit($this->returnApiError(BaseController::FATAL_ERROR, '数据库写入失败'));
        } else {
            exit($this->returnApiError(BaseController::FATAL_ERROR, '验证码发送失败'));
        }
    }

    /**
     * 忘记密码发送短信
     */
    public function forgetsendsms()
    {


        $mobile = I('post.phone');
        $this->checkparam([$mobile]);

        if (strlen($mobile) != 11)
            exit($this->returnApiError(BaseController::FATAL_ERROR, '手机号码错误'));

        if ($this->member_model->checkUserName($mobile) == 0)
            exit($this->returnApiError(BaseController::FATAL_ERROR, '该帐号未注册'));

        $code = $this->get_code(6, 1);
        $content = '【咪咻】你的验证码是:' . $code . '，15分钟内有效。';
        $Validtime = 60 * 15; //有效时间

        $data = [
            'code' => $code,
            'content' => $content,
            'mobile' => $mobile,
            'create_time' => time(),
            'end_time' => time() + $Validtime,
        ];

        vendor("Cxsms.Cxsms");
        $options = C('SMS_ACCOUNT');
        $Cxsms = new \Cxsms($options);
        $result = $Cxsms->send($mobile, $content);
        if ($result && $result['returnsms']['returnstatus'] == 'Success') {
            $adds = $this->smslog_model->add($data);
            if ($adds) exit($this->returnApiSuccess());
            else
                exit($this->returnApiError(BaseController::FATAL_ERROR, '数据库写入失败'));
        } else {
            exit($this->returnApiError(BaseController::FATAL_ERROR, '验证码发送失败'));
        }
    }

    public function getpass(){
        $this->display('get_pwd');
    }


    /**
     * 会员登录
     */
    public function userlogin()
    {
        $userAccount = I("post.userAccount");
        $userPassword = I("post.userPassword");
        $this->checkparam([$userAccount, $userPassword]);
        $where = [
            'username' => $userAccount,
            'password' => sp_password($userPassword),
        ];
        $field = 'id as mid,username,headimg,authentication';
        $members = $this->member_model
            ->field($field)
            ->where($where)
            ->find();

        if (!$members) exit($this->returnApiError(BaseController::FATAL_ERROR, '登录帐号或者密码错误.'));

        session('mid', $members['mid']);

        if ( !session('mid')) exit($this->returnApiError(BaseController::FATAL_ERROR,'登录失败'));

        exit($this->returnApiSuccess());

    }


    /**
     * 注册界面
     */
    public function register()
    {
        $this->regis_agreement();
        $this->display();
    }

    /**
     * 用户注册协议
     */
    public function regis_agreement(){
        $regis_agreement = $this->documents_model->where('doc_class="regis_agreement"')->getField('content');
        $this->assign('agreement',$regis_agreement);
    }


    /**
     * 会员注册
     */
    public function register_post()
    {

        $password = I('post.password');
        $code = I('post.code');
        $phone = I('post.phone');


        $this->checkparam([$password, $code, $phone]);

        if ($this->member_model->checkUserName($phone) > 0)
            exit($this->returnApiError(BaseController::FATAL_ERROR, '该帐号已注册'));

        if (!$this->smslog_model->checkcode($phone, $code)) {
            exit($this->returnApiError(BaseController::FATAL_ERROR, '验证码错误或过期'));
        }

        if ($this->member_model->register($phone, $password)) {


            $content = C('REGISTER_CONTENT');
            $data = [
                'content'     => $content,
                'mobile'      => $phone,
                'create_time' => time(),
                'end_time'    => time()
            ];
            vendor("Cxsms.Cxsms");
            $options = C('SMS_ACCOUNT');

            $Cxsms = new \Cxsms($options);
            $result = $Cxsms->send($phone, $content);
            if ($result && $result['returnsms']['returnstatus'] == 'Success') {
                $adds = $this->smslog_model->add($data);
                if( !$adds ) exit($this->returnApiError(BaseController::FATAL_ERROR, '数据库写入失败'));
            } else {
                exit($this->returnApiError(BaseController::FATAL_ERROR, '验证码发送失败'));
            }




            exit($this->returnApiSuccess());
        } else {
            exit($this->returnApiError('失败'));
        }

    }


    /**
     * 修改密码
     */
    public function modifypasswd()
    {

        $this->is_login();
        $mid = session('mid');
        $member = $this->member_model->where('id= '.$mid )->field('username')->find();
        $username = $member['username'];

        $oldpwd = I('post.oldpwd');
        $newpwd = I('post.newpwd');
        //
        $this->checkparam([$username, $newpwd, $oldpwd]);
        $where = ['username' => $username];
        $member = $this->member_model->where($where)->find();
        if (!$member) exit($this->error( '用户不存在'));

        if (!sp_compare_password($oldpwd, $member['password'])) exit($this->error( '旧密码错误'));

        $resutl = $this->member_model->where($where)->save(['password' => sp_password($newpwd)]);
        if ($resutl === false) exit($this->error(  '密码修改失败'));

        $this->redirect('Web/Member/setting_c');
    }



    /**
     * 找回密码
     */
    public function backpasswd()
    {


        $username = I('post.username');
        $code = I('post.code');
        $password = I('post.password');

        //checkparam
        $this->checkparam([$username, $code, $password]);
        if (strlen($password) < 5) exit($this->returnApiError(BaseController::FATAL_ERROR, '密码长度过段'));

        $where = ['username' => $username];
        $member = $this->member_model->where($where)->find();

        if (!$member) {
            exit($this->returnApiError(BaseController::FATAL_ERROR, '查无此用户'));
        };

        $result = $this->smslog_model->checkcode($username, $code);
        if (!$result) exit($this->returnApiError(BaseController::FATAL_ERROR, '验证码错误或过期'));


        $update = $this->member_model->where($where)->save(['password' => sp_password($password)]);
        if ($update === false) exit($this->returnApiError(BaseController::FATAL_ERROR, '密码修改失败'));

        exit($this->returnApiSuccess());

    }

    /**
     *个人信息
     */
    public function userinfo()
    {
        $this->is_login();
        $mid = session('mid');
        $member = $this->member_model->where('id='.$mid)->field('nickname,headimg')->find();
        $member['nickname'] = $member['nickname'] ? $member['nickname'] : "未设置";
        $member['headimg']  = $member['headimg']  ? setUrl($member['headimg']) : "/public/Web/images/user_head_cation.png";

        $this->assign('member',$member);
        $this->display();
    }

    /**
     * 修改头像
     */
    public function updateHeadimg()
    {
            $mid = session('mid');
            $this->is_login();
            $fileurl = upload_img('User');
            if (!$fileurl) exit($this->error('图片上传失败'));
            $result = $this->member_model->where(array('id' => $mid))->save(array('headimg' => $fileurl[0]));
            if ($result === false) exit($this->error('上传成功，写入数据库失败'));
            $this->success('上传成功');
    }

    /**
     * 修改昵称
     */
    public function updataNickname()
    {
        $nickname = I('post.nickname');
        $mid = session('mid');
        $result = $this->member_model->where(array('id' => $mid))->save(array('nickname' => $nickname));
        if ($result === false) exit($this->error('修改失败'));
        $this->success('修改成功');
    }


    /**
     * 宠物信息
     */
    public function petinfo()
    {
        $mid = session('mid');

        $mempet = $this->mempet_model->where(array('mid'=>$mid))->field('pid,pname,psex,pbirthday,headimg')->select();

        foreach( $mempet as $k => $v){
            $mempet[$k]['headimg']   = $this->setUrl($v['headimg']);
            $mempet[$k]['pbirthday'] = $this->mempet_model->getage(date('Y-m-d',$v['pbirthday']));
            $mempet[$k]['psex']      = $v['psex'] == 1 ? '公' : '母';
        }

     

        $this->assign('mempet',$mempet);
        $this->display();
    }


    /**
     * 宠物详细信息
     */
    public function petinfo_show()
    {
        $mid = session('mid');
        $pid = I('get.pid');

        $pet = $this->mempet_model->where(array('mid'=>$mid,'pid'=>$pid))->find();
        $pet['psex'] = $this->mempet_model->getSextoString($pet['psex']);
        $pet['ptype']= $this->mempet_model->petTypetoString($pet['ptype']);
        $pet['pbirthday']= date('Y-m-d', $pet['pbirthday']);// $this->mempet_model->getage($pet['pbirthday']);
        $pet['headimg'] = $this->setUrl($pet['headimg']);
        $pet['insect_time'] = $pet['insect_time'] ? date('Y-m-d', $pet['insect_time']) : '';
        $pet['vaccine_time'] = $pet['vaccine_time'] ? date('Y-m-d', $pet['vaccine_time']) : '';

        $this->assign('pet_detail',$pet);
        $this->display();
    }

    /**
     * 增加宠物信息
     */
    public function add_petinfo()
    {
        $this->getPetCategory();
        $this->display();
    }

    /**
     * 获取品种列表
     */
    public function getPetCategory()
    {

        $pet_type = session('ptype');
        if( !$pet_type ) $pet_type = '2';

        if ($pet_type == PetModel::PET_TYPE_CAT) {
            $result = F('PCPetCategoryCat');
        } else if ($pet_type == PetModel::PET_TYPE_DOG) {
            $result = F('PCPetCategoryDog');
        } else {

        }
        if (!$result) {
            $result = $this->pettype_model
                ->where(['pet_type' => $pet_type])
                ->field('pet_variety_id,pet_variety,pet_letter')
                ->order('pet_letter asc')
                ->select();

            if ($pet_type == PetModel::PET_TYPE_CAT) {
                F('PCPetCategoryCat', $result);
            } else if ($pet_type == PetModel::PET_TYPE_DOG) {
                F('PCPetCategoryDog', $result);
            } else {
            }
        }

        $this->assign( 'PetCategory',$result );


    }

    /**
     * 添加宠物
     */
    public function add_pet_post(){

        $mid = session('mid');
        $ptype    = I('post.ptype');
        $pname    = I('post.pname');
        $pbirthday= I('post.pbirthday');
        $psex     = I('post.psex');
        $Insect_time = I('post.Insect_time');
        $vaccine_time = I('post.vaccine_time');

        if( strtotime($pbirthday) >= time() ) exit($this->error( '添加时间不正确'));
        if( strtotime($Insect_time) >= time() ) exit($this->error( '添加驱虫时间不正确'));
        if( strtotime($vaccine_time) >= time() ) exit($this->error( '添加疫苗时间不正确'));
        $fileurl = upload_img('Pet');
        if (!$fileurl) exit($this->error(  '图片上传失败'));
        $data = array(
            'mid' => $mid,
            'ptype' => $ptype,
            'pname' => $pname,
            'pbirthday' => strtotime($pbirthday),
            'psex' => $psex,
            'headimg' => $fileurl[0],
            'Insect_time' => $Insect_time ? strtotime($Insect_time) : '',
            'vaccine_time' => $vaccine_time ? strtotime($vaccine_time) : '',
            'create_time' => time(),
        );
        $msg = array();
        if($Insect_time) {
            $msg[] = array(
                'mid' => $mid,
                'push_time' => ($this->getTime(strtotime($Insect_time),PushmsgModel::MSG_TYPE_INSECT)),
                'push_type' => PushmsgModel::MSG_TYPE_INSECT,
                'push_content' =>  '您的宠物该打疫苗了！',
            );
        }
        if($vaccine_time) {
            $msg[] = array(
                'mid' => $mid,
                'push_time' => ($this->getTime(strtotime($vaccine_time), PushmsgModel::MSG_TYPE_VACCINE)),
                'push_type' => PushmsgModel::MSG_TYPE_VACCINE,
                'push_content' =>  '您的宠物该打疫苗了！',
            );
        }
        $this->push_msgmodel->startTrans();
        $iscommit = true;
        if($Insect_time && $vaccine_time){
            if($this->push_msgmodel->addAll($msg) == false) {
                $iscommit = false;
            }
        }
        if($this->mempet_model->add($data) == false) {
            $iscommit = false;
        }


        if($iscommit) {
            $this->push_msgmodel->commit();
            exit($this->success('添加成功',U('Web/Member/petinfo')));
        } else {
            $this->push_msgmodel->rollback();
            exit($this->error( "添加失败"));
        }

    }



    //获取疫苗、驱虫下次推送时间
    public function getTime($time, $type) {
        $return = '';
        $year = date('Y', $time);
        $month = date('m', $time);
        $date = date('d', $time);
        if($type == PushmsgModel::MSG_TYPE_VACCINE) {
            if($date == 29 && $month == '02') {
                $date = 28;
            }
            $year += 1;
            $return = strtotime($year.'-'.$month.'-'.$date)-3600*24*15;
            if($return <= strtotime(date('Y-m-d 00:00:00',time()))) {
                if($date == 29 && $month == '02') {
                    $date = 28;
                }
                $year += 1;
                $return = strtotime($year.'-'.$month.'-'.$date);
            }
        }
        if($type == PushmsgModel::MSG_TYPE_INSECT) {
            $month += 3;
            if($month > 12) {
                $month = $month-12;
                $year += 1;
            }
            $return = strtotime($year.'-'.$month.'-'.$date)-3600*24*15;
            if($return <= strtotime(date('Y-m-d 00:00:00',time()))) {
                $month += 3;
                if($month > 12) {
                    $month = $month-12;
                    $year += 1;
                }
                $return = strtotime($year.'-'.$month.'-'.$date)-3600*24*15;
            }
        }
        return $return;
    }
    /**
     * 我的消息
     */
    public function tidings()
    {
        $this->msgList();
        $this->display();
    }

    /**
     * 消息列表
     */
    public function msgList() {
        $this->is_login();
        $mid = session('mid');
        $where = array(
            'mid' => $mid,
            'push_status' => PushmsgModel::PUSH_STATUS_YES,
        );
        $count = $this->push_msgmodel
            ->where($where)
            ->count();

        $page = $this->page($count,10);
        $lists = $this->push_msgmodel
            ->where($where)
            ->limit( $page->firstRow.','.$page->listRows )
            ->order('push_read asc, push_time desc')
            ->select();
       $str = '';

        foreach( $lists as $k => $v ){
            if( $v['push_read'] == 1 ){
                $str .= '<li><input type="checkbox" class="checkbox mag" name="pusmsg" value="'.$v['id'].'" /> <b>[未读]</b> '.$v['push_content'].'  <span>'.date('Y-m-d',$v['push_time']).'</span></li>';
            }
            if( $v['push_read'] == 2 ){
                $str .= '<li class="tidingsed"><input name="pusmsg" value="'.$v['id'].'" type="checkbox" class="checkbox mag" /> <b>[已读]</b> '.$v['push_content'].'  <span>'.date('Y-m-d',$v['push_time']).'</span></li>';
            }

        }

        $this->assign('lists',$str);
        $this->assign('Page',$page->show('Admin'));

    }


    /**
     * 修改消息为已读状态
     */
    public function editStatus() {

        $id = I('post.id');
        $where['id'] = array( 'in',$id );
        $rst = $this->push_msgmodel->where($where)->save(array('push_read' => 2));
        if( $rst ){
            $this->ajaxReturn(1);
        }else{
            $this->ajaxReturn(2);
        }
    }

    public function delMsg() {
        $mid = session('mid');
        $id = I('post.id');
        $where['mid'] = $mid;
        $where['id']  = array('in',$id);
        $rst = $this->push_msgmodel->where($where)
            ->delete();
        if(!$rst) {
            $this->ajaxReturn(2);
        }
        $this->ajaxReturn(1);
    }


    /**
     * 安全设置
     */
    public function setting()
    {
        $this->is_login();
        $mid = session('mid');
        $member = $this->member_model->where('id= '.$mid )->field('username,pay_password')->find();
        if( $member['pay_password'] ){
            $member['percent'] = '100';
            $member['level'] = '高';
        }else{
            $member['percent'] = '60';
            $member['level'] = '中';
        }
        $member['type_pass'] = empty($member['pay_password']) ? '立即设置' : '立即修改';


        $this->assign( 'member',$member );
        $this->display();
    }


    /**
     * 收货地址
     */
    public function address()
    {
        $this->is_login();
        $mid = session('mid');

        $result = $this->address_model
            ->where(array('mid' => $mid))
            ->order('id desc')
            ->select();
        $str = '';
        foreach( $result as $k => $v ){
            $province = $this->region_model->where('code='.$v['province'])->field('name')->find();
            $city = $this->region_model->where('code='.$v['city'])->field('name')->find();
            $country = $this->region_model->where('code='.$v['country'])->field('name')->find();
            $result[$k]['address'] = $province['name'].'/'.$city['name'].'/'.$country['name'].'/'.$v['address'];

            $addressdefa = $result[$k]['status'] == AddressModel::ADDRESS_DEFAULT ? '<span>[默认地址]</span>' : '';
            $str .= '<li id="add_ress_1">
							<div class="add_ress_left">
								<p>'. $result[$k]['fullname'].$addressdefa.'</p>
								<h1 id="phone">'. $result[$k]['phone'].'</h1>
								<p>'. $result[$k]['address'].'</p>
							</div>

							<div class="add_ress_right">
								<a href="javascript:;" name="'.$result[$k]['id'].'" class="delete_address_1">删除</a>
							</div>
					</li>';

        }

        $this->assign('address',$str );
        $this->display();

    }

    /**
     * 删除地址
     */
    public function addressDelete()
    {
        $this->is_login();
        $addressid = I('post.id');
        $result = $this->address_model->delete($addressid);
        if( $result ) {
            exit($this->ajaxReturn(1));
        }else{
            exit($this->ajaxReturn(2));
        }

    }

    /**
     * 删除宠物
     */
    public function delete_pet(){

        $pid      = I('post.pid');
        $result = $this->mempet_model->delete($pid);
        if( !$result ) exit($this->error('删除失败'));
        exit($this->success('删除成功',U('Web/Member/petinfo')));
    }


    public function add_address(){
        $provinc = $this->region_model->getProvincetoStr();
        $city    = $this->region_model->getCitytoStr($code = '110000' );
        $country = $this->region_model->getCitytoStr($code = '110100' );
        $data['provinc'] = $provinc;
        $data['city']    = $city;
        $data['country'] = $country;
        $this->assign('area',$data);
        $this->display();
    }

    public function getcity(){
        $province = I('post.province');
        $city     = $this->region_model->getCitytoStr( $province );
        $citycode = $this->region_model->getCity($province);
        $country  = $this->region_model->getCitytoStr( $citycode[0]['code'] );
        $data['city']    = $city;
        $data['country'] = $country;
        $this->ajaxReturn($data);

    }

    public function getcountry(){
        $city = I('post.city');
        $country  = $this->region_model->getCitytoStr( $city );
        $this->ajaxReturn($country);
    }

    /**
     * 增加地址
     */
    public function addressAdd()
    {

        $this->is_login();
        $mid      = session('mid');

        $fullname = I('post.fullname');
        $shopping_telephone = I('post.phone');
        $address  = I('post.address');
        $province = I('post.province');
        $city     = I('post.city');
        $country  = I('post.country');
        $status   = I('post.status') ? I('post.status') : 2;

        $data = array(
            'mid' => $mid,
            'fullname' => $fullname,
            'phone' => $shopping_telephone,
            'address' => $address,
            'province' => $province,
            'city' => $city,
            'country' => $country,
            'status' => $status,
        );

        $result = $this->address_model->add($data);
        if ($result){
            if( $status == AddressModel::ADDRESS_DEFAULT ){
                $this->address_model->where('mid='.$mid )->save(array('status' => AddressModel::ADDRESS_NOT_DEFAULT));
                $this->address_model->where('id='.$result)->save(array('status' => AddressModel::ADDRESS_DEFAULT));
            }
            exit($this->ajaxReturn(1));
        }
        else exit($this->ajaxReturn(2));
    }


    /**
     * 密码设置第一步
     */
    public function setting_a()
    {

        $this->is_login();
        $mid = session('mid');
        $member = $this->member_model->where('id= '.$mid )->field('username')->find();
        $member['type'] = I('post.type');
        $this->assign('member',$member);
        $this->display();
    }

    /**
     * 密码设置第二步
     */
    public function setting_b()
    {
        $this->is_login();
        $mid = session('mid');
        $member = $this->member_model->where('id= '.$mid )->field('username')->find();
        $username = $member['username'];
        $result = $this->smslog_model->checkcode($username, I('code'));
        if (!$result) exit($this->error('验证码错误或过期'));
        $member['code'] = I('code');
        $this->assign('member',$member);
        $this->display();
    }

    /**
     * 找回支付密码
     */
    public function backpaypasswd()
    {

        $this->is_login();
        $mid = session('mid');
        $member = $this->member_model->where('id= '.$mid )->field('username')->find();
        $username = $member['username'];

        $code = I('post.code');
        $password = I('post.password');

        //checkparam
        $this->checkparam([$username, $code, $password]);
        if (strlen($password) < 5) exit($this->error( '密码长度过段'));

        $where = ['username' => $username];
        $member = $this->member_model->where($where)->find();

        if (!$member) {
            exit($this->error( '查无此用户'));
        };

        $result = $this->smslog_model->checkcode($username, $code);
        if (!$result) exit($this->error( '验证码错误或过期'));


        $update = $this->member_model->where($where)->save(['pay_password' => sp_password($password)]);
        if ($update === false) exit($this->error( '密码修改失败'));

        $this->redirect('Web/Member/setting_c');



    }

    /**
     * 修改密码
     */
    public function setting_d(){
        $this->display();
    }

    /**
     * 密码设置第三步
     */
    public function setting_c()
    {
        $this->display();
    }


    public function user(){

        $this->is_login();
        $mid = session('mid');
        $member = $this->member_model->where(['id'=>$mid , 'shows'=> 1 ] )->field('username,pay_password')->find();
        if( $member['pay_password'] ){
            $member['percent'] = '100';
            $member['level'] = '高';
        }else{
            $member['percent'] = '60';
            $member['level'] = '中';
        }
        $this->sale_hot();
        $this->assign( 'member',$member );
        $this->getOrder();
        $this->display();
    }

    /**
     * 热卖商品
     */
    public function sale_hot(){
        $product = $this->product_model
            ->where( [ 'status' => 1 ] )
            ->order('sales_volume')
            ->limit('10')
            ->field('id,pro_name,smeta')
            ->select();

        foreach( $product as $k => $v ){
            $option_price = $this->product_option_model->where(['product_id'=> $v['id']])->min('option_price');
            $product[$k]['picture'] = setUrl(json_decode($v['smeta'],true)['0']['url']);
            $product[$k]['price'] = $option_price;
        }

        $this->assign('sale_hot',$product);
    }


    public function getOrder(){
        $mid = session('mid');
        $order_status_type =  'not_paid';
        $where = $this->order_model->getOrderTypeByCode($order_status_type);
        $result = $this->order_model
            ->where(['mid' => $mid])
            ->where($where)
            ->limit(3)
            ->field('id,mid,order_sn,order_price,order_type,create_time,cover,status,address')
            ->order('id desc')
            ->select();

        foreach ($result as $k => $v) {
            $address = json_decode($v['address'],true);
            $result[$k]['address'] = $address['fullname'];
            $result[$k]['create_time'] = dateDefault($v['create_time']);
            $result[$k]['cover'] = $v['cover'] ? setUrl($v['cover']) : '';

            $result[$k]['app_key_type'] = $v['order_type'] == OrderModel::ORDER_TYPE_GOODS ? '2' : '1';

            $result[$k]['app_key_return'] = '1';
            $result[$k]['status_value'] = $order_status_type;
            $result[$k]['return_str'] = $this->order_model->getOrderTypetoString($order_status_type);
            $result[$k]['refund'] = '';
            $result[$k]['refund_status'] = '';



        // 商品
        if ($result[$k]['order_type'] == OrderModel::ORDER_TYPE_GOODS) {
            $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'product as b on a.product_id = b.id';
            $list = $this->order_product_model
                ->alias('a')
                ->join($join)
                ->where(['order_id' => $v['id']])
                ->field('a.snapshot,b.pro_name as name,b.smeta')
                ->select();

            foreach ($list as $key => $val) {
                $val['snapshot'] = json_decode($val['snapshot'], true);
                $list[$key]['price'] = $val['snapshot']['option_price'];
                $val['smeta'] = json_decode($val['smeta'], true);

                $list[$key]['cover'] = $val['smeta'][0]['url'];
                if ($list[$key]['cover']) {
                    $list[$key]['cover'] = $this->setUrl($list[$key]['cover']);
                }


                unset($list[$key]['snapshot']);
                unset($list[$key]['smeta']);
            }

            $result[$k]['list'] = $list;
        }

        // 活体宠物
        if ($result[$k]['order_type'] == OrderModel::ORDER_TYPE_PET) {
            $list = $this->order_pet_model
                ->where(['order_id' => $v['id']])
                ->field('snapshot,price')
                ->find();

            if ($list) {
                $list['snapshot'] = json_decode($list['snapshot'], true);
                $list['name'] = $list['snapshot']['pet_name'];
                $list['cover'] = $v['cover'];
                unset($list['snapshot']);
            } else {
                $list = [];
            }
            $result[$k]['list'][] = $list;
        }

        // 运输
        if ($result[$k]['order_type'] == OrderModel::ORDER_TYPE_TRANSPORT) {

            $result[$k]['list'][] = [

                'name'  => $this->order_model->getOrdrTypetoString($result[$k]['order_type']),
                'price' => $v['order_price'],
                'cover' => $v['cover'],
            ];
        }

        // 殡仪
        if ($result[$k]['order_type'] == OrderModel::ORDER_TYPE_FUNERAL) {

            $result[$k]['list'][] = [
                'name'  => $this->order_model->getOrdrTypetoString($result[$k]['order_type']),
                'price' => $v['order_price'],
                'cover' => $v['cover'],
            ];
        }

        // 寄养
        if ($result[$k]['order_type'] == OrderModel::ORDER_TYPE_FOSTER) {

            $result[$k]['list'][] = [
                'name'  => $this->order_model->getOrdrTypetoString($result[$k]['order_type']),
                'price' => $v['order_price'],
                'cover' => $v['cover'],
            ];
        }

        // 婚介
        if ($result[$k]['order_type'] == OrderModel::ORDER_TYPE_MARRIAGE) {

            $result[$k]['list'][] = [
                'name'  => $this->order_model->getOrdrTypetoString($result[$k]['order_type']),
                'price' => $v['order_price'],
                'cover' => $v['cover'],
            ];
        }

        //医疗
        if ($result[$k]['order_type'] == OrderModel::ORDER_TYPE_HOSPITAL) {

            $result[$k]['list'][] = [
                'name'  => $this->order_model->getOrdrTypetoString($result[$k]['order_type']),
                'price' => $v['order_price'],
                'cover' => $v['cover'],


            ];
        }


        }
        $this->assign('order_list',$result);

    }


}
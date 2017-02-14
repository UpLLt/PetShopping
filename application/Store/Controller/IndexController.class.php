<?php
namespace Store\Controller;

use Common\Model\CommentModel;
use Common\Model\OrderModel;
use Common\Model\WithdrawalsModel;
use Consumer\Model\WalletBillModel;
use Consumer\Model\WalletModel;
use Merchant\Model\HospitalModel;
use Merchant\Model\HospitalShopModel;
use Merchant\Model\MemberShopModel;
use Merchant\Model\ShopTypeModel;
use Think\Controller;
use Web\Controller\BaseController;

class IndexController extends BaseController
{
    private $hos_shopmodel, $shop_typemodel, $hospital_model, $member_shopmodel, $withdrawal_model, $wallet_model,$wallet_bill_model, $comment_model;
    public function __construct()
    {
        parent::__construct();
        $this->hos_shopmodel = new HospitalShopModel();
        $this->shop_typemodel = new ShopTypeModel();
        $this->hospital_model = new HospitalModel();
        $this->member_shopmodel = new MemberShopModel();
        $this->withdrawal_model = new WithdrawalsModel();
        $this->wallet_model = new WalletModel();
        $this->wallet_bill_model = new WalletBillModel();
        $this->comment_model = new CommentModel();
    }

    /**
     * 登录页面
     */
    public function index() {
        /*if($_POST) {
//            dump($_POST);
            $this->display('detail');
        }*/
        $this->display();
    }

    /**
     * 生成验证码
     */
    public function verify_c(){
        $Verify = new \Think\Verify();
        $Verify->fontSize = 22;
        $Verify->length   = 4;
        $Verify->useNoise = false;
        $Verify->codeSet = '0123456789';
        $Verify->imageW = 150;
        $Verify->imageH = 50;
        $Verify->useCurve = false;
        //$Verify->expire = 600;
        $Verify->entry();
    }
    /**
     * 验证码检查
     */
    function check_verify($code, $id = ""){
        $verify = new \Think\Verify();
        return $verify->check($code, $id);
    }

    /**
     * 登录
     */
    public function login() {
        $data = I('post.');

        if($this->check_verify($data['code']) == false) {
            $this->error('验证码错误');
        }
//        dump($data);exit;
        $where['user_login'] = $data['username'];
        $where['user_pass'] = sp_password($data['password']);
        $rst = $this->member_shopmodel
            ->alias('a')
            ->join('left join ego_hospital_shop as b on a.id = b.sid ')
            ->where($where)
            ->field('a.user_login,a.user_img,a.status, a.user_pass ,a.id, b.id as hid')
            ->find();
//        dump($this->member_shopmodel->getLastSql());
        if(!$rst) {
            $this->error('用户名或密码错误');
        }
        if($rst['status'] == 0) {
            $this->error('账户已被冻结，请联系客服');
        }
//        dump($rst);exit;
        session('ADMIN_ID', 'ADMIN');
        session('username', $rst['user_login']);
        session('password', $rst['user_pass']);
        session('id', $rst['id']);
        session('hid', $rst['hid']);
        session('img', setUrl($rst['user_img']));
        $this->success('登录成功', U('Store/Index/detail'));
    }

    /**
     * 主页、商家详情
     */
    public function detail()
    {
        $info = checkLogin();
        if(empty($info)) {
            echo "<script>alert('请登录');</script>";
            $this->display('Index/index');
        }
        $hid = $info['hid'];
        $list  = $this->hos_shopmodel
            ->alias('a')
            ->join('LEFT JOIN ego_member_shop as b  on a.sid  = b.id ')
            ->join('LEFT JOIN ego_region as c on a.bu_province = c.code')
            ->join('LEFT JOIN ego_region as d on a.bu_city = d.code')
            ->join('LEFT JOIN ego_region as e on a.bu_country = e.code')
            ->field('a.*, c.name as province_name, d.name as city_name, e.name as country_name')
            ->where(array('a.id' => $hid))
            ->find();
        foreach (json_decode($list['hos_business_license'],true) as $k => $v) {
            $hos_business_license .= '<img style="margin-right: 25px; margin-left:13px; margin-bottom: 20px;" src="'.setUrl($v).'?imageView2/1/w/150/h/150"/>';
        }
        foreach (json_decode($list['hos_idcard'],true) as $k => $v) {
            $hos_idcard .= '<img style="margin-right: 25px; margin-left:13px; margin-bottom: 20px;" src="'.setUrl($v).'?imageView2/1/w/150/h/150"/>';
        }
        $images = json_decode($list['hos_image'], true);
        foreach ($images as $key => $val) {
            $img[] = $val['url'];
        }
        foreach ($img as $k => $v) {
            $hos_image .= '<img style="margin-right: 25px; margin-left:13px; margin-bottom: 20px;" src="'.setUrl($v).'?imageView2/1/w/150/h/150"/>';
        }

        $type = $this->shop_typemodel->getType($list['shop_type']);

        $detail = '<dl class="dl-horizontal">
                <dt>入驻类型:</dt>
                    <dd>'.$type['st_name'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>商家名称:</dt>
                    <dd style="margin-right: 30%;">'.$list['hos_name'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>地址:</dt>
                    <dd>'.$list['province_name'].$list['city_name'].$list['country_name'].$list['hos_address'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>经度:</dt>
                    <dd>'.$list['hos_longitude'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>纬度:</dt>
                    <dd>'.$list['hos_latitude'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>注册资本:</dt>
                    <dd style="margin-right: 30%;">'.$list['hos_registered_capital'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>公司规模:</dt>
                    <dd style="margin-right: 30%;">'.$list['hos_commany_size'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>联系人:</dt>
                    <dd style="margin-right: 30%;">'.$list['hos_contacts'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>联系电话:</dt>
                    <dd style="margin-right: 30%;">'.$list['hos_contacts_phone'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>公司简介:</dt>
                    <dd style="margin-right: 30%;">'.$list['hos_describe'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>营业执照:</dt>
                    <dd style="margin-right: 30%;">'.$hos_business_license.'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>法人身份证:</dt>
                    <dd style="margin-right: 30%;">'.$hos_idcard.'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>公司照片:</dt>
                    <dd style="margin-right: 30%;">'.$hos_image.'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>店铺二维码:</dt>
                    <dd style="margin-left: 190px;;"><img style="-webkit-user-select: none" src="https://www.mixiupet.com/index.php?g=Store&m=Index&a=pro_code&hid=mixiu-'.$info['hid'].'"></dd>
                </dl>';//http://localhost:8080/PHP-ebuy/index.php?g=&m=Index&a=pro_code&hid='.$info['hid'].'

        $this->assign('info', $info);
        $this->assign('detail', $detail);
        $this->display();
    }

    /**
     * 编辑页面
     */
    public function edit() {
        $info = checkLogin();
        if(empty($info)) {
            echo "<script>alert('请登录');</script>";
            $this->display('Index/index');
        }
        $hid =  $info['hid'];
        $list  = $this->hos_shopmodel
            ->alias('a')
            ->join('LEFT JOIN ego_member_shop as b  on a.sid  = b.id ')
//            ->field('a.*,me, ego_pet.pe_phone')
            ->where(array('a.id' => $hid))
            ->find();

        $this->assign('info', $info);
//        $this->assign('smeta', $url);
        $this->assign('smeta', json_decode($list['hos_image'], true));
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 修改
     */
    public function edit_post() {
        $info = checkLogin();
        if(empty($info)) {
            echo "<script>alert('请登录');</script>";
            $this->display('Index/index');
        }
        if (!empty($_POST['photos_alt']) && !empty($_POST['photos_url'])) {
            foreach ($_POST['photos_url'] as $key => $url) {
                $photourl = sp_asset_relative_url($url);
                $_POST['post']['smeta'][] = ["url" => $photourl, "alt" => $_POST['photos_alt'][$key]];
            }
        }
        $postdata = get_data(1);
        $postdata['hos_image'] = json_encode($_POST['post']['smeta']);
//        dump($postdata);exit;

        $postdata['update_time'] = time();
        $rst = $this->hos_shopmodel->where(array('id' => $info['hid']))->save($postdata);
        if($rst) {
            $this->success('修改成功');
        } else{
            $this->error('修改失败');
        }

    }

    /**
     * 提现记录列表
     */
    public function records() {
        $info = checkLogin();
        if(empty($info)) {
            echo "<script>alert('请登录');</script>";
            $this->display('Index/index');
        }
        $list = $this->withdrawal_model->where(array('mid' => $info['id'], 'wi_type' => 2))->select();
//        dump($this->withdrawal_model->getLastSql());exit;
        $lists = '';
        foreach($list as $k => $v) {
            $lists .= '<tr>
                            <td>' . ($k + 1) . '</td>
                            <td>' . sprintf("%0.2f",$v['wi_money']). '元</td>
                            <td>' . date('Y-m-d H:i:s',$v['create_time']) . '</td>
                            <td>' . '提现'  .'</td>
                            <td>' . $this->withdrawal_model->getStatusValus($v['wi_status']) . '</td>
                          </tr>';
        }
        $this->assign('info', $info);
        $this->assign('lists', $lists);
        $this->display();
    }

    /**
     * 评论管理
     */
    public function comment() {
        $info = checkLogin();
        if(empty($info)) {
            echo "<script>alert('请登录');</script>";
            $this->display('Index/index');
        }
        $fields = [
            'nickname' => ["field" => "c.nickname", "operator" => "=", 'datatype' => 'string'],
        ];

        $where_ands = [];
        if (IS_POST) {
            foreach ($fields as $param => $val) {
                if (isset($_POST[$param]) && !empty($_POST[$param])) {
                    $operator = $val['operator'];
                    $field = $val['field'];
                    $datatype = $val['datatype'];
                    $get = $_POST[$param];
                    $_GET[$param] = $get;

                    if ($operator == "like") {
                        $get = "%$get%";
                    }
                    if ($datatype == 'time')
                        $get = strtotime($get);
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        } else {
            foreach ($fields as $param => $val) {
                if (isset($_GET[$param]) && !empty($_GET[$param])) {
                    $operator = $val['operator'];
                    $field = $val['field'];
                    $datatype = $val['datatype'];
                    $get = $_GET[$param];
                    if ($operator == "like") {
                        $get = "%$get%";
                    }
                    if ($datatype == 'time')
                        $get = strtotime($get);
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        }

        $where = join(" and ", $where_ands);

//        dump($where);exit;
        $where .= $where ? 'and a.relevance_id='.$info['hid'] : 'a.relevance_id='.$info['hid'];
        if(I('post.reply_status') == 1) {
            $where .= ' and a.reply_status = 1';
        }
        if(I('post.reply_status') == 2) {
            $where .= ' and a.reply_status = 2';
        }
        $where .= ' and order_type='.OrderModel::ORDER_TYPE_HOSPITAL. ' and status = 2';
        $count = $this->comment_model
            ->where($where)
            ->alias('a')
            ->join('LEFT JOIN ego_member as c  on a.mid = c.id ')
            ->count();
        $page = $this->page($count,C('PAGE_NUMBER'));
        $list  = $this->comment_model
            ->alias('a')
            ->join('LEFT JOIN ego_member as c  on a.mid = c.id ')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('a.*, c.nickname')
            ->order('a.create_time desc')
            ->where($where)
            ->select();
//        dump($this->comment_model->getLastSql());exit;
        $marriage = '';
        // 2、已完成，3、待分配，4、已分配
        foreach( $list as $k => $v ){
            if(empty($v['content'])) {
                $result[$k]['str_manage'] = '待评价';
            } else {
                if($v['reply_status'] == 1) {
                    $result[$k]['str_manage'] = '待回复 | '. '<a data-toggle="modal"   class="add_ext" name="'.$v['id'].'"  onclick="">马上回复</a>';
                } else {
                    $result[$k]['str_manage'] = '已回复 ';
                }
            }

            $marriage .= '<tr>
                            <td>' . ($k + 1) . '</td>
                            <td>' . $v['nickname'] . '</td>
                            <td>' . $v['content'] . '</td>
                            <td>' . $v['replay']  . '<form action="Store/Index/reply" method="post"><input type="text" name="id" class="hidden" value="'.$v['id'].'"><input type="text" name="reply" class="hidden" style="height: 35px; margin-top: 10px;"><button class="hidden btn btn-default" name="button" style="margin-right: 25px;">保存</button></form></td>
                            <td>' . date('Y/m/d H:i',$v['create_time']) . '</td>
                            <td>' . $result[$k]['str_manage'] . '</td>
                          </tr>';
        }

        $this->assign('Page',$page->show());
        $this->assign('lists', $marriage);
        $this->assign('formget', I(''));
        $this->assign('info', $info);
        $this->display();
    }

    public function reply() {
        $id = I('post.id');
        $reply = I('post.reply');
        if(empty($reply)) {
            $this->error('回复不能为空');exit;
        }
        $rst = $this->comment_model->where(array('id' => $id))->save(array('replay' => $reply, 'reply_status' => 2));
        if($rst) {
            $this->success('成功');
        } else {
            $this->error('失败');
        }
    }

    /**
     *  分页
     * {@inheritDoc}
     * @see \Common\Controller\AppframeController::page()
     */
    protected function page($total_size = 1, $page_size = 0, $current_page = 1, $listRows = 6, $pageParam = '', $pageLink = '', $static = false) {
        if ($page_size == 0) {
            $page_size = C("PAGE_LISTROWS");
        }

        if (empty($pageParam)) {
            $pageParam = C("VAR_PAGE");
        }

        $page = new \Page($total_size, $page_size, $current_page, $listRows, $pageParam, $pageLink, $static);
        $page->SetPager('Store', '{first}{prev}&nbsp;{liststart}{list}&nbsp;{next}{last}<span>共{recordcount}条数据</span>', array("listlong" => "4", "first" => "首页", "last" => "尾页", "prev" => "上一页", "next" => "下一页", "list" => "*", "disabledclass" => ""));
        return $page;
    }

    /**
     * 提现
     */
    public function withdraw() {
        $info = checkLogin();
        if(empty($info)) {
            echo "<script>alert('请登录');</script>";
            $this->display('Index/index');
        }
        $username = session('username');
        $member_shop_info = $this->member_shopmodel->where(array('user_login' => $username))->find();
        $banks = $this->withdrawal_model->getBank();
        $options = '';
        foreach ($banks as $k => $v) {
            $options .= '<option value="'.$v['bcode'].'">'.$v['bname'].'</option>';
        }

        if(IS_POST) {
            $money     = I('post.wi_money');
            $bank      = I('post.wi_bank');
            $bank_card = I('post.wi_bank_card');
            $realname  = I('post.wi_bank_name');
            $pay_password = I('post.taking_pass');

            $where = [
                'id' => $info['id'],
                'taking_pass' => sp_password($pay_password),
            ];
            $members = $this->member_shopmodel
                ->where($where)
                ->find();
            if (!$members) {
                echo "<script>alert('密码错误');</script>";
                $this->display();exit;
            }

//            $this->checkparam(array($mid, $token, $bank, $money, $realname , $bank_card ,$pay_password));
//            if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

            $w = date('d');
            if( $w != 1 || $w != 10 || $w != 20 )   {
                echo "<script>alert('当前时间不允许提现');</script>";
                $this->display();exit;
            }

            $is_bank = $this->withdrawal_model->getBankStr($bank);
            if(empty( $is_bank ) ) {
                echo "<script>alert('银行不正确');</script>";
                $this->display();exit;
            }

            if( strlen($bank_card) < 15 ) {
                echo "<script>alert('银行卡号格式不正确');</script>";
                $this->display();exit;
            }

//            if ($this->withdrawal_model->where(array('wi_status' => WithdrawalsModel::EXAMINE_WAIT, 'mid' => $info['id']))->count() > 0) {
//                echo "<script>alert('有未处理完成的提现申请，请等待处理完毕');</script>";
//                $this->display();exit;
//            }

            //资金变动前的金额
//            $before_change = $this->wallet_model->getBalance($mid);
            if ($members['balance'] < $money) {
                echo "<script>alert('余额不足');</script>";
                $this->display();exit;
            }

            $data = array(
                'mid' => $info['id'],
                'wi_money' => $money,
                'wi_bank' =>  $bank,
                'wi_bank_card' => $bank_card,
                'wi_bank_name' => $info['username'],
                'wi_status' => WithdrawalsModel::EXAMINE_WAIT,
                'create_time' => time(),
                'wi_type'=> WithdrawalsModel::WIDTH_TYPE_PRODUCT,
                'update_time'=> time(),
            );

            /*if (!$this->withdrawal_model->create($data))
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, $this->withdrawal_model->getError()));*/

            $iscommit = true;
            $this->wallet_model->startTrans();

            //新增提现申请记录
            if (!$this->withdrawal_model->add($data))
                $iscommit = false;

            //用户钱包操作
            $result_wallet = $this->member_shopmodel->editBalance($info['id'], $money);
            if ($result_wallet === false) {
                $iscommit = false;
            }
            //钱包流水记录
            $result_wallet_bill = $this->wallet_bill_model->addBill($info['id'], $money, $members['balance'], WalletBillModel::BUY_STORE, WalletBillModel::BILL_TYPE_OUT);
            if ($result_wallet_bill === false) {
                $iscommit = false;
            }

            if ($iscommit) {
                $this->wallet_model->commit();
                $this->success('成功');
            } else {
                $this->wallet_model->rollback();
                $this->error('失败');
            }
        }

        $this->assign('info', $info);
        $this->assign('options', $options);
        $this->assign('memberinfo', $member_shop_info);
        $this->display();
    }

    /**
     * 生成二维码
     */
    public function pro_code() {
        $id =  I('get.hid');
        if(empty($id)) {
            $this->error();
        }
        vendor('phpqrcode.phpqrcode');
        \QRcode::png($id, false, $errorCorrectionLevel, 8, 2);
    }


}
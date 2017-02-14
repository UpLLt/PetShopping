<?php
namespace Merchant\Controller;
use Common\Controller\AdminbaseController;
use Common\Model\OrderModel;
use Merchant\Model\HospitalModel;
use Merchant\Model\HospitalShopModel;
use Merchant\Model\MemberShopModel;
use Merchant\Model\ShopTypeModel;

class IndexController extends AdminbaseController {
    private $shop_typemodel, $hos_shopmodel, $member_shopmodel , $hosptal_model;
    private $order_model ;
    public function __construct()
    {
        parent::__construct();
        $this->shop_typemodel = new ShopTypeModel();
        $this->hos_shopmodel = new HospitalShopModel();
        $this->member_shopmodel = new MemberShopModel();
        $this->hosptal_model = new HospitalModel();
        $this->order_model = new OrderModel();

    }



    public function order()
    {
        $this->order_lists();

        $this->display();
    }

    private function order_lists()
    {
        $fields = [
            'keyword'  => ["field" => "a.order_sn", "operator" => "like", 'datatype' => 'string'],
            'username' => ["field" => "b.username", "operator" => "like", 'datatype' => 'string'],
            'status'   => ["field" => "a.status", "operator" => "=", 'datatype' => 'string'],
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

        if(  $where ){
            $where = $where . ' and '.'a.order_type='.OrderModel::ORDER_TYPE_HOSPITAL .' and a.shows = 1 ';
        }else{
            $where['a.order_type'] = OrderModel::ORDER_TYPE_HOSPITAL;
            $where['a.shows']      = 1;
        }

        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'member as b on a.mid = b.id';
        $join2 = 'LEFT JOIN ' . C('DB_PREFIX') . 'hospital as c on a.id = c.order_sid';
        $count = $this->order_model
            ->alias('a')
            ->join($join)
            ->join($join2)
            ->where($where)
            ->count();
        $page = $this->page($count, 20);
        $result = $this->order_model
            ->alias('a')
            ->join($join)
            ->join($join2)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->where($where)
            ->field('a.*,b.username,c.hid')
            ->order('create_time desc')
            ->select();

        $tablebody = '';

        foreach ($result as $k => $v) {
            $result[$k]['str_manage'] .= ' <a class="js-ajax-delete"  href="' . U('Index/delete', ['id' => $v['id']]) . '">删除</a>';

            $tablebody .= '<tr>
                <td>' . ($k + 1) . '</td>
                <td>' . $v['id'] . '</td>
                <td>' . $v['order_sn'] . '</td>
                <td>' . $v['username'] . '</td>
                <td>' . $v['order_price'] . '</td>
                <td>' . $this->order_model->getStatusMertoString($v['status']) . '</td>
                <td>' . $this->order_model->payTypetoString($v['pay_type']) . '</td>
                <td>' . ($v['pay_time'] ? dateDefault($v['pay_time']) : '') . '</td>
                <td>' . $this->hos_shopmodel->getHospitalName($v['hid']) . '</td>
                <td>' . $this->member_shopmodel->getMmeberShopUser($v['hid']) . '</td>
                <td>' . dateDefault($v['create_time']) . '</td>
                <td>' . $result[$k]['str_manage'] . '</td>
            </tr>';
        }


        $this->assign('statusOption', $this->hosptal_model->getStatusOption(I('status')));
        $this->assign('formget', I(''));
        $this->assign('tablebody', $tablebody);
        $this->assign("Page", $page->show());
    }

    /**
     * 软删除
     */
    public function delete(){
        $id = I('id');
        if (empty($id)) $this->error('empty');
        if ($this->order_model->deleteOrder($id) === false) $this->error('error');
        $this->success('success');
    }


    public function lists(){
        //商家类型
        /*$types = $this->shop_typemodel->select();

        foreach ($types as $k => $v) {
            $options .= '<option value="'.$v['st_id'].'">'.$v['st_name'].'</option>';
        }*/

        $fields = [
            'user_login' => ["field" => "b.user_login", "operator" => "=", 'datatype' => 'string'],
            'hos_name' => ["field" => "a.hos_name", "operator" => "=", 'datatype' => 'string'],
            'st_id'   => ["field" => "a.shop_type",   "operator" => "=", 'datatype' => 'string'],
            'shop_status'   => ["field" => "a.shop_status",   "operator" => "=", 'datatype' => 'string'],
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

        $count = $this->hos_shopmodel
            ->where($where)
            ->alias('a')
            ->join('LEFT JOIN ego_member_shop as b  on a.sid  = b.id ')
            ->count();
        $page = $this->page($count,C('PAGE_NUMBER'));
        $list  = $this->hos_shopmodel
            ->alias('a')
            ->join('LEFT JOIN ego_member_shop as b  on a.sid  = b.id ')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('a.*, b.user_login,b.balance')
            ->order('a.id desc')
            ->where($where)
            ->select();

        foreach($list as $k => $v) {
            $type = $this->shop_typemodel->getType($v['shop_type']);
            $status = $this->hos_shopmodel->getStatus($v['shop_status']);
            $action = '';
            if($status == '待审核') {
                $action = '<a class="js-ajax-delete"  href="' . U('Index/delete_shop', array('id' => $v['id'])) . '">'.'删除'.'</a>'.' | 待审核 | '. '<a href="' . U('Index/edit_shop', ['id' => $v['id'],'status' => '2']) .                 '">审核通过</a>'.' | '. '<a href="' . U('Index/edit_shop', ['id' => $v['id'],'status' =>                       '3']) . '">审核拒绝</a>';
            } elseif($status == '审核通过') {
                $action = '<a class="js-ajax-delete"  href="' . U('Index/delete_shop', array('id' => $v['id'])) . '">'.'删除'.'</a>'.' | 审核通过'.' | '. '<a href="' . U('Index/edit_shop', ['id' => $v['id'],'status' =>                    '4']) . '">冻结商家</a>';
            } elseif ($status == '审核拒绝') {
                $action = '<a class="js-ajax-delete"  href="' . U('Index/delete_shop', array('id' => $v['id'])) . '">'.'删除'.'</a>'.' | 审核拒绝';
            } elseif ($status == '已冻结') {
                $action = '<a class="js-ajax-delete"  href="' . U('Index/delete_shop', array('id' => $v['id'])) . '">'.'删除'.'</a>'.' | 已冻结'.' | '. '<a href="' . U('Index/edit_shop', ['id' => $v['id'],'status' =>                     '5']) . '">解除冻结</a>';
            }
            $marriage .= '<tr>
                            <td>' . ($k + 1) . '</td>
                            <td>' . $type['st_name'] . '</td>
                            <td>' . $v['user_login'] . '</td>
                            <td>' . '<a href="' . U('Index/detail', ['id' => $v['id']]) . '">'.$v['hos_name'] . '</a></td>
                            <td>' . $v['hos_address'] . '</td>
                            <td>' . $v['hos_contacts_phone'] . '</td>
                            <td>' . date('Y-m-d H:i:s',$v['time']) . '</td>
                            <td>' . ($v['balance'] ? $v['balance'] : '-' ). '</td>
                            <td>' . $action . '</td>
                          </tr>';
        }

        $this->assign('lists', $marriage);
//        $this->assign('options', $options);
        $this->assign('formget', I(''));
        $this->display();
    }

    public function edit_shop() {
        $id = I('get.id');
        $status = I('get.status');
        $action = $this->hos_shopmodel->getStatus($status);
        $where['id'] = $id;
        $data['shop_status'] = $status;
        $this->hos_shopmodel->startTrans();
        $iscommit = true;
        if($status == '5') {
            $data['shop_status'] = 2;
        }
        $rst = $this->hos_shopmodel->where($where)->save($data);
        if(!$rst) {
            $iscommit = false;
        }
        $content = '';

        $info = $this->hos_shopmodel->where($where)->field('hos_contacts_phone, sid')->find();//dump($info);exit;
        $mobile = $info['hos_contacts_phone'];
        if($action == '审核通过') {
            $password = $this->getPassword();
            $content = '【'.C("SMS_ACCOUNT.company").'】您提交的店铺入驻已审核通过，用户名为接收该短信的手机号，密码是'.$password.'支付密码为手机号后6位';
            $res = $this->member_shopmodel->addMember($mobile, $password);
            if(!$res) {
                $iscommit = false;
            }
            $result = $this->hos_shopmodel->where($where) ->save(array('sid' => $res));//绑定账户id
            if(!$result) {
                $iscommit = false;
            }
        } elseif($action == '审核拒绝') {
            $content ='【'.C("SMS_ACCOUNT.company").'】您提交的店铺入驻审核被拒绝';
        } elseif($action == '已冻结') {
            $content = '【'.C("SMS_ACCOUNT.company").'】您的店铺由于被投诉，已被冻结，详情联系客服';
            $free = $this->member_shopmodel->editStatus($info['sid'], 0);//冻结账号
//            dump($free);
            if(!$free) {
                $iscommit = false;
            }
        } elseif($action == '解冻') {
            $content = '【'.C("SMS_ACCOUNT.company").'】您店铺已经解冻，详情请联系客服.';
            $yes = $this->member_shopmodel->editStatus($info['sid'], 1);//解冻账号
            if(!$yes) {
                $iscommit = false;
            }
        }

        if (strlen($mobile) != 11)
            exit($this->success('手机号码错误'));

        vendor("Cxsms.Cxsms");
        $options = C("SMS_ACCOUNT");
//        dump($options);exit;
        $Cxsms = new \Cxsms($options);
        $result = $Cxsms->send($mobile, $content);
        if (!$result || !$result['returnsms']['returnstatus'] == 'Success') {
            $iscommit = false;

        }
        if($iscommit) {
            $this->hos_shopmodel->commit();
            $this->success();
        } else {
            $this->hos_shopmodel->rollback();
            $this->error();
        }

    }

    /**
     * 生成商家密码
     * @return string
     */
    public function getPassword() {
        // 密码字符集，可任意添加你需要的字符
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';

        $password = '';
        $length = strlen($chars);
        for ( $i = 0; $i < 6; $i++ )
        {
            // 这里提供两种字符获取方式
            // 第一种是使用 substr 截取$chars中的任意一位字符；
            // 第二种是取字符数组 $chars 的任意元素
            // $password .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
            $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }

        return $password;
    }

    //商家详情
    public function detail() {
        $id = I('get.id');
        $list  = $this->hos_shopmodel
            ->alias('a')
            ->join('LEFT JOIN ego_member_shop as b  on a.sid  = b.id ')
            ->join('LEFT JOIN ego_region as c on a.bu_province = c.code')
            ->join('LEFT JOIN ego_region as d on a.bu_city = d.code')
            ->join('LEFT JOIN ego_region as e on a.bu_country = e.code')
            ->field('a.*, c.name as province_name, d.name as city_name, e.name as country_name')
            ->where(array('a.id' => $id))
            ->find();
//        dump($list);
        $type = $this->shop_typemodel->getType($list['shop_type']);//dump($type);

        $hos_business_license .= '<img style="margin-right: 25px; margin-left:13px; margin-bottom: 20px;" src="'.setUrl($list['hos_business_license']).'?imageView2/1/w/150/h/150"/>';

        foreach (json_decode($list['hos_idcard'],true) as $k => $v) {
            $hos_idcard .= '<img style="margin-right: 25px; margin-left:13px; margin-bottom: 20px;" src="'.setUrl($v).'?imageView2/1/w/150/h/150"/>';
        }
        foreach (json_decode($list['hos_image'],true) as $k => $v) {
            $hos_image .= '<img style="margin-right: 25px; margin-left:13px; margin-bottom: 20px;" src="'.setUrl($v['url']).'?imageView2/1/w/150/h/150"/>';
        }

        $type = $this->shop_typemodel->getType($list['shop_type']);
        /*$status = $this->hos_shopmodel->getStatus($list['shop_status']);
        if($status == '待审核') {
            $action = '待审核 | '. '<a href="' . U('Index/edit_shop', ['id' => $list['id'],'status' => '2']) .                 '">审核通过</a>'.' | '. '<a href="' . U('Index/edit_shop', ['id' => $list['id'],'status' =>                       '3']) . '">审核拒绝</a>';
        } elseif($status == '审核通过') {
            $action = '审核通过'.' | '. '<a href="' . U('Index/edit_shop', ['id' => $list['id'],'status' =>                    '4']) . '">冻结商家</a>';
        } elseif ($status == '审核拒绝') {
            $action = '审核拒绝';
        } elseif ($status == '已冻结') {
            $action = '已冻结'.' | '. '<a href="' . U('Index/edit_shop', ['id' => $list['id'],'status' =>                     '5']) . '">解除冻结</a>';
        }*/
        $detail = '<dl class="dl-horizontal">
                <dt>入驻类型:</dt>
                    <dd>'.$type['st_name'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>商家名称:</dt>
                    <dd style="margin-right: 30%;">'.$list['hos_name'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>详细地址:</dt>
                    <dd>'.$list['province_name'].$list['city_name'].$list['country_name'].$list['hos_address'].'</dd>
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
                </dl> ';
        /*<dl class="dl-horizontal">
                    <dd style="margin-right: 30%;">'.$action.'</dd>
                </dl>*/

        $this->assign('detail', $detail);
        $this->display();
    }

    //删除店铺
    public function delete_shop() {
        $this->success();exit;
        $hid = I('get.id');

        $hosInfo = $this->hos_shopmodel->where(array('id' => $hid))->find();

        $this->hos_shopmodel->startTrans();
        $iscommit = true;

        if($this->hos_shopmodel->where(array('id' => $hid))->delete() == false) {
            $iscommit = false;
        }
        //查询所属医院的订单
        $hos_order = $this->hosptal_model->where(array('hid'=> $hid))->field('order_sid')->select();
        $ids = array();
        if($hos_order) {
            foreach($hos_order as $k => $v) {
                array_push($ids, $v['order_sid']);
            }
        }
        //有订单则删除订单
        if(!empty($ids)) {
            if($this->hosptal_model->where(array('hid' => $hid))->delete() == false) {
                $iscommit = false;
            }
            if($this->order_model->where(array('id' => array('in', $ids)))->setField('shows', 0) == false) {
                $iscommit = false;
            }
        }

        //有账户则删除账户
        if($hosInfo['sid']) {
            if($this->member_shopmodel->where(array('id' => $hosInfo['sid']))->delete() == false) {
                $iscommit = false;
            }
        }
//var_dump($this->hosptal_model->getLastSql());
        if($iscommit) {
//            $this->hos_shopmodel->commit();
            $this->hos_shopmodel->rollback();
            $this->success('success', U('Merchant/Index/lists'));
        } else {
            $this->hos_shopmodel->rollback();
            $this->error('fail', U('Merchant/Index/lists'));
        }
    }
}
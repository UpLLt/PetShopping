<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/19
 * Time: 15:46
 */

namespace Consumer\Controller;


use Common\Controller\AdminbaseController;

use Common\Model\AddressModel;
use Common\Model\RechargeModel;
use Consumer\Model\MemberModel;
use Consumer\Model\WalletBillModel;
use Consumer\Model\WalletModel;


class MemberController extends AdminbaseController
{
    private $member_model;
    private $address_model;
    private $recharge_model;
    private $wallet_model;
    private $wallet_bill_model;

    public function __construct()
    {
        parent::__construct();
        $this->member_model = new MemberModel();
        $this->address_model = new AddressModel();
        $this->recharge_model = new RechargeModel();
        $this->wallet_model = new WalletModel();
        $this->wallet_bill_model = new WalletBillModel();
    }

    public function lists()
    {
        $this->_lists();
        $this->display();
    }



    private function _lists()
    {
        $fields = [
            'keyword' => ["field" => "username", "operator" => "=", 'datatype' => 'string'],
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
        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'wallet as b on a.id = b.mid';
        $join2 = 'LEFT JOIN ' . C('DB_PREFIX') . 'com_score as c on a.id = c.sco_member_id';

        $count = $this->member_model
            ->alias('a')
            ->where($where)
            ->join($join)
            ->join($join2)
            ->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->member_model
            ->alias('a')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->where($where)
            ->join($join)
            ->join($join2)
            ->field('a.* , b.balance , c.sco_now, c.sco_level')
            ->order('a.id desc')
            ->select();


        $categorys = '';
        foreach ($result as $k => $v) {

            $result[$k]['identity_phone'] = '<a href="javascript:open_iframe_dialog(\'' . U('Member/activityinfo', ['id' => $v['id']]) . '\',\'活动详情\',\'\',\'90%\')">活动详情</a>';
//            $result[$k]['headimg'] = '<a target="_blank" href="' . $v['headimg'] . '">查看</a>';

            $result[$k]['str_manage'] = '<a class="" href="' . U('Member/address', ['id' => $v['id']]) . '">收货地址</a> |
                                         <a class="js-ajax-delete" href="' . U('Member/delete', ['id' => $v['id']]) . '">删除</a> |
                                         <a data-toggle="modal" data-target="#myModal_remarks"  class="add_ext"  onclick="" cool="' . $v['username'] . '" name="' . $v['id'] . '">备注</a> |
                                         <a data-toggle="modal" data-target="#myModal"  class="add_ext"  onclick="" cool="' . $v['username'] . '" name="' . $v['id'] . '">用户充值</a>';
//            $result[$k]['str_manage'] .= ' | ';
//            $result[$k]['str_manage'] .= '<a class="" href="' . U('Member/authAction', array('id' => $v['id'])) . '">身份证认证</a>';

            $categorys .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $result[$k]['username'] . '</td>
            <td>' . $result[$k]['nickname'] . '</td>
            <td>' . $v['balance'] . '</td>
            <td>' . $v['sco_now'] . '</td>
            <td>' . $v['sco_level'] . '</td>
            <td>' . $v['remarks'] . '</td>
            <td>' . dateDefault($v['create_time']) . '</td>
            <td style="white-space:nowrap;">' . $result[$k]['str_manage'] . '</td>
        </tr>';

        }

        $this->assign('formget', I(''));
        $this->assign('categorys', $categorys);
        $this->assign("Page", $page->show());
    }

    public function delete()
    {
        $mid = intval(I('id'));
        $result = $this->member_model->where(['id' => $mid])->delete();
        if ($result) {
            $this->success('success');
        } else {
            $this->error('error');
        }

    }


    /**
     * 用户备注
     */
    public function saveRemarks(){
        $id = I('post.mid');
        $remarks = I('post.remarks');
        $result = $this->member_model->where(['id'=>$id])->setField('remarks',$remarks);
        if($result)
            $this->success('Success');
           else
            $this->error('error');
    }


    public function address()
    {
        $this->_addresslist();
        $this->display();
    }

    private function _addresslist()
    {
        $mid = intval(I('id'));
        if (empty($mid)) $this->error('empty');

        $where['mid'] = $mid;
        $count = $this->address_model
            ->where($where)
            ->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->address_model
            ->limit($page->firstRow . ',' . $page->listRows)
            ->where($where)
            ->order('id desc')
            ->select();

        foreach ($result as $k => $v) {

            $result[$k]['str_manage'] = '';

            $categorys .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $result[$k]['fullname'] . '</td>
            <td>' . $result[$k]['address'] . '</td>
            <td>' . $result[$k]['city'] . '</td>
            <td>' . $result[$k]['postcode'] . '</td>
            <td>' . $result[$k]['shopping_telephone'] . '</td>
            <td>' . $result[$k]['status'] . '</td>
            <td style="white-space:nowrap;">' . $result[$k]['str_manage'] . '</td>
        </tr>';
        }

        $this->assign('formget', I(''));
        $this->assign('categorys', $categorys);
        $this->assign("Page", $page->show());
    }

    public function authAction()
    {
        $id = intval(I('get.id'));
        if (empty($id)) $this->error('error');
        $result = $this->member_model->find($id);
        if ($result['authentication'] == 1) {
            $this->error('已审核');
        }

        $save = $this->member_model->where(['id' => $id])->save(['authentication' => '1']);
        if ($save === false)
            $this->error('操作失败');
        else
            $this->success('操作成功');

    }

    public function activityinfo()
    {
        $id = intval(I('get.id'));
        if (empty($id)) $this->error('error');

        $result = $this->member_model->find($id);
        $identity_phone = json_decode($result['identity_phone'], true);

        $front = '';
        $back = '';

        foreach ($identity_phone as $k => $v) {
            if ($v['key'] == 'front') {
                $front = $v;
            }
            if ($v['key'] == 'back') {
                $back = $v;
            }
        }

        $this->assign('member_data', $result);
        $this->assign('front', $front);
        $this->assign('back', $back);
        $this->display();
    }


    /**
     * 充值
     */
    public function Recharge(){

        $pay_money = I('post.Recharge');
        $mid = I('post.mid');
        if ($pay_money <= 0) {
            $this->error('请输入正确的充值金额');
        }


        $out_trade_no = $this->recharge_model->getOrderNumber();
        $insert_data = [
            'mid'           => $mid,
            'out_trade_no'  => $out_trade_no,
            'total_fee'     => $pay_money,
            'status'        => RechargeModel::NOTIFY_STATUS_SUCCESS,
            'notify_status' => RechargeModel::STATUS_PAY_SUCCESS,
            'paytype'       => RechargeModel::PAY_ADMIN,
            'notify_time'    => time(),
            'update_time'    => time(),
            'create_time'   => time(),


        ];
        if (!$this->recharge_model->create($insert_data))
            exit($this->error( $this->recharge_model->getError()));
        if (!$this->recharge_model->add())
            exit($this->error( '失败'));

        $iscommit = true;
        $this->recharge_model->startTrans();

        $before_change = $this->wallet_model->getBalance($mid);
        $result_wallet = $this->wallet_model->addmoney($mid, $pay_money);
        if ($result_wallet === false) {
            $iscommit = false;
        }

        //钱包流水记录
        $result_wallet_bill = $this->wallet_bill_model->addBill($mid, $pay_money, $before_change, '充值', WalletBillModel::BILL_TYPE_IN);

        if ($result_wallet_bill == false) {
            $iscommit = false;
        }


        if ($iscommit) {
            $this->recharge_model->commit();
            $this->success('充值成功');

        } else {
            $this->recharge_model->rollback();
            $this->success('充值失败');
        }
    }



}
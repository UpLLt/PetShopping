<?php
namespace Marriage\Controller;
use Common\Controller\AdminbaseController;
use Common\Model\MarriageModel;
use Common\Model\OrderModel;
use Community\Model\ComScoreModel;
use Consumer\Model\CouponModel;
use Consumer\Model\WalletBillModel;
use Consumer\Model\WalletModel;
use Marriage\Model\WeddingRulesModel;

class OrderController extends AdminbaseController  {

    private $marriage_model, $order_model,$wallet_model, $wallet_billmodel, $com_sco_model, $coupon_model,$wedding_rule_model;
    public function __construct()
    {
        parent::__construct();
        $this->marriage_model = new MarriageModel();
        $this->order_model = new OrderModel();
        $this->wallet_model = new WalletModel();
        $this->wallet_billmodel = new WalletBillModel();
        $this->com_sco_model = new ComScoreModel();
        $this->coupon_model = new CouponModel();
        $this->wedding_rule_model = new WeddingRulesModel();
    }

    public function lists(){
        $fields = [
            'order_sn' => ["field" => "ego_order.order_sn", "operator" => "=", 'datatype' => 'string'],
            'nickname' => ["field" => "ego_member.nickname", "operator" => "=", 'datatype' => 'string'],
            'status'   => ["field" => "ego_order.status",   "operator" => "=", 'datatype' => 'string'],
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
        $where .= empty( $where ) ? 'ego_order.`shows` = 1 ' : 'and ego_order.`shows` = 1';

        $count = $this->marriage_model
            ->where($where)
            ->alias('a')
            ->join('LEFT JOIN ego_order  on a.order_sid  = ego_order.id ')
            ->join('LEFT JOIN ego_member  on ego_order.mid = ego_member.id ')
            ->count();
        $page = $this->page($count,C('PAGE_NUMBER'));
        $list  = $this->marriage_model
            ->alias('a')
            ->join('LEFT JOIN ego_order  on a.order_sid =ego_order.id ')
            ->join('LEFT JOIN ego_member  on ego_order.mid =ego_member.id ')
            ->join('LEFT JOIN ego_pet on a.pid = ego_pet.pid')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('id desc')
            ->field('a.*,ego_order.create_time, ego_order.order_sn,ego_order.status, ego_member.nickname, ego_member.username, ego_pet.pe_name, ego_pet.pe_phone')
            ->where($where)
            ->select();
//        dump($list);exit;
        $marriage = '';
        // 2、已完成，3、待分配，4、已分配


        foreach( $list as $k => $v ){
            $status = $this->marriage_model->getStatus($v['status']);
            if($status == '待联系') {
                $result[$k]['str_manage'] = '待联系 | '. '<a href="' . U('Order/edit_order', ['id' => $v['order_sid'],'status' => OrderModel::STATUS_SEND]) . '">已联系</a>'.' | '. '<a href="' . U('Order/cancelOrder', ['id' => $v['order_sid'],'status' => OrderModel::STATUS_CANCEL]) . '">取消订单</a>'.' | '. '<a data-toggle="modal" data-target="#myModal"  class="add_ext"  onclick="" name="'.$v['id'].'">添加备注</a>';
            } elseif($status == '已联系') {
                $result[$k]['str_manage'] = '已联系 | '. '<a href="' . U('Order/complete_order', ['id' => $v['order_sid'],'status' => OrderModel::STATUS_COMPLETE]) . '">已完成</a>'.' | '. '<a href="' . U('Order/cancelOrder', ['id' => $v['order_sid'],'status' => OrderModel::STATUS_CANCEL]) . '">取消订单</a>'.' | '. '<a data-toggle="modal" data-target="#myModal"  class="add_ext" name="'.$v['id'].'"  onclick="">添加备注</a>';
            } elseif($status == '已完成') {
                $result[$k]['str_manage'] = '已完成'.' | '.  '<a data-toggle="modal" data-target="#myModal"  class="add_ext"  onclick="" name="'.$v['id'].'">添加备注</a>';
            } elseif($status == '待付款') {
                $result[$k]['str_manage'] = '待付款'.' | '. '<a data-toggle="modal" data-target="#myModal"  class="add_ext"  onclick="" name="'.$v['id'].'">添加备注</a>';
            } elseif($status == '用户取消') {
                $result[$k]['str_manage'] = '已取消'.' | '. '<a data-toggle="modal" data-target="#myModal"  class="add_ext"  onclick="" name="'.$v['id'].'">添加备注</a>';
            }

            $result[$k]['str_manage'] .= '| <a  class="js-ajax-delete"  href="' . U('Order/delete', ['id' => $v['order_sid']]) . '">删除</a>';

            $service[$k] .= '';
            $service[$k] .= $v['ma_ovulation'] == '0.00' ? '' : '测排卵';
            $service[$k] .= $v['ma_sperm'] == '0.00' ? '' : ($service[$k] ? ' | 验精' : '验精');
            $marriage .= '<tr>
                            <td>' . ($k + 1) . '</td>
                            <td>' . $v['order_sn'] . '</td>
                            <td>' . $v['pe_name'] . '</td>
                            <td>' . $v['nickname']  . '</td>
                            <td>' . $v['username'] . '</td>
                            <td>' . $v['pe_phone'] . '</td>
                            <td>' . $service[$k] . '</td>
                            <td>' . $v['ma_sprice'] . '</td>
                            <td>' . $v['ma_extra']. '</td>
                            <td>' . date('Y-m-d H:i:s',$v['create_time']). '</td>
                            <td>' . $result[$k]['str_manage'] . '</td>
                          </tr>';
        }

        $this->assign('Page',$page->show());
        $this->assign('lists', $marriage);
        $this->assign('formget', I(''));
        $this->display();
    }

    /**
     * 修改订单状态
     */
    public function edit_order() {
        $id= I('get.id');
        $status = I('get.status');
        $rst = $this->order_model->where(array('id' => $id))->save(array('status' => $status));
        if($rst) {
            $this->success();
        } else {
            $this->error();
        }
    }

    /*
     * 软删除订单
     */
    public function delete(){
        $id = I('id');
        if (empty($id)) $this->error('empty');
        if ($this->order_model->deleteOrder($id) === false) $this->error('error');
        $this->success('success');
    }



    /**
     * 完成订单，转钱给用户
     */
    public function complete_order() {
        $id= I('get.id');
        $status = I('get.status');
        $where['a.id'] = $id;
        $orderInfo = $this->order_model
            ->alias('a')
            ->join('LEFT JOIN ego_marriage as b on a.id = b.order_sid')
            ->where($where)
            ->field('a.mid, b.order_sid, b.ma_breeding_price')
            ->find();
        $rules = $this->wedding_rule_model->getField('we-commission');
        //dump($orderInfo);
        //dump($rules);//exit;
        $member_money = sprintf("%0.2f",$orderInfo['ma_breeding_price']*(100-$rules)/100);
//        dump($member_money);exit;

        $this->order_model->startTrans();
        $iscommit = true;

        //添加流水
        $before  = $this->wallet_model->getBalance($orderInfo['mid']);
        $bill = $this->wallet_billmodel->addBill($orderInfo['mid'], $member_money, $before,'婚介所得', WalletBillModel::BILL_TYPE_IN);
        if(!$bill) {
            $iscommit = false;
            $error = '1';
        }
        //增加余额
        if($this->wallet_model->addMoney($orderInfo['mid'], $member_money) == false) {
            $iscommit = false;
            $error = '2';
        }
        //dump($this->wallet_model->getLastSql());exit;$iscommit=false;
        $rst = $this->order_model->where(array('id' => $id))->save(array('status' => $status));
        if(!$rst) {
            $iscommit = false;
        }
        if($iscommit) {
            $this->order_model->commit();
            $this->success('钱款已转到用户余额');
        } else {
            $this->order_model->rollback();
            $this->error($error);
        }

    }

    /**
     * 取消订单
     */
    public function cancelOrder() {
        $id = I('get.id');
        $status = I('get.status');
        $orderInfo = $this->order_model
            ->where(array('id' => $id))
            ->field('mid, order_price, score, coupon_id')
            ->find();

        $this->order_model->startTrans();
        $iscommit = true;
        //修改订单状态
        if($this->order_model->setStatus($id, $status) == false) {
            $iscommit = false;
        }

        //退积分
        if($orderInfo['score']) {
            if($this->com_sco_model->saveScore($orderInfo['mid'], $orderInfo['score']) == false) {
                $iscommit = false;
            }
        }
        //退优惠券
        if($orderInfo['coupon_id']) {
            $info = $this->coupon_model->where(array('coupon_id' => $orderInfo['coupon_id']))->find();
            //检查是否超过有效期
            if($info['expiration_time'] < time()) {
                $coup_status = CouponModel::STATUS_OVERDUE;
            } else {
                $coup_status = CouponModel::STATUS_VALIDITY;
            }
            //修改状态
            $rst = $this->coupon_model
                ->where(array('coupon_id' => $orderInfo['coupon_id']))
                ->save(array('cou_status' => $coup_status));
            if(!$rst) {
                $iscommit = false;
            }
        }
        //增加余额
        if($orderInfo['order_price']) {
            //添加流水
            $before  = $this->wallet_model->getBalance($orderInfo['mid']);
            $bill = $this->wallet_billmodel->addBill($orderInfo['mid'], $orderInfo['order_price'], $before,'取消订单退款', WalletBillModel::BILL_TYPE_IN);
            if(!$bill) {
                $iscommit = false;
                $error = '5';
            }
            if($this->wallet_model->addMoney($orderInfo['mid'], $orderInfo['order_price']) == false) {
                $iscommit = false;
                $error = '2';
            }
        }

        if($iscommit) {
            $this->order_model->commit();
            $this->success('订单已取消，钱款、积分、优惠券已退回用户');
        } else {
            $this->order_model->rollback();
            $this->error('取消订单失败');
        }

    }

    /**
     * 添加备注
     */
    public function edit() {
        $ma_extra = I('post.ma_extra');
        $id = I('post.id');
        $rst = $this->marriage_model->where(array('id' => $id))->save(array('ma_extra' => $ma_extra));
        if($rst) {
            $this->success();
        } else{
            $this->error();
        }

    }
}
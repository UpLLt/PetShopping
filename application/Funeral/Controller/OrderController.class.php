<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/28
 * Time: 10:02
 */

namespace Funeral\Controller;


use Common\Controller\AdminbaseController;
use Common\Model\OrderModel;
use Common\Model\RegionModel;
use Community\Model\ComScoreModel;
use Consumer\Model\CouponModel;
use Consumer\Model\WalletBillModel;
use Consumer\Model\WalletModel;
use Funeral\Model\BuriedModel;

class OrderController extends AdminbaseController
{
    private $buried_model, $order_model,$wallet_model, $wallet_billmodel, $com_sco_model, $coupon_model ,$region_model;

    public function __construct()
    {
        parent::__construct();
        $this->buried_model = new BuriedModel();
        $this->order_model = new OrderModel();
        $this->wallet_model = new WalletModel();
        $this->wallet_billmodel = new WalletBillModel();
        $this->com_sco_model = new ComScoreModel();
        $this->coupon_model = new CouponModel();
        $this->region_model = new RegionModel();
    }

    public function lists() {
        $fields = [
            'order_sn' => ["field" => "ego_order.order_sn", "operator" => "=", 'datatype' => 'string'],
            'nickname' => ["field" => "ego_member.username", "operator" => "=", 'datatype' => 'string'],
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
        $where .= empty( $where ) ? 'ego_order.shows = 1 ' : 'and ego_order.shows = 1';
        $count = $this->buried_model
            ->where($where)
            ->alias('a')
            ->join('LEFT JOIN ego_order  on a.order_id  = ego_order.id ')
            ->join('LEFT JOIN ego_member  on ego_order.mid = ego_member.id ')
            ->count();
        $page = $this->page($count,C('PAGE_NUMBER'));
        $list  = $this->buried_model
            ->alias('a')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->join('LEFT JOIN ego_order  on a.order_id =ego_order.id ')
            ->join('LEFT JOIN ego_member  on ego_order.mid =ego_member.id ')
            ->field('a.*,ego_member.username,ego_order.create_time,ego_order.order_sn,ego_order.status')
            ->order('ego_order.create_time desc')
            ->where($where)
            ->select();

        $buried = '';
        // 1、取消，2、待付款，3、待分配，8、已分配
        $result = '';
        foreach( $list as $k => $v ){
            $status = $this->buried_model->getStatus($v['status']);
            if($status == '待分配') {
                $result[$k]['str_manage'] .= '待分配 | '. '<a href="' . U('Order/edit_order', ['id' => $v['order_id'],'status' => OrderModel::STATUS_SEND]) . '">已分配</a>'.' | '. '<a href="' . U('Order/cancelOrder', ['id' => $v['order_id'],'status' => OrderModel::STATUS_CANCEL]) . '">取消订单</a>';
            } elseif($status == '已分配') {
                $result[$k]['str_manage'] .= '已分配 | '. '<a href="' . U('Order/edit_order', ['id' => $v['order_id'],'status' => OrderModel::STATUS_COMPLETE]) . '">已完成</a>'.' | '. '<a href="' . U('Order/cancelOrder', ['id' => $v['order_id'],'status' => OrderModel::STATUS_CANCEL]) . '">取消订单</a>';
            } elseif($status == '已完成') {
                $result[$k]['str_manage'] .= '已完成';
            } elseif($status == '待付款') {
                $result[$k]['str_manage'] .= '待付款';
            } elseif($status == '用户取消') {
                $result[$k]['str_manage'] .= '已取消';
            }

            $result[$k]['str_manage'] .= '| <a  class="js-ajax-delete"  href="' . U('Order/delete', ['id' => $v['order_id']]) . '">删除</a>';

            $buried .= '<tr>
                            <td>' . ($k + 1) . '</td>
                            <td>' . $v['order_sn'] . '</td>
                            <td>' . $v['username'] . '</td>
                            <td>' . $this->buried_model->getPickup($v['bu_method'])  . '</td>
                            <td>' . $this->region_model->getAllarea($v['bu_area']).'/'. $v['bu_address'] . '</td>
                            <td>' . $v['bu_contacts'] . '</td>
                            <td>' . $v['bu_contacts_phone'] . '</td>
                            <td>' . $v['bu_bury'] . '</td>
                            <td>' . ($v['bu_buried'] == 1 ? '是' : '否') . '</td>
                            <td>' . $v['bu_price'] . '</td>
                            <td>' . $v['bu_weight'] . 'KG</td>
                            <td>' . date('Y-m-d H:i:s',$v['create_time']) . '</td>
                            <td>' . $result[$k]['str_manage'] . '</td>
                          </tr>';
        }
        $this->assign('formget', I(''));
        $this->assign('Page',$page->show());
        $this->assign('lists', $buried);
        $this->display();
    }


    public function delete(){
        $id = I('id');
        if (empty($id)) $this->error('empty');
        if ($this->order_model->deleteOrder($id) === false) $this->error('error');
        $this->success('success');
    }


    /**
     * 修改订单状态
     */
    public function edit_order() {
        $id = I('get.id');
        $status = I('get.status');
//        dump($id);dump($status);exit;
        $rst = $this->order_model->where(array('id' => $id))->save(array('status' => $status));
        if($rst) {
            $this->success();
        } else {
            $this->error();
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

//        dump($orderInfo);exit;
        $this->order_model->startTrans();
        $iscommit = true;
        //修改订单状态
        if($this->order_model->setStatus($id, $status) == false) {
            $error = '1';
            $iscommit = false;
        }

        //退积分
        if($orderInfo['score']) {
            if($this->com_sco_model->saveScore($orderInfo['mid'], $orderInfo['score']) == false) {
                $iscommit = false;
                $error = '3';
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
                $error = '4';
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
            $this->error('取消订单失败'.$error);
        }

    }
}
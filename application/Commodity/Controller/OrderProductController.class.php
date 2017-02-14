<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/12/7
 * Time: 上午10:09
 */

namespace Commodity\Controller;


use Common\Controller\AdminbaseController;
use Common\Model\OrderModel;
use Common\Model\OrderProductModel;
use Common\Model\OrderRefundModel;
use Common\Model\RegionModel;
use Common\Model\SmslogModel;
use Community\Model\ComScoreModel;
use Consumer\Model\CouponModel;
use Consumer\Model\MemberModel;
use Consumer\Model\WalletBillModel;
use Consumer\Model\WalletModel;

/**
 * 商品订单
 * Class OrderProductController
 * @package Commodity\Controller
 */
class OrderProductController extends AdminbaseController
{
    private $order_product_model;
    private $order_model;
    private $member_model;

    private $coupon_model;
    private $com_sco_model;

    private $refund_model;
    private $wallet_model;
    private $wallet_bill_model;
    private $smslog_model;
    private $region_model;


    public function __construct()
    {
        $this->order_product_model = new OrderProductModel();
        $this->order_model = new OrderModel();
        $this->member_model = new MemberModel();
        $this->coupon_model = new CouponModel();
        $this->com_sco_model = new ComScoreModel();

        $this->refund_model = new OrderRefundModel();
        $this->smslog_model = new SmslogModel();
        $this->wallet_model = new WalletModel();
        $this->wallet_bill_model = new WalletBillModel();
        $this->region_model = new RegionModel();
        parent::__construct();
    }

    public function lists()
    {
        $this->_lists();
        $this->display();
    }

    private function _lists()
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
            $where = $where . ' and '.'a.order_type='.OrderModel::ORDER_TYPE_GOODS .' and a.shows = 1 ';
        }else{
            $where['a.order_type'] = OrderModel::ORDER_TYPE_GOODS;
            $where['a.shows']      = 1;
        }

        $join = 'LEFT JOIN ' . C('DB_PREFIX') .  'member as b on a.mid = b.id';
        $count = $this->order_model
            ->alias('a')
            ->join($join)
            ->where($where)
            ->count();
        $page = $this->page($count, 20);
        $result = $this->order_model
            ->alias('a')
            ->join($join)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->where($where)
            ->field('a.*,b.username')
            ->order('create_time desc')
            ->select();

        $tablebody = '';

        foreach ($result as $k => $v) {
            $result[$k]['str_manage'] .= '<a class="js-ajax-btn-dialog" href="' . U('OrderProduct/info', ['id' => $v['id']]) . '">订单详情</a>';


            if ($v['status'] == OrderModel::STATUS_PAY_SUCCESS) {
                $result[$k]['str_manage'] .= ' | ';
//                $result[$k]['str_manage'] .= '<a class="js-ajax-btn-dialog" href="' . U('OrderProduct/send', ['id' => $v['id']]) . '">发货</a>';
                $result[$k]['str_manage'] .= '<a data-toggle="modal" data-target="#myModal"  class="add_ext"  onclick=""   name="' . $v['id'] . '">发货</a>';
            }
            $result[$k]['str_manage'] .= '| <a class="js-ajax-delete"  href="' . U('OrderProduct/delete', ['id' => $v['id']]) . '">删除</a>';

            $tablebody .= '<tr>
                <td>' . ($k + 1) . '</td>
                <td>' . $v['id'] . '</td>
                <td>' . $v['order_sn'] . '</td>
                <td>' . $v['username'] . '</td>
                <td>' . $v['order_price'] . '</td>
                <td>' . $this->order_product_model->getStatustoString($v['status']) . '</td>
                <td>' . $this->order_model->payTypetoString($v['pay_type']) . '</td>
                <td>' . ($v['pay_time'] ? dateDefault($v['pay_time']) : '') . '</td>
                <td>' . ($v['returns_status'] == 1 ? '退款申请中' : '') . '</td>
                <td>' . dateDefault($v['create_time']) . '</td>
                <td>' . $result[$k]['str_manage'] . '</td>
            </tr>';
        }


        $this->assign('statusOption', $this->order_model->getStatusOption(I('status')));
        $this->assign('formget', I(''));
        $this->assign('tablebody', $tablebody);
        $this->assign("Page", $page->show());
    }


    /**
     * 详情
     *
     * @param $id
     */
    public function info($id)
    {
        $id = intval($id);
        if (empty($id)) $this->error('empty');
        $data = $this->order_model->find($id);
        $data_member = $this->member_model->find($data['mid']);

        $data['address'] = json_decode($data['address'], true);

        $data['address']['address'] = $this->region_model->getNamForCode($data['address']['province']).'/'.$this->region_model->getNamForCode($data['address']['city']).'/'.$this->region_model->getNamForCode($data['address']['country']).'/'.$data['address']['address'];

        $data['status'] = $this->order_model->getStatustoString($data['status']);

        $categorys = '';

        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'product as b on a.product_id = b.id';
        $result = $this->order_product_model
            ->alias('a')
            ->join($join)
            ->where(['order_id' => $id])
            ->field('a.*,b.pro_name')
            ->select();

        $total = '';
        foreach ($result as $k => $v) {
            $v['snapshot'] = json_decode($v['snapshot'], true);
            $result[$k]['every_price'] = $v['snapshot']['option_price'] * $v['quantity'];
            $total += $result[$k]['every_price'];
            $result[$k]['every_price'] = number_format($result[$k]['every_price'], 2);
            $categorys .= '<tr>
                <td>' . ($k + 1) . '</td>
                <td>' . $v['pro_name'] . '</td>
                <td>' . $v['snapshot']['option_name'] . '</td>
                <td>' . $v['snapshot']['option_price'] . '</td>
                <td>' . $v['quantity'] . '</td>
                <td>' . $result[$k]['every_price'] . '</td>
                ';
        }

        if ($data['coupon_id'])
            $coupon_price = $this->coupon_model->getCouponValue($data['coupon_id']);
        if ($data['score'])
            $data['score'] = $this->com_sco_model->sconToMoney($data['score']);


        $this->assign('total_price', number_format($total, 2));
        $this->assign('coupon_price', number_format($coupon_price, 2));
        $this->assign('categorys', $categorys);
        $this->assign('data_order', $data);
        $this->assign('data_member', $data_member);
        $this->display();
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


    /**
     * 设置物流
     */
    public function set_logistics()
    {
        $order_id = intval(I('order_id'));
        $logistics_number = I('post.logistics_number');
        $logistics_company = I('post.logistics_company');

        if (empty($order_id) || empty($logistics_company) || empty($logistics_number))
            $this->error('数据不能为空');

        $result = $this->order_model
            ->where(['id' => $order_id])
            ->save(['logistics_number' => $logistics_number, 'logistics_company' => $logistics_company]);

        if ($result === false) {
            $this->error('失败');
        } else {
            $this->success('成功');
        }
    }


    /**
     * 发货
     */
    public function send($id)
    {

        if (empty($id)) $this->error('empty');

        $result = $this->order_model->find($id);
        if (!$result) $this->error('订单不存在');

        if ($result['status'] != OrderModel::STATUS_PAY_SUCCESS) {
            $this->error('订单未付款');
        }

        if ($this->order_model->setStatus($id, OrderModel::STATUS_SEND) === false) {
            $this->error('失败');
        } else {

            $this->success('成功');
        }
    }

    /**
     * 发货并填写运单号
     */
    public function sendNum()
    {
        $id = I('post.order_id');
        $logistics_number = I('post.logistics_number');
        $logistics_company = I('post.logistics_company');





        if (empty($id)) $this->error('empty');
        if (empty($logistics_number)) $this->error('运单号为空');
        if (empty($logistics_company)) $this->error('快递公司为空');

        $result = $this->order_model->find($id);
        if (!$result) $this->error('订单不存在');

        if ($result['status'] != OrderModel::STATUS_PAY_SUCCESS) {
            $this->error('订单未付款');
        }

        $where['id'] = $id;
        $data = [
            'logistics_number'  => $logistics_number,
            'logistics_company' => $logistics_company,
            'status'            => OrderModel::STATUS_SEND,
        ];
        $rst = $this->order_model->where($where)->save($data);
        if ($rst === false) {
            $this->error('失败');
        } else {


            $username = $this->order_model
                ->where(['ego_order.id'=>$id])
                ->join('LEFT JOIN ego_member on ego_member.id = ego_order.mid')
                ->field('ego_order.*,ego_member.username')
                ->find();
            $content = C('SEND_CONTENT');
            $data = [
                'content'     => $content,
                'mobile'      => $username['username'],
                'create_time' => time(),
                'end_time'    => time()
            ];
            vendor("Cxsms.Cxsms");
            $options = C('SMS_ACCOUNT');

            $Cxsms = new \Cxsms($options);
            $result = $Cxsms->send($username['username'], $content);
            if ($result && $result['returnsms']['returnstatus'] == 'Success') {
                $adds = $this->smslog_model->add($data);
                if( !$adds ) exit($this->error( '数据库写入失败'));
            } else {
                exit($this->error(  '验证码发送失败'));
            }
            $this->success('成功');
        }
    }


    public function refundlists()
    {

        $this->_refundLists();
        $this->display();
    }

    private function _refundLists()
    {
        $fields = [
            'keyword' => ["field" => "b.username", "operator" => "like", 'datatype' => 'string'],
            'order_sn' => ["field" => "c.order_sn", "operator" => "like", 'datatype' => 'string'],
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

        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'member as b on a.mid = b.id';
        $join2 = 'LEFT JOIN ' . C('DB_PREFIX') . 'order as c on a.order_id = c.id';
        $count = $this->refund_model
            ->alias('a')
            ->join($join)
            ->join($join2)
            ->where($where)
            ->count();
        $page = $this->page($count, 20);
        $result = $this->refund_model
            ->alias('a')
            ->join($join)
            ->join($join2)
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('id desc')
            ->field('a.*,b.username,c.order_sn')
            ->select();
        $tablebody = '';

        foreach ($result as $k => $v) {
            $result[$k]['str_manage'] = '';

            if ($v['status'] == OrderRefundModel::STATUS_APPLY) {
                $result[$k]['str_manage'] .= $result[$k]['str_manage'] ? ' | ' : '';
                $result[$k]['str_manage'] .= '<a class="js-ajax-btn-dialog" href="' . U('OrderProduct/apply_agree', ['id' => $v['id']]) . '">同意</a>';

                $result[$k]['str_manage'] .= $result[$k]['str_manage'] ? ' | ' : '';
                $result[$k]['str_manage'] .= '<a class="js-ajax-btn-dialog" href="' . U('OrderProduct/apply_refuse', ['id' => $v['id']]) . '">拒绝</a>';
            }

            if ($v['status'] == OrderRefundModel::STATUS_APPLY_SEND) {
                $result[$k]['str_manage'] .= $result[$k]['str_manage'] ? ' | ' : '';
                $result[$k]['str_manage'] .= '<a class="js-ajax-btn-dialog" href="' . U('OrderProduct/receipt', ['id' => $v['id']]) . '">收货</a>';

            }

            if ($v['status'] == OrderRefundModel::STATUS_GET_GOODS) {
                $result[$k]['str_manage'] .= $result[$k]['str_manage'] ? ' | ' : '';
                $result[$k]['str_manage'] .= '<a class="js-ajax-btn-dialog" href="' . U('OrderProduct/drawback', ['id' => $v['id']]) . '">退款</a>';
            }
            $detail = '<a class="js-ajax-btn-dialog" href="' . U('OrderProduct/refundinfo', ['id' => $v['order_id']]) . '">订单详情</a>';;

            $tablebody .= '<tr>
                            <td>' . ($k + 1) . '</td>
                            <td>' . $v['order_sn'] . '</td>
                            <td>' . $v['username'] . '</td>
                            <td>' . $this->order_model->getRefundDesc($v['argument']) . '</td>
                            <td>' . $v['logistics_company'] . '</td>
                            <td>' . $v['logistics_number'] . '</td>
                            <td>' . ($v['image'] ? '<a target="view_window" href="' . setUrl($v['image']) . '">查看</a>' : '') . '</td>
                            <td>' . $this->refund_model->getStatusToString($v['status']) . '</td>
                            <td>' . dateDefault($v['create_time']) . '</td>
                            <td>' . $detail . '</td>
                            <td>' . $result[$k]['str_manage'] . '</td>
                          </tr>';
        }

        $this->assign('formget', I(''));
        $this->assign('tablebody', $tablebody);
        $this->assign("Page", $page->show());
    }

        /* 退货详情
        *
        * @param $id
        */
    public function refundinfo($id)
    {
        $id = intval($id);
        if (empty($id)) $this->error('empty');
        $data = $this->order_model->find($id);

        $data_member = $this->member_model->find($data['mid']);

        $data['address'] = json_decode($data['address'], true);

        $data['status'] = $this->order_model->getStatustoString($data['status']);

        $categorys = '';

        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'product as b on a.product_id = b.id';
        $result = $this->order_product_model
            ->alias('a')
            ->join($join)
            ->where(['order_id' => $id])
            ->field('a.*,b.pro_name')
            ->select();

        $total = '';
        foreach ($result as $k => $v) {
            $v['snapshot'] = json_decode($v['snapshot'], true);
            $result[$k]['every_price'] = $v['snapshot']['option_price'] * $v['quantity'];
            $total += $result[$k]['every_price'];
            $result[$k]['every_price'] = number_format($result[$k]['every_price'], 2);
            $categorys .= '<tr>
                <td>' . ($k + 1) . '</td>
                <td>' . $v['pro_name'] . '</td>
                <td>' . $v['snapshot']['option_name'] . '</td>
                <td>' . $v['snapshot']['option_price'] . '</td>
                <td>' . $v['quantity'] . '</td>
                <td>' . $result[$k]['every_price'] . '</td>
                ';
        }

        if ($data['coupon_id'])
            $coupon_price = $this->coupon_model->getCouponValue($data['coupon_id']);
        if ($data['score'])
            $data['score'] = $this->com_sco_model->sconToMoney($data['score']);

        $this->assign('total_price', number_format($total, 2));
        $this->assign('coupon_price', number_format($coupon_price, 2));
        $this->assign('categorys', $categorys);
        $this->assign('data_order', $data);
        $this->assign('data_member', $data_member);
        $this->display();
    }

    /**
     * 同意
     *
     * @param $id
     */
    public function apply_agree($id)
    {
        if (empty($id)) $this->error('empty');

        $result = $this->refund_model->find($id);
        if (!$result) $this->error('操作的数据不存在');

        if ($result['status'] != OrderRefundModel::STATUS_APPLY) {
            $this->error('只有申请状态可以被操作');
        }

        if ($this->refund_model->setStatus($id, OrderRefundModel::STATUS_APPLY_OK) === false)
            $this->error();
        $this->success('success');
    }


    /**
     * 拒绝
     *
     * @param $id
     */
    public function apply_refuse($id)
    {
        if (empty($id)) $this->error('empty');

        $result = $this->refund_model->find($id);
        if (!$result) $this->error('操作的数据不存在');

        $order = $this->order_model->find($result['order_id']);
        if (!$order) $this->error('操作的数据不存在');


        $iscommit = true;
        $this->refund_model->startTrans();

        if ($result['status'] != OrderRefundModel::STATUS_APPLY) {
            $this->error('只有申请状态可以被拒绝');
        }

        if ($this->order_model->setStatus($order['id'], OrderModel::STATUS_COMPLETE) === false) {
            $iscommit = false;
        }

        if ($this->refund_model->setStatus($id, OrderRefundModel::STATUS_APPLY_NO) === false)
            $iscommit = false;


        if ($iscommit) {
            $this->refund_model->commit();
            $this->error('失败');
        } else {
            $this->refund_model->rollback();
            $this->success('成功');
        }

    }

    /**
     * 收货
     *
     * @param $id
     */
    public function receipt($id)
    {
        if (empty($id)) $this->error('empty');

        $result = $this->refund_model->find($id);
        if (!$result) $this->error('操作的数据不存在');

        if ($result['status'] != OrderRefundModel::STATUS_APPLY_SEND) {
            $this->error('用户未发货');
        }

        if ($this->refund_model->setStatus($id, OrderRefundModel::STATUS_GET_GOODS) === false)
            $this->error();
        $this->success('success');

    }

    /**
     * 退款
     *
     * @param $id
     */
    public function drawback($id)
    {
        if (empty($id)) $this->error('empty');

        $result = $this->refund_model->find($id);
        if (!$result) $this->error('操作的数据不存在');

        $order = $this->order_model->find($result['order_id']);
        if (!$order) $this->error('操作的数据不存在');

        if ($result['status'] != OrderRefundModel::STATUS_GET_GOODS) {
            $this->error('还没有收到包裹，不能退款');
        }

        $iscommit = true;
        $this->refund_model->startTrans();

        if ($this->refund_model->setStatus($id, OrderRefundModel::STATUS_BACK_BALANCE) === false)
            $iscommit = false;

        if ($this->wallet_model->addMoney($result['mid'], $order['order_price']) === false)
            $iscommit = false;

        $before = $this->wallet_model->getBalance($result['mid']);

        if ($this->wallet_bill_model->addBill($result['mid'], $order['order_price'], $before, '退款', WalletBillModel::BILL_TYPE_IN) === false)
            $iscommit = false;

        if ($this->order_model->setStatus($order['id'], OrderModel::STATUS_COMPLETE) === false) {
            $iscommit = false;
        }

        if ($this->order_model->where(['id' => $result['order_id']])->save(['returns_status' => OrderModel::REFUND_STATUS_COMPLETE]) === false)
            $iscommit = false;


        if ($iscommit) {
            $this->refund_model->commit();
            $this->error('失败');
        } else {
            $this->refund_model->rollback();
            $this->success('成功');
        }


    }

}
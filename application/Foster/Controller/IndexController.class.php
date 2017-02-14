<?php
namespace Foster\Controller;

use Appapi\Controller\ApibaseController;
use Common\Controller\AdminbaseController;
use Common\Model\OrderModel;
use Common\Model\PetTypeModel;
use Common\Model\RegionModel;
use Community\Model\ComScoreModel;
use Consumer\Model\CouponModel;
use Consumer\Model\WalletBillModel;
use Consumer\Model\WalletModel;
use Foster\Model\FosterModel;
use Foster\Model\FosterRulesModel;
use Think\Controller;

class IndexController extends AdminbaseController
{
    private $foster_model;
    private $foster_rules_model;
    private $order_model;
    private $wallet_model, $wallet_billmodel, $com_sco_model, $coupon_model;
    private $region_model,$pet_type_model;

    public function __construct()
    {
        parent::__construct();
        $this->foster_model = new FosterModel();
        $this->foster_rules_model = new FosterRulesModel();
        $this->order_model = new OrderModel();
        $this->wallet_model = new WalletModel();
        $this->wallet_billmodel = new WalletBillModel();
        $this->com_sco_model = new ComScoreModel();
        $this->coupon_model = new CouponModel();
        $this->region_model = new RegionModel();
        $this->pet_type_model = new PetTypeModel();

    }

    public function lists()
    {

        $this->_lists();
        $this->display();
    }

    public function _lists(){

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
        $where .= empty( $where ) ? 'ego_order.`shows` = 1 ' : 'and ego_order.`shows` = 1';

        $count = $this->foster_model
            ->where($where)
            ->alias('a')
            ->join('LEFT JOIN ego_order  on a.order_id  = ego_order.id ')
            ->join('LEFT JOIN ego_member  on ego_order.mid = ego_member.id ')
            ->join('LEFT JOIN ego_pet_type  on a.ptype = ego_pet_type.pet_variety_id ')
            ->count();
        $page = $this->page($count,C('PAGE_NUMBER'));

        $list  = $this->foster_model
            ->alias('a')
            ->join('LEFT JOIN ego_order  on a.order_id =ego_order.id ')
            ->join('LEFT JOIN ego_member  on ego_order.mid =ego_member.id ')
            ->join('LEFT JOIN ego_pet_type  on a.ptype = ego_pet_type.pet_variety_id ')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('id desc')
            ->field('a.*,ego_member.username,ego_order.create_time,ego_order.order_sn,ego_order.status,ego_pet_type.pet_variety')
            ->where($where)
            ->select();

        $trasport = "";
        $result = '';
        foreach( $list as $k => $v ){
            $status = $this->foster_model->getStatus($v['status']);
            if($status == '待分配') {
                $result[$k]['str_manage'] .= '待分配 | '. '<a href="' . U('Index/edit_order', ['id' => $v['order_id'],'status' => OrderModel::STATUS_SEND]) . '">已分配</a>'.' | '. '<a href="' . U('Index/cancelOrder', ['id' => $v['order_id'],'status' => OrderModel::STATUS_CANCEL]) . '">取消订单</a>';
            } elseif($status == '已分配') {
                $result[$k]['str_manage'] .= '已分配 | '. '<a href="' . U('Index/edit_order', ['id' => $v['order_id'],'status' => OrderModel::STATUS_COMPLETE]) . '">已完成</a>'.' | '. '<a href="' . U('Index/cancelOrder', ['id' => $v['order_id'],'status' => OrderModel::STATUS_CANCEL]) . '">取消订单</a>';
            } elseif($status == '已完结') {
                $result[$k]['str_manage'] .= '已完结';
            } elseif($status == '待付款') {
                $result[$k]['str_manage'] .= '待付款';
            } elseif($status == '用户取消') {
                $result[$k]['str_manage'] .= '已取消';
            }

            $result[$k]['str_manage'] .= '| <a  class="js-ajax-delete"  href="' . U('Index/delete', ['id' => $v['order_id']]) . '">删除</a>';

            $pettype = $this->pet_type_model->where('pet_variety_id='.$v['ptype'])->find();
            $fo_vaccine = json_decode($v['fo_vaccine'],true);
            if( $fo_vaccine['status'] == 1 ){
                $vaccine = $fo_vaccine['content'];
            }else{
                $vaccine = "未打疫苗";
            }

            $trasport .= '<tr>
                            <td>' . ($k + 1) . '</td>
                            <td>' . $v['order_sn'] . '</td>
                            <td>' . $v['username'] . '</td>
                            <td>' . $this->foster_model->getPickup($v['fo_pickup'])  . '</td>
                            <td>' . $this->region_model->getAllarea($v['fo_area']).'/'.$v['fo_address'] . '</td>
                            <td>' . $v['fo_contacts'] . '</td>
                            <td>' . $v['fo_contacts_phone'] . '</td>
                            <td>' . $v['fo_faster_time'] . '</td>
                            <td>' . $this->foster_model->getFood($v['fo_dog_food']) . '</td>
                            <td>' . $pettype['pet_variety'] . '</td>
                            <td>' . $v['fo_service'] . '</td>
                            <td>' . $v['fo_age'] . '</td>
                            <td>' . $vaccine . '</td>
                            <td>' . $v['fo_price'] . '</td>
                            <td>' . date('Y-m-d H:i:s',$v['create_time']) . '</td>
                            <td>' . $result[$k]['str_manage'] . '</td>
                          </tr>';
        }
        $this->assign('formget', I(''));
        $this->assign('Page',$page->show('Admin'));
        $this->assign('trasport',$trasport);
        $this->assign('formget', I(''));
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
    public function del_order(){
        $id = I('id');
        $result = $this->order_model->find($id);

        if( $result['status'] == FosterModel::ORDER_STATUS_WAIT ){

            $save = array(
                'status'=>FosterModel::ORDER_STATUS_ALLOT,
            );
           if( $this->order_model->where('id='.$id)->save($save) === false){
               $this->error('修改失败');
           }else{
               $this->success('修改成功');
           }
        }
        if( $result['status'] == FosterModel::ORDER_STATUS_ALLOT ){
            $save = array(
                'status'=>FosterModel::ORDER_STATUS_END,
            );
            if( $this->order_model->where('id='.$id)->save($save) === false){
                $this->error('修改失败');
            }else{
                $this->success('修改成功');
            }
        }

        if( $result['status'] == FosterModel::ORDER_STATUS_WAIT_PAY ){
            $this->error('修改失败，当前订单未付款 禁止修改');
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
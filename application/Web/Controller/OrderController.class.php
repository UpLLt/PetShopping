<?php
namespace Web\Controller;

use Common\Model\AddressModel;
use Common\Model\CartModel;
use Common\Model\LogisticsTempModel;
use Common\Model\MarriageModel;
use Common\Model\OrderPetModel;
use Common\Model\OrderProductModel;
use Common\Model\OrderRefundModel;
use Common\Model\ProductModel;
use Common\Model\ProductOptionModel;
use Community\Model\ComRecordModel;
use Community\Model\ComScoreModel;
use Consumer\Model\CouponModel;
use Consumer\Model\TicketModel;
use Foster\Model\FosterModel;
use Funeral\Model\BuriedModel;
use Issue\Model\ProductPetModel;
use Common\Model\OrderModel;
use Merchant\Model\HospitalModel;
use Think\Controller;
use Transport\Model\TransportModel;
use Consumer\Model\MemberModel;
use Common\Model\CommentModel;

/**
 * 交易管理
 * Class IndexController
 * @package Web\Controller
 */
class OrderController extends BaseController
{
    private $product_pet_model, $cart_model, $product_model, $product_option_model , $logistics_model, $coupon_model,$ticket_model,$com_sco_model;
    private $address_model ,$order_product_model,$order_model ,$com_record_model,$hospital_model;
    private $transport_model , $buried_model , $foster_model ,$marriage_model ,$com_score_model,$order_pet_model,$order_refund_model;

    public function __construct()
    {
        parent::__construct();

        $this->product_pet_model = new ProductPetModel();
        $this->cart_model = new CartModel();
        $this->product_model = new ProductModel();
        $this->product_option_model = new ProductOptionModel();
        $this->logistics_model = new LogisticsTempModel();
        $this->coupon_model = new CouponModel();
        $this->ticket_model = new TicketModel();
        $this->com_sco_model = new ComScoreModel();
        $this->address_model = new AddressModel();
        $this->order_product_model = new OrderProductModel();
        $this->order_model   = new OrderModel();
        $this->com_record_model = new ComRecordModel();
        $this->transport_model = new TransportModel();
        $this->buried_model = new BuriedModel();
        $this->foster_model = new FosterModel();
        $this->marriage_model = new MarriageModel();
        $this->com_score_model = new ComScoreModel();
        $this->order_pet_model = new OrderPetModel();
        $this->order_refund_model = new OrderRefundModel();
        $this->hospital_model = new HospitalModel();

    }

    /**
     * 我的订单
     */
    public function order()
    {
        $order_status_type = I('type');
        $this->OrderList($order_status_type);
        $this->display();
    }




    /**
     * 订单列表
     * @param string $order_status_type
     */
    public function OrderList($order_status_type )
    {

        $mid = session('mid');
        $this->is_login();
        if(!$order_status_type ) $order_status_type = 'not_paid';
        $_GET['type'] = $order_status_type;

        $where = $this->order_model->getOrderTypeByCode($order_status_type);

        $count = $this->order_model
            ->where(['mid' => $mid ,'shows'=> 1 ])
            ->where($where)
            ->count();

        $page   = $this->page($count,6);
        $result = $this->order_model
            ->where(['mid' => $mid  ,'shows'=> 1])
            ->where($where)
            ->limit($page->firstRow.','.$page->listRows)
            ->field('id,mid,order_sn,order_price,order_type,create_time,cover,status')
            ->order('id desc')
            ->select();

        if( $order_status_type == 'not_paid' ){
            $order_list = '<li class="cation" ><a href="'.U('Web/Order/order').'">待付款</a></li>
					<li ><a href="'.U('Web/Order/order',array('type'=>'paid')).'" >已付款</a></li>
					<li ><a href="'.U('Web/Order/order',array('type'=>'sign')).'">待收货</a></li>
					<li ><a href="'.U('Web/Order/order',array('type'=>'reviews')).'">待评价</a></li>
					<li ><a href="'.U('Web/Order/order',array('type'=>'complete')).'">已完成</a></li>
					<li ><a href="'.U('Web/Order/order',array('type'=>'refund')).'" >待退货</a></li>';



        }elseif($order_status_type == 'paid'){
            $order_list = '<li  ><a href="'.U('Web/Order/order').'">待付款</a></li>
					<li class="cation"><a href="'.U('Web/Order/order',array('type'=>'paid')).'" >已付款</a></li>
					<li ><a href="'.U('Web/Order/order',array('type'=>'sign')).'">待收货</a></li>
					<li ><a href="'.U('Web/Order/order',array('type'=>'reviews')).'">待评价</a></li>
					<li ><a href="'.U('Web/Order/order',array('type'=>'complete')).'">已完成</a></li>
					<li ><a href="'.U('Web/Order/order',array('type'=>'refund')).'" >待退货</a></li>';




        }elseif($order_status_type == 'sign'){
            $order_list = '<li  ><a href="'.U('Web/Order/order').'">待付款</a></li>
					<li><a href="'.U('Web/Order/order',array('type'=>'paid')).'" >已付款</a></li>
					<li  class="cation" ><a href="'.U('Web/Order/order',array('type'=>'sign')).'">待收货</a></li>
					<li ><a href="'.U('Web/Order/order',array('type'=>'reviews')).'">待评价</a></li>
					<li ><a href="'.U('Web/Order/order',array('type'=>'complete')).'">已完成</a></li>
					<li ><a href="'.U('Web/Order/order',array('type'=>'refund')).'" >待退货</a></li>';



        }elseif($order_status_type == 'reviews'){
            $order_list = '<li  ><a href="'.U('Web/Order/order').'">待付款</a></li>
					<li ><a href="'.U('Web/Order/order',array('type'=>'paid')).'" >已付款</a></li>
					<li ><a href="'.U('Web/Order/order',array('type'=>'sign')).'">待收货</a></li>
					<li class="cation" ><a href="'.U('Web/Order/order',array('type'=>'reviews')).'">待评价</a></li>
					<li ><a href="'.U('Web/Order/order',array('type'=>'complete')).'">已完成</a></li>
					<li ><a href="'.U('Web/Order/order',array('type'=>'refund')).'" >待退货</a></li>';



        }elseif($order_status_type == 'complete'){
            $order_list = '<li  ><a href="'.U('Web/Order/order').'">待付款</a></li>
					<li><a href="'.U('Web/Order/order',array('type'=>'paid')).'" >已付款</a></li>
					<li ><a href="'.U('Web/Order/order',array('type'=>'sign')).'">待收货</a></li>
					<li ><a href="'.U('Web/Order/order',array('type'=>'reviews')).'">待评价</a></li>
					<li  class="cation" ><a href="'.U('Web/Order/order',array('type'=>'complete')).'">已完成</a></li>
					<li ><a href="'.U('Web/Order/order',array('type'=>'refund')).'" >待退货</a></li>';


        }elseif($order_status_type == 'refund'){
            $order_list = '<li  ><a href="'.U('Web/Order/order').'">待付款</a></li>
					<li><a href="'.U('Web/Order/order',array('type'=>'paid')).'" >已付款</a></li>
					<li ><a href="'.U('Web/Order/order',array('type'=>'sign')).'">待收货</a></li>
					<li ><a href="'.U('Web/Order/order',array('type'=>'reviews')).'">待评价</a></li>
					<li ><a href="'.U('Web/Order/order',array('type'=>'complete')).'">已完成</a></li>
					<li class="cation" ><a href="'.U('Web/Order/order',array('type'=>'refund')).'" >待退货</a></li>';


        }


        foreach ($result as $k => $v) {

            if( $order_status_type == 'not_paid' ){

                $category_str = '<a href="'.U('Web/Pay/pay',array('order_sn'=>$v['order_sn'])).'" class="pay_now_1" >马上付款</a>
							<a href="javascript:;" name="'.$v['id'].'" class="delete_order">取消订单</a>';


            }elseif($order_status_type == 'paid'){

                $category_str = '<a href="javascript:;" >我们将马上处理您的订单，请耐心等待</a>';

            }elseif($order_status_type == 'sign'){


                $category_str = '<a href="#" class="pay_now" name="'.$v['id'].'" >确认收货</a>
								 <a href="javascript:;" name="'.$v['id'].'" class="logistics_asaassa" id="logistics_'.$k.'">查看物流</a>';

            }elseif($order_status_type == 'reviews'){

                if( $v['order_type'] == OrderModel::ORDER_TYPE_TRANSPORT || $v['order_type'] == OrderModel::ORDER_TYPE_FUNERAL  || $v['order_type'] == OrderModel::ORDER_TYPE_FOSTER ){
                    $category_str = '<a href="'.U('Web/Order/evaluate',array('order_id'=>$v['id'])).'"  id="pay_now_2"  class="pay_now_now" >马上评价</a>';

                }else{
                    $category_str = '<a href="'.U('Web/Order/evaluate',array('order_id'=>$v['id'])).'"  id="pay_now_2"  class="pay_now_now" >马上评价</a>
								 <a href="javascript:;" name="'.$v['id'].'" class="refund" >申请退款</a> ';
                }


            }elseif($order_status_type == 'complete'){


                $category_str = '<a href="javascript:;" name="'.$v['id'].'" id="pay_now_1"  class="pay_now1" >删除订单</a>';

            }elseif($order_status_type == 'refund'){

                $category_str = '<a href="javascript:;" name="'.$v['id'].'" id="pay_now_1" class="pay_now2" >马上发货</a>';

            }

            $result[$k]['category_str'] = $category_str;
            $result[$k]['create_time'] = dateDefault($v['create_time']);
            $result[$k]['cover'] = $v['cover'] ? setUrl($v['cover']) : '';

            $result[$k]['app_key_type'] = $v['order_type'] == OrderModel::ORDER_TYPE_GOODS ? '2' : '1';

            $result[$k]['app_key_return'] = '1';
            $result[$k]['status_value'] = $order_status_type;
            $result[$k]['return_str'] = $this->order_model->getOrderTypetoString($order_status_type);
            $result[$k]['refund'] = '';
            $result[$k]['refund_status'] = '';



            if ($order_status_type == 'refund') {
                $refund = $this->order_refund_model->where(['mid' => $mid, 'order_id' => $v['id']])->find();
                $result[$k]['refund'] = $this->order_refund_model->getStatusToString($refund['status']);
                $result[$k]['refund_status'] = $refund['status'];
            }

            //退货条件： 1、限定为商品; 2、付款成功; 3、待评价
            if ($v['order_type'] == OrderModel::ORDER_TYPE_GOODS && ($v['status'] == OrderModel::STATUS_PAY_SUCCESS || ($v['status'] == OrderModel::STATUS_COMPLETE && $v['comment_status'] == '0'))) {
                $result[$k]['app_key_return'] = '2';
            }

            $result[$k]['comment'] = '1';
            if ($v['order_type'] == OrderModel::ORDER_TYPE_GOODS || $v['order_type'] == OrderModel::ORDER_TYPE_HOSPITAL) {
                $result[$k]['comment'] = '2';
            }

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


                $category_str = '<a href="#" class="pay_now">马上付款</a>
								 <a href="javascript:;" class="'.$v['id'].'" id="delete_order_1">取消订单</a>';


                $result[$k]['list'][] = [
                    'name'  => $this->order_model->getOrdrTypetoString($result[$k]['order_type']),
                    'price' => $v['order_price'],
                    'cover' => $v['cover'],


                ];
            }

        }

        $this->assign('order_list',$order_list);
        $this->assign('lists',$result);
        $this->assign('Page',$page->show('Admin'));
    }


    /**
     * 取消订单
     */
    public function cancel()
    {

        $mid = session('mid');
        $this->check_login();
        $order_id = I('post.order_id');
        $result = $this->order_model->find($order_id);
        if (!$result) exit($this->returnApiError(BaseController::FATAL_ERROR, '订单不存在'));


        $iscommit = true;
        $this->order_model->startTrans();


        //宠物取消订单，恢复宠物售卖状态
        if ($result['order_type'] == OrderModel::ORDER_TYPE_PET) {

            $product_pet_id = $this->order_pet_model->where(['order_id' => $order_id])->getField('product_pet_id');

            if ($this->cancel_pet($product_pet_id) === false) {
                $iscommit = false;
            }
        }


        if ($this->order_model->setStatus($order_id, OrderModel::STATUS_CANCEL) === false)
            $iscommit = false;

        if ($result['coupon_id']) {
            if ($this->coupon_model->where(['coupon_id' => $result['coupon_id']])->setField('cou_status', CouponModel::STATUS_VALIDITY) === false)
                $iscommit = false;
        }

        if ($result['score']) {
            if (!$this->com_record_model->addOne($result['score'], '订单抵扣', $mid, 1))
                $iscommit = false;

            if (!$this->com_sco_model->saveScore($mid, $result['score']))
                $iscommit = false;
        }

        if ($iscommit) {
            $this->order_model->commit();
            exit($this->returnApiSuccess());
        } else {
            $this->order_model->rollback();
            exit($this->returnApiError(BaseController::FATAL_ERROR, '错误'));
        }

    }

    /**
     * 取消订单
     * 活体宠物
     *
     * @param $product_pet_id
     *
     * @return string
     */
    public function cancel_pet($product_pet_id)
    {
        return $this->product_pet_model->where(['id' => $product_pet_id])->save(['status' => 0]);
    }


    /**
     *
     * 确认收货
     */
    public function confirmReceipt()
    {
        $mid = session('mid');
        $this->check_login();
        $order_id = I('post.order_id');

        $order = $this->order_model->getOrderData($mid, $order_id);
        if (!$order) exit($this->returnApiError(BaseController::FATAL_ERROR, '订单不存在'));

        if ($order['status'] != OrderModel::STATUS_SEND) {
            exit($this->returnApiError(BaseController::FATAL_ERROR, '商品未发货或已完成'));
        }

        if ($this->order_model->setStatus($order_id, OrderModel::STATUS_COMPLETE) === false) {
            exit($this->returnApiError(BaseController::FATAL_ERROR, '操作失败,请重试'));
        }

        exit($this->returnApiSuccess());
    }


    /**
     * 物流查询接口
     */
    public function logisticsQuery()
    {

        $order_id = I('post.order_id');

        $order = $this->order_model->find($order_id);
        if (!$order) exit($this->returnApiError(BaseController::FATAL_ERROR, '订单不存在'));

        if (!$order['logistics_number']) exit($this->returnApiError(BaseController::FATAL_ERROR, '没有物流信息'));
        $str = '';
        if( $order['order_type'] == OrderModel:: ORDER_TYPE_PET ){
            $str .= "<p></p>";
        }


        vendor('Jisukdcx.JisukdcxMarket');
        $jisukdcx = new \JisukdcxMarket(C('ALI_JISUKDCX_KEY'));
        $result = $jisukdcx->query($order['logistics_number']);
        $result = json_decode($result, true);

//        exit($this->returnApiSuccess($result));

        $str =  '';
        foreach( $result['result']['list'] as $k => $v ){
            $str .= "<p>".$v['time']."   ".$v['status']."</p>";
        }

        if( !$str ) $str = "暂未查询到物流信息";
        $this->ajaxReturn($str);

    }


    public function evaluate(){

        $mid = session('mid');
        $order_id = I('order_id');

        $order = $this->order_model->getOrderData($mid, $order_id, 'id,order_type,comment_status');
        if (!$order) exit($this->error( '订单不存在' ));


        //判断订单状态
//        if (!($order['status'] == OrderModel::STATUS_COMPLETE && $order['comment_status'] == 0)) {
//            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单状态不匹配,不可评价.'));
//        }


        if ($order['order_type'] == OrderModel::ORDER_TYPE_GOODS) {

            $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'product as b on a.product_id = b.id';
            $result = $this->order_product_model
                ->alias('a')
                ->join($join)
                ->where(['order_id' => $order['id']])
                ->field('a.product_id as id,b.pro_name as name,b.smeta')
                ->select();
            foreach ($result as $k => $v) {
                $result[$k]['smeta'] = json_decode($v['smeta'], true);
                if ($result[$k]['smeta']) {
                    $result[$k]['cover'] = $result[$k]['smeta'][0]['url'];
                    $result[$k]['cover'] = $this->setUrl($result[$k]['cover']);
                }
                unset($result[$k]['smeta']);
            }


        } else if ($order['order_type'] == OrderModel::ORDER_TYPE_HOSPITAL) {

            $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'hospital_shop as b on a.hid = b.id';
            $result = $this->hospital_model
                ->alias('a')
                ->join($join)
                ->where(['order_sid' => $order['id']])
                ->field('a.hid as id,b.hos_name as name,b.hos_image as smeta')
                ->select();

            foreach ($result as $k => $v) {
                $result[$k]['smeta'] = json_decode($v['smeta'], true);
                if ($result[$k]['smeta']) {
                    $result[$k]['cover'] = $result[$k]['smeta'][0]['url'];
                    $result[$k]['cover'] = $this->setUrl($result[$k]['cover']);
                }
                unset($result[$k]['smeta']);
            }

        }  else if( $order['order_type'] == OrderModel:: ORDER_TYPE_TRANSPORT){

            $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'order as b on a.order_id = b.id';
            $result = $this->transport_model
                ->alias('a')
                ->join($join)
                ->where(['order_id' => $order['id']])
                ->field('a.id,b.cover')
                ->select();
            foreach ($result as $k => $v) {
                $result[$k]['name'] = "运输";
            }
        }else if( $order['order_type'] == OrderModel:: ORDER_TYPE_FUNERAL){

            $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'order as b on a.order_id = b.id';
            $result = $this->buried_model
                ->alias('a')
                ->join($join)
                ->where(['order_id' => $order['id']])
                ->field('a.id,b.cover')
                ->select();
            foreach ($result as $k => $v) {
                $result[$k]['name'] = "殡仪";
            }
        }else if( $order['order_type'] == OrderModel:: ORDER_TYPE_FOSTER){

            $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'order as b on a.order_id = b.id';
            $result = $this->foster_model
                ->alias('a')
                ->join($join)
                ->where(['order_id' => $order['id']])
                ->field('a.id,b.cover')
                ->select();
            foreach ($result as $k => $v) {
                $result[$k]['name'] = "寄养";
            }
        }else {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单类型不允许评论'));
        }


        unset($order['comment_status']);
        $data['order'] = $order;
        $data['list'] = $result;


        $this->assign('list',$result);
        $this->assign('order_id',I('order_id'));
        $this->display();
    }

    /**
     * 退货->物流信息
     */
    public function refundLogistics()
    {

        $this->is_login();

        $order_id = I('post.order_id');
        $logistics_company = I('post.logistics_company');
        $logistics_number = I('post.logistics_number');


        $result = $this->order_refund_model->where(['order_id' => $order_id])->find();
        if (!$result) exit($this->error('申请不存在'));

        if ($result['status'] != OrderRefundModel::STATUS_APPLY_OK) {
            exit($this->error( '该订单已发货'));
        }

        $save = $this->order_refund_model->where(['order_id' => $order_id])->save(
            [
                'logistics_company' => $logistics_company,
                'logistics_number'  => $logistics_number,
                'status'            => OrderRefundModel::STATUS_APPLY_SEND,
            ]);

        if ($save === false)
        exit($this->error( '物流编号设置错误'));

        exit($this->success('发货成功'));
    }

    /**
     * 评论
     */
    public function comment_post()
    {

        $this->is_login();
        $mid = session('mid');
        $order_id = I('post.order_id');
        $content = I('post.content');
        $level = I('post.level');
        $id     = I('post.id');

        $orderdata = $this->order_model->getOrderData($mid, $order_id);
        if (!$orderdata) exit($this->error( '订单不存在'));

        if ($orderdata['comment_status'] == 1) {
            exit($this->error( '订单已评价',U('Web/Order/order',array('type'=>"complete"))));
        }

        $comment_model = new CommentModel();
        $member_model = new MemberModel();
        $full_name = $member_model->getNickNameByid($mid);


        foreach ($content as $k => $v) {
            if ($v) {
                    if( !$v ) $v = "很不错，很满意";

                $data[] = [
                    'mid'          => $mid,
                    'star'         => $level[$k],
                    'relevance_id' => $id[$k],
                    'content'      => $v,
                    'order_type'   => $orderdata['order_type'],
                    'full_name'    => $full_name,
                    'full_name'    => $full_name,
                    'create_time'  => time(),
                ];
            }
        }
        if (!$data || empty($data) || count($data) < 1) {
            exit($this->error( '数据为空'));
        }

        $iscommit = true;
        $comment_model->startTrans();

        if (!$comment_model->addAll($data)) {
            $iscommit = false;
        }

        if ($this->order_model->where(['id' => $order_id])->save(['comment_status' => 1]) === false) {
            $iscommit = false;
        }

        if ($iscommit) {
            $comment_model->commit();
            exit($this->success('评论成功',U('Web/Order/order',array('type'=>"complete"))));
        } else {
            $comment_model->rollback();
            exit($this->error( '评论失败',U('Web/Order/order',array('type'=>"complete"))));
        }
    }

    public function refund(){
        $this->assign('order_id',I('order_id'));
        $this->display();
    }


    public function returned(){

        $this->assign('order_id',I('order_id'));

        $this->display();
}
    /**
     * 上传
     */
    public function applyRefundPhoto()
    {


        $mid = session('mid');
        $this->is_login();

        $order_id = I('post.order_id');
        $refund_id = I('post.refund_id');

        $result = $this->order_model->find($order_id);
        if (!$result) exit($this->error( '订单不存在'));

        //退货条件： 1、限定为商品; 2、付款成功; 3、待评价
        if (!($result['order_type'] == OrderModel::ORDER_TYPE_GOODS && ($result['status'] == OrderModel::STATUS_PAY_SUCCESS || ($result['status'] == OrderModel::STATUS_COMPLETE && $result['comment_status'] == '0')))) {
            exit($this->error( '申请失败'));
        }

        if ($result['returns_status'] == 1) {
            exit($this->error( '已申请退货'));
        }

        $imagurl = upload_img('photo');
        if( $refund_id == 3 ){
            if (!$imagurl) {
                exit($this->error( '！请上传图片'));
            }
        }

        $data = [
            'mid'         => $mid,
            'order_id'    => $order_id,
            'argument'    => $refund_id,
            'image'       => $imagurl[0],
            'create_time' => time(),
        ];

        if (!$this->order_model->create($data)) {
            exit($this->error());
        }

        $iscommit = true;
        $this->order_model->startTrans();
        if ($this->order_model->where(['id' => $order_id])->save(['returns_status' => 1]) === false)
            $iscommit = false;

        if (!$this->order_refund_model->add($data))
            $iscommit = false;

        if ($iscommit) {
            $this->order_model->commit();
            exit($this->success('申请退货成功，正在为你处理'));

        } else {
            $this->order_model->rollback();
            exit($this->error( '失败'));
        }
    }


    public function hide()
    {
        $mid = session('mid');
        $this->check_login();
        $order_id = I('post.order_id');

        $order = $this->order_model->getOrderData($mid, $order_id);
        if (!$order) exit($this->returnApiError(BaseController::FATAL_ERROR, '订单不存在'));

        if ($order['status'] != OrderModel::STATUS_COMPLETE && $order['comment_status'] != 1) {
            exit($this->returnApiError(BaseController::FATAL_ERROR, '订单未完成,不可隐藏'));
        }

        if ($this->order_model->where(['id' => $order_id])->save(['shows' => 0]) === false) {
            exit($this->returnApiError(BaseController::FATAL_ERROR, '操作失败,请重试'));
        }

        exit($this->returnApiSuccess());
    }

    /**
     * 我的购物车
     */
    public function cart()
    {
        $mid = session('mid');
        $this->is_login();

        $cart_product = $this->cart_model
            ->alias('a')
            ->where(array('mid'=>$mid,'cart_type'=>OrderModel::ORDER_TYPE_GOODS))
            ->join('LEFT JOIN '.C('DB_PREFIX').'product as b on a.product_id = b.id')
            ->join('LEFT JOIN '.C('DB_PREFIX').'product_option as c on a.option_key_id = c.option_key_id')
            ->field('a.id as cartid,a.create_time,a.cart_type,a.quantity,b.*,c.option_name,c.option_price,c.inventory')
            ->select();


        foreach( $cart_product as $k => $v ){
            $pet_pic = json_decode($v['smeta'],true);
            $data_pro[$k]['cartid']   = $v['cartid'];
            $data_pro[$k]['pic'] = $this->setUrl($pet_pic['0']['url']);
            $data_pro[$k]['name']= $v['pro_name'];
            $data_pro[$k]['cart_type'] = $v['cart_type'];
            $data_pro[$k]['quantity'] = $v['quantity'];
            $data_pro[$k]['price'] = $v['option_price'];
            $data_pro[$k]['inventory'] = $v['inventory'];
            $total_price = $v['quantity'] * $v['option_price'];
            $data_pro[$k]['total_price'] = sprintf("%.2f", $total_price);
            $data_pro[$k]['option_name'] = $v['option_name'];
        }

        $this->assign('product',$data_pro);

        $this->display();
    }

    /**
     * 订单生成前
     */
    public function cartSettlementBefore() {

        $this->is_login();
        $mid = session('mid');
        $cart_ids = I('post.cartid');
        $total = '';//总价
        $ids = explode(',', $cart_ids);
        $logistics_cost = '';//物流价格
        $list = array();
        //查购物车信息
        $carts = $this->cart_model
            ->where(array('id' => array('in', $ids)))
            ->select();
        foreach ($carts as $k => $v) {
            $product = $this->product_model->find($v['product_id']);
            if (!$product) exit($this->error('商品不存在'));

            if ($product['pro_shop_type'] == 2) {
                exit($this->error('第三方平台商品,请到第三方平台结算'));
            }
            $product_option = $this->product_option_model->find($v['option_key_id']);
            if (!$product_option) exit($this->error( '商品不存在'));

            if ($product_option['inventory'] < $v['quantity'])
                exit($this->error( '商品库存不足'));
            //单种商品价值
            $product_one = $product_option['option_price']*$v['quantity'];
            $total += $product_one;

            //物流价格
            $logistics_one = $this->logistics_model->computational_cost($product['logistics_id'], $v['quantity'], $product_option['option_price']);
            $logistics_cost += $logistics_one;
            $list[] = array(
                'smeta'=>setUrl(json_decode($product['smeta'],true)[0]['url']),
                'cart_id' => $v['id'],
                'product_id' => $v['product_id'],
                'name' => $product['pro_name'],
                'quantity' => $v['quantity'],
                'one_price' => sprintf("%0.2f", $product_option['option_price']),
                'option_name' => $product_option['option_name'],
                'total_price' => sprintf("%0.2f", $product_one + $logistics_one),
                'logistics_cost' => sprintf("%0.2f", $logistics_one),
            );
        }
        //积分
        $score = $this->com_sco_model->scoExchange($mid, $total);

        //默认收货地址
        $address = $this->address_model->where('mid='.$mid)->select();

        foreach( $address as $k => $v ){
            if($v['status'] == 1 ){
                $address_str = $v;
            }
        }
        if( !$address_str ){
            $address_str = $address[0];
        }else{
            $addtr = "	<b>[默认地址]</b>
						<span class=\"user_name_nicke\">".$address_str['fullname']."</span>
						<span>".$address_str['address']."</span>
						<span>".$address_str['phone']."</span>";

            $addlr = "	<p><b>寄送至：</b>".$address_str['address']."</p>
						<p><span>".$address_str['fullname']."</span> <span>".$address_str['phone']."</span></p>";
        }

        if( !$address_str){
            $addtr = "请添加收货地址";
            $addlr = '';
        }else{
            $addtr = "	<b>[默认地址]</b>
						<span class=\"user_name_nicke\">".$address_str['fullname']."</span>
						<span>".$address_str['address']."</span>
						<span>".$address_str['phone']."</span>";

            $addlr = "	<p><b>寄送至：</b>".$address_str['address']."</p>
                        <input id='verifyaddressid' name = 'address_id' type='hidden' value='".$address_str['id']."'>
						<p><span>".$address_str['fullname']."</span> <span>".$address_str['phone']."</span></p>";
        }

        $data = array(
            'addtr'=>$addtr,
            'addlr'=>$addlr,
            'score'=>$score,
            'total_price_sum' => sprintf("%0.2f", $total + $logistics_cost),
            'total_logistics_sum' => sprintf("%0.2f", $logistics_cost),
            'cart_ids' => $cart_ids,
            'lists' => $list,
        );

        $this->coupon($data['total_price_sum']);
        $this->assign('lists',$data);
        $this->display('wait');
    }


    /**
     * 下单前
     */
    public function buyItNowBefour()
    {
        $mid = session('mid');
        $this->is_login();
        $product_pet_id = I('post.pet_id');

        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'pet_type as b on a.pet_variety_id = b.pet_variety_id';
        $product_pet = $this->product_pet_model
            ->join($join)
            ->alias('a')
            ->field('a.id,a.pet_type,a.pet_variety_id,a.pet_name,a.pet_price,a.pet_picture,b.pet_variety')
            ->find($product_pet_id);


        if (!$product_pet)
            exit($this->error( '商品不存在'));
        if ($product_pet['status'] == ProductPetModel::LOCK_ON)
            exit($this->error(  '商品已被其他人下单'));


//        $cover = $product_pet['smeta'];
        $product_pet['pet_picture'] = json_decode($product_pet['pet_picture'], ture);
        if ($product_pet['pet_picture']) {
            $product_pet['pet_picture'] = $product_pet['pet_picture'][0]['url'];
            $product_pet['pet_picture'] = $this->setUrl($product_pet['pet_picture']);
        }



        //积分
        $score = $this->com_sco_model->scoExchange($mid, $product_pet['pet_price']);

        //默认收货地址
        $address = $this->address_model->where('mid='.$mid)->select();

        foreach( $address as $k => $v ){
            if($v['status'] == 1 ){
                $address_str = $v;
            }
        }
        if( !$address_str ){
            $address_str = $address[0];
        }else{
            $addtr = "	<b>[默认地址]</b>
						<span class=\"user_name_nicke\">".$address_str['fullname']."</span>
						<span>".$address_str['address']."</span>
						<span>".$address_str['phone']."</span>";

            $addlr = "	<p><b>寄送至：</b>".$address_str['address']."</p>
						<p><span>".$address_str['fullname']."</span> <span>".$address_str['phone']."</span></p>";
        }

        if( !$address_str){
            $addtr = "请添加收货地址";
            $addlr = '';
        }else{
            $addtr = "	<b>[默认地址]</b>
						<span class=\"user_name_nicke\">".$address_str['fullname']."</span>
						<span>".$address_str['address']."</span>
						<span>".$address_str['phone']."</span>";

            $addlr = "	<p><b>寄送至：</b>".$address_str['address']."</p>
                        <input id='verifyaddressid' name = 'address_id' type='hidden' value='".$address_str['id']."'>
						<p><span>".$address_str['fullname']."</span> <span>".$address_str['phone']."</span></p>";
        }


        $data['Product'] = $product_pet;
        $data['addtr'] = $addtr;
        $data['addlr'] = $addlr;
        $data['score'] = $score['score'];
        $data['score_price'] = $score['price'];
        $this->coupon($product_pet['pet_price']);

        $this->assign('lists',$data);
        $this->display('petbuy');
    }


    /**
     * 立即购买前界面
     */
    public function SettlementLogisticsCost()
    {
        $this->is_login();
        $mid = session('mid');

        $product_id = I('post.product_id');
        $product_option_id = I('post.product_option_id');
        $quantity = I('post.quantity');


        $product = $this->product_model->find($product_id);
        if (!$product) exit($this->error( '商品不存在'));

        $product_option = $this->product_option_model->find($product_option_id);
        if (!$product_option) exit($this->error(  '规格不存在'));


        if ($product_option['inventory'] < $quantity)
            exit($this->error(  '商品库存不足'));

        $product_total = $quantity * $product_option['option_price'];

        $LogisticsCost = $this->logistics_model->computational_cost($product['logistics_id'], $quantity, $product_option['option_price']);

        $cover = $product['smeta'];
        $cover = json_decode($cover, ture);
        if ($cover) {
            $cover = $cover[0]['url'];
            $cover = $this->setUrl($cover);
        }
        $data['Product'] = [
            'id'                => $product['id'],
            'pro_name'          => $product['pro_name'],
            'cover'             => $cover,
            'product_option_id' => $product_option_id,
            'option_name'       => $product_option['option_name'],
            'price'             => $product_option['option_price'],
        ];

        $total = $product_total + $LogisticsCost;

        $score_number = $this->com_sco_model->scoExchange($mid, $total, true);
        $score_price = $this->com_sco_model->scoExchange($mid, $total);


        //默认收货地址
        $address = $this->address_model->where('mid='.$mid)->select();

        foreach( $address as $k => $v ){
            if($v['status'] == 1 ){
                $address_str = $v;
            }
        }
        if( !$address_str ){
            $address_str = $address[0];
        }else{
            $addtr = "	<b>[默认地址]</b>
						<span class=\"user_name_nicke\">".$address_str['fullname']."</span>
						<span>".$address_str['address']."</span>
						<span>".$address_str['phone']."</span>";

            $addlr = "	<p><b>寄送至：</b>".$address_str['address']."</p>
						<p><span>".$address_str['fullname']."</span> <span>".$address_str['phone']."</span></p>";
        }

        if( !$address_str){
            $addtr = "请添加收货地址";
            $addlr = '';
        }else{
            $addtr = "	<b>[默认地址]</b>
						<span class=\"user_name_nicke\">".$address_str['fullname']."</span>
						<span>".$address_str['address']."</span>
						<span>".$address_str['phone']."</span>";

            $addlr = "	<p><b>寄送至：</b>".$address_str['address']."</p>
                        <input id='verifyaddressid' name = 'address_id' type='hidden' value='".$address_str['id']."'>
						<p><span>".$address_str['fullname']."</span> <span>".$address_str['phone']."</span></p>";
        }


        $data['addtr'] = $addtr;
        $data['addlr'] = $addlr;
        $data['score'] = $score_price['score'];
        $data['score_use'] = $score_number;
        $data['score_price'] = $score_price['price'];
        $data['LogisticsCost'] = $LogisticsCost;
        $data['quantity'] = $quantity;
        $data['total'] = $total;

        $this->coupon($total);
        $this->assign('lists',$data);
        $this->display('buynow');

    }


    /**
     *地址显示
     */
    public function address_more(){
        $mid = session('mid');
        $address = $this->address_model->where('mid='.$mid)->select();
        $this->assign('address',$address);
        $this->display();
    }

    public function editdefault(){
        $addressid = I('post.addressid');
        $mid = session('mid');
        $this->check_login();
        $result = $this->address_model
            ->where(array('id' => $addressid))
            ->find();

        if( $result['status'] == AddressModel::ADDRESS_NOT_DEFAULT ){
            $this->address_model->where('mid='.$mid )->save(array('status' => AddressModel::ADDRESS_NOT_DEFAULT));
            $this->address_model->where('id='.$addressid)->save(array('status' => AddressModel::ADDRESS_DEFAULT));
        }
        exit($this->ajaxReturn(1));
    }



    //殡仪等订单 下单前展示
    public function oneOrderBefore(){

        $mid = session('mid');
        $this->is_login();
        $order_id = I('order_id');
        $order_type = $this->order_model->where([ 'id'=>$order_id , 'mid'=> $mid ] )->getField('order_type');

        if( $order_type == OrderModel::ORDER_TYPE_TRANSPORT ){
            $join_tran = 'LEFT JOIN '.C('DB_PREFIX'). 'transport as b on a.id = b.order_id ';
            $data = $this->order_model
                         ->alias('a')
                         ->where([ 'a.id'=>$order_id ])
                         ->join( $join_tran )
                         ->field('a.order_price,a.cover,a.id,b.tr_pickup_price')
                         ->find();

            $lists['price']      =  $data['order_price'];
            $lists['pick_price'] =  $data['tr_pickup_price'];
            $lists['order_id']   =  $data['id'];
            $lists['picture']     =  $data['cover'];
            $lists['name']       =  '宠物运输';



        }elseif( $order_type == OrderModel::ORDER_TYPE_FUNERAL ){

            $join_buried = 'LEFT JOIN '.C('DB_PREFIX'). 'buried as b on a.id = b.order_id ';
            $data = $this->order_model
                ->alias('a')
                ->where([ 'a.id'=>$order_id ])
                ->join( $join_buried )
                ->field('a.order_price,a.id,a.cover,b.bu_pick_up_price')
                ->find();
            $lists['price']      =  $data['order_price'];
            $lists['pick_price'] =  $data['bu_pick_up_price'];
            $lists['order_id']   =  $data['id'];
            $lists['picture']     =  $data['cover'];
            $lists['name']       =  '宠物殡仪';

        }elseif( $order_type == OrderModel::ORDER_TYPE_FOSTER ){

            $join_foster = 'LEFT JOIN '.C('DB_PREFIX'). 'foster as b on a.id = b.order_id ';
            $data = $this->order_model
                ->alias('a')
                ->where([ 'a.id'=>$order_id ])
                ->join( $join_foster )
                ->field('a.order_price,a.id,a.cover,b.fo_pickup_price')
                ->find();

            $lists['price']      =  $data['order_price'];
            $lists['pick_price'] =  $data['fo_pickup_price'];
            $lists['order_id']   =  $data['id'];
            $lists['picture']    =  $data['cover'];
            $lists['name']       =  '宠物寄养';

        }elseif( $order_type == OrderModel::ORDER_TYPE_MARRIAGE ){
            $join_foster = 'LEFT JOIN '.C('DB_PREFIX'). 'marriage as b on a.id = b.order_sid ';
            $data = $this->order_model
                ->alias('a')
                ->where([ 'a.id'=>$order_id ])
                ->join( $join_foster )
                ->field('a.order_price,a.cover,a.id')
                ->find();

            $lists['price']      =  $data['order_price'];
            $lists['pick_price'] =  0;
            $lists['order_id']   =  $data['id'];
            $lists['picture']    =  $data['cover'];
            $lists['name']       =  '宠物婚介';

        }

        //默认收货地址
        $address = $this->address_model->where('mid='.$mid)->select();

        foreach( $address as $k => $v ){
            if($v['status'] == 1 ){
                $address_str = $v;
            }
        }
        if( !$address_str ){
            $address_str = $address[0];
        }else{
            $addtr = "	<b>[默认地址]</b>
						<span class=\"user_name_nicke\">".$address_str['fullname']."</span>
						<span>".$address_str['address']."</span>
						<span>".$address_str['phone']."</span>";

            $addlr = "	<p><b>寄送至：</b>".$address_str['address']."</p>
						<p><span>".$address_str['fullname']."</span> <span>".$address_str['phone']."</span></p>";
        }

        if( !$address_str){
            $addtr = "请添加收货地址";
            $addlr = '';
        }else{
            $addtr = "	<b>[默认地址]</b>
						<span class=\"user_name_nicke\">".$address_str['fullname']."</span>
						<span>".$address_str['address']."</span>
						<span>".$address_str['phone']."</span>";

            $addlr = "	<p><b>寄送至：</b>".$address_str['address']."</p>
                        <input id='verifyaddressid' name = 'address_id' type='hidden' value='".$address_str['id']."'>
						<p><span>".$address_str['fullname']."</span> <span>".$address_str['phone']."</span></p>";
        }
        $score_number = $this->com_sco_model->scoExchange($mid, $lists['price'], true);
        $score_price = $this->com_sco_model->scoExchange($mid, $lists['price']);
        $lists['addtr'] = $addtr;
        $lists['addlr'] = $addlr;
        $lists['score'] = $score_price['score'];
        $lists['score_use'] = $score_number;
        $lists['score_price'] = $score_price['price'];

        $this->assign('lists',$lists);
        $this->coupon( $lists['price'] );
        $this->display();
    }


    /**
     * 修改订单积分优惠券使用信息(殡仪运输订单)
     */
    public function editOrder() {


        $postdata = get_data(1);
        $this->is_login();

        $mid = session('mid');
        if( !$postdata['score'] )  $postdata['score']  = 1;

        $info = $this->order_model->where(array('id' => $postdata['order_id']))->find();
        $total = $info['order_price'];

        //积分
        $score_number = '';

        if ($postdata['score'] == 2) {
            //积分查询
            $score_number = $this->com_score_model->scoExchange($mid, $total, true);
            $score_price = $this->com_score_model->scoExchange($mid, $total);
            $total = $total - $score_price['price'];
        }

        //没有使用优惠券且没有积分可用
        if(!$score_number && !$postdata['coupon_id']) {
            $return = array(
                'order_sn' => $info['order_sn'],
            );

            $this->success('下单成功',U('Web/Pay/pay',$return));

        }

        // 优惠券计算
        if ( $postdata['coupon_id']) {
            $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'ticket as b on a.tid = b.id';
            $coupon_data = $this->coupon_model
                ->alias('a')
                ->where(['mid' => $mid, 'coupon_id' => $postdata['coupon_id']])
                ->join($join)
                ->field('a.*,b.full_use,b.price')
                ->find();

            if (!$coupon_data)
                exit($this->error( '优惠券不存在'));

            if ($coupon_data['expiration_time'] < time()) {
                exit($this->error( '优惠券已过期'));
            }

            if ($coupon_data['cou_status'] == CouponModel::STATUS_USED) {
                exit($this->error( '优惠券已被使用'));
            }

            $total = $total - $coupon_data['price'];
        }
            $this->order_model->startTrans();
            $is_commit = true;
            $order = array(
                'order_price' => $total,
                'score' => $score_number,
                'coupon_id' => $postdata['coupon_id'],
            );
            $rst = $this->order_model->where(array('id' => $postdata['order_id']))->save($order);
            if(!$rst) {
                $is_commit = false;
            }
            //不同订单类型修改不同表信息
            if($info['order_type'] == OrderModel::ORDER_TYPE_FUNERAL) {
                $data = array(
                    'bu_price' => $total,
                );
                if($this->buried_model->where(array('order_id' => $postdata['order_id']))->save($data) == false) {
                    $is_commit = false;
                }
            } elseif($info['order_type'] == OrderModel::ORDER_TYPE_TRANSPORT) {
                $data = array(
                    'tr_price' => $total,
                );
                if($this->transport_model->where(array('order_id' => $postdata['order_id']))->save($data) == false) {
                    $is_commit = false;
                }
            } elseif ($info['order_type'] == OrderModel::ORDER_TYPE_FOSTER) {
                $data = array(
                    'fo_price' => $total,
                );
                if($this->foster_model->where(array('order_id' => $postdata['order_id']))->save($data) == false) {
                    $is_commit = false;
                }
            } elseif($info['order_type'] == OrderModel::ORDER_TYPE_MARRIAGE) {
                $data = array(
                    'ma_sprice' => $total,
                );
                if($this->marriage_model->where(array('order_sid' => $postdata['order_id']))->save($data) == false) {
                    $is_commit = false;
                }
            }

            if ($postdata['score'] == 2 && $score_number) {
                if (!$this->com_record_model->addOne($score_number, '订单抵扣', $mid, 2))
                    $is_commit = false;

                if (!$this->com_score_model->decScore($mid, $score_number))
                    $is_commit = false;
            }

            if ($postdata['coupon_id']) {
                if ($this->coupon_model->where(['coupon_id' => $postdata['coupon_id']])->setField('cou_status', CouponModel::STATUS_USED) === false)
                    $is_commit = false;
            }

            if($is_commit) {
                $this->order_model->commit();
            } else {
                $this->order_model->rollback();
                exit($this->error( '修改订单信息失败'));
            }

            $return = array(
                'order_sn' => $info['order_sn'],
            );
            $this->success('下单成功',U('Web/Pay/pay',$return));


    }


    /**
     * 优惠劵
     */
    public function coupon($total){

        $this->is_login();
        $mid = session('mid');

//        $total = I('post.total'); //当前订单金额


        if ($total) {
            $where = [
                'full_use' => ['lt', $total],
            ];
        }

        $where['mid'] = $mid;
        $this->coupon_model->CouponStatusChange();
        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'ticket as b on a.tid = b.id';
        $lists = $this->coupon_model
            ->alias('a')
            ->where(['mid' => $mid, 'expiration_time' => ['gt', time()]])
            ->where($where)
            ->join($join)
            ->field('a.*,b.price,b.ttype,b.full_use')
            ->select();

        foreach ($lists as $k => $v) {
            if( $v['cou_status'] ==  1 ){
                $data[$k]['full_use']  =  floor($v['full_use']) ;
                $data[$k]['coupon_id'] = $v['coupon_id'];
                $data[$k]['expiration_time'] = date('Y-m-d', $v['expiration_time']);
                $data[$k]['price'] = floor($v['price']);
                $data[$k]['coupon_number'] = $v['coupon_number'];
                $data[$k]['ttype'] = $this->ticket_model->getTypeStr($v['ttype']);
            }else{
                $list[$k]['full_use']  =   floor($v['full_use']);
                $list[$k]['coupon_id'] = $v['coupon_id'];
                $list[$k]['expiration_time'] = date('Y-m-d', $v['expiration_time']);
                $list[$k]['price'] = floor($v['price']);
                $list[$k]['coupon_number'] = $v['coupon_number'];
                $list[$k]['ttype'] = $this->ticket_model->getTypeStr($v['ttype']);
            }
        }

        sort($data);
        sort($list);

        $this->assign('coupon',$data);
    }

    public function get_coupon_price(){
        $couponid = I('post.couponid');
        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'ticket as b on a.tid = b.id';
        $lists = $this->coupon_model
            ->alias('a')
            ->where(['a.coupon_id'=>$couponid])
            ->join($join)
            ->getField('b.price');
        if( $couponid == 0 ) $lists = 0;
       $this->ajaxReturn($lists);
    }

    /**
     * 添加订单
     */
    public function createOrder() {

        $mid = session('mid');
        $this->is_login();
        $cart_ids = I('post.cart_ids');
        $address_id = I('post.address_id');
        $remarks    =  I('post.remarks');
        $coupon_id = I('post.coupon_id'); //优惠券 id  可选参数
        $score = I('post.score'); //积分  1/2
        if( !$score ) $score = 1;

        $total = '';//总价
        $ids = explode(',', $cart_ids);
        $logistics_cost = '';//物流价格

        //查购物车信息
        $carts = $this->cart_model->where(array('id' => array('in', $ids)))->select();
        foreach ($carts as $k => $v) {
            $product = $this->product_model->find($v['product_id']);
            if (!$product) exit($this->error( '商品不存在'));

            if ($product['pro_shop_type'] == 2) {
                exit($this->error( '第三方平台商品,请到第三方平台结算'));
            }
            $product_option = $this->product_option_model->find($v['option_key_id']);
            if (!$product_option) exit($this->error( '商品不存在'));

            if ($product_option['inventory'] < $v['quantity'])
                exit($this->error( '商品库存不足'));
            //单种商品价值
            $total += $product_option['option_price']*$v['quantity'];

            //物流价格
            $logistics_cost += $this->logistics_model->computational_cost($product['logistics_id'], $v['quantity'], $product_option['option_price']);

            $data_order_product[] = array(
                'order_id'   => '',
                'product_id' => $v['product_id'],
                'full_name'  => $product['pro_name'],
                'quantity'   => $v['quantity'],
                'snapshot'   => json_encode($product_option),
            );

        }
        //订单号
        $order_sn = $this->order_product_model->getOrderNumber();
        $total +=  $logistics_cost;
        //收货地址
        $data_address = $this->address_model->find($address_id);
        if (!$data_address) exit($this->error('地址错误'));

        //积分
        $score_number = '';

        if ($score == 2) {
            //积分查询
            $score_number = $this->com_sco_model->scoExchange($mid, $total, true);
            $score_price = $this->com_sco_model->scoExchange($mid, $total);
            $total = $total - $score_price['price'];
        }

        // 优惠券计算
        if ($coupon_id) {
            $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'ticket as b on a.tid = b.id';
            $coupon_data = $this->coupon_model
                ->alias('a')
                ->where(['mid' => $mid, 'coupon_id' => $coupon_id])
                ->join($join)
                ->field('a.*,b.full_use,b.price')
                ->find();

            if (!$coupon_data)
                exit($this->error( '优惠券不存在'));

            if ($coupon_data['expiration_time'] < time()) {
                exit($this->error( '优惠券已过期'));
            }

            if ($coupon_data['cou_status'] == CouponModel::STATUS_USED) {
                exit($this->error( '优惠券已被使用'));
            }

            $total = $total - $coupon_data['price'];

        }

        $data_order = [
            'order_type'     => OrderModel::ORDER_TYPE_GOODS,
            'order_sn'       => $order_sn,
            'order_price'    => $total,
            'mid'            => $mid,
            'status'         => OrderProductModel::STATUS_WAIT_FOR_PAY,
            'address'        => json_encode($data_address),
            'logistics_cost' => $logistics_cost,
            'create_time'    => time(),
            'score'          => $score_number,
            'remarks'        =>$remarks,
        ];

        if ($coupon_id) {
            $data_order['coupon_id'] = $coupon_id;
        }

        if (!$this->order_model->create($data_order))
            exit($this->error('失败'));

        $iscommit = true;

        $this->order_model->startTrans();
        if (!$this->order_model->add($data_order)) $iscommit = false;

        if ($score == 2 && $score_number != 0) {
            if (!$this->com_record_model->addOne($score_number, '订单抵扣', $mid, 2)) {
                $iscommit = false;
            }

            if (!$this->com_sco_model->decScore($mid, $score_number)) {
                $iscommit = false;
            }
        }

        if ($coupon_id) {
            if ($this->coupon_model->where(['coupon_id' => $coupon_id])->setField('cou_status', CouponModel::STATUS_USED) === false) {
                $iscommit = false;
            }
        }

        $order_id = $this->order_model->getLastInsID();

        foreach($data_order_product as $k => $v) {
            $order_product[] = array(
                'order_id'   => $order_id,
                'product_id' => $v['product_id'],
                'full_name'  => $v['full_name'],
                'quantity'   => $v['quantity'],
                'snapshot'   => $v['snapshot'],
            );
        }

        if (!$this->order_product_model->addAll($order_product)) {
            $error .= '4';
            $iscommit = false;
        }
        //清除购物车
        if($this->cart_model->delete($cart_ids) == false) {
            $iscommit = false;
        }

        if ($iscommit) {
            $this->order_model->commit();


            $data = [
                'order_sn'       => $order_sn,
            ];

            $this->success('下单成功',U('Web/Pay/pay',$data));
        } else {
            $this->order_model->rollback();

            exit($this->error( '下单失败' . $error));
        }
    }

    public function petbuyItNow()
    {

        $mid = session('mid');
        $this->is_login();
        $product_pet_id = I('post.pet_id');
        $address_id = I('post.address_id');
        $remarks    = I('post.remarks');
        $score = I('post.score');   // 1/2
        if(!$score )  $score = 1;
        $coupon_id = I('post.coupon_id');



        $product_pet = $this->product_pet_model->find($product_pet_id);

        if (!$product_pet)
            exit($this->error( '商品不存在'));

        if ($product_pet['status'] == ProductPetModel::LOCK_ON)
            exit($this->error(  '商品已被其他人下单'));

        $data_address = $this->address_model->find($address_id);
        if (!$data_address) exit($this->error(  '收货地址错误'));
        $order_sn = $this->order_pet_model->getOrderNumber();

        //积分
        $score_number = '';
        //总价
        $total = $product_pet['pet_price'];

        //积分计算
        if ($score == 2) {
            //积分查询
            $score_number = $this->com_sco_model->scoExchange($mid, $total, true);
            $score_price = $this->com_sco_model->scoExchange($mid, $total);
            $total = $total - $score_price['price'];
        }

        // 优惠券计算
        if ($coupon_id) {
            $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'ticket as b on a.tid = b.id';
            $coupon_data = $this->coupon_model
                ->alias('a')
                ->where(['mid' => $mid, 'coupon_id' => $coupon_id])
                ->join($join)
                ->field('a.*,b.full_use,b.price')
                ->find();

            if (!$coupon_data)
                exit($this->error(  '优惠券不存在'));

            if ($coupon_data['expiration_time'] < time()) {
                exit($this->error(  '优惠券已过期'));
            }

            if ($coupon_data['cou_status'] == CouponModel::STATUS_USED) {
                exit($this->error(  '优惠券已被使用'));
            }

            $total = $total - $coupon_data['price'];
        }


        $data = [
            'mid'         => $mid,
            'order_sn'    => $order_sn,
            'order_type'  => OrderModel::ORDER_TYPE_PET,
            'status'      => OrderPetModel::STATUS_WAIT_FOR_PAY,
            'address'     => json_encode($data_address),
            'order_price' => $total,
            'create_time' => time(),
            'score'       => $score_number,
            'remarks'     => $remarks,
        ];

        if ($coupon_id) $data['coupon_id'] = $coupon_id;

        if (!$this->order_model->create($data))
            exit($this->error( '失败' ));


        $iscommit = true;
        $this->order_model->startTrans();
        if (!$this->order_model->add($data)) $iscommit = false;

        $order_id = $this->order_model->getLastInsID();

        $data_pet = [
            'order_id'       => $order_id,
            'product_pet_id' => $product_pet['id'],
            'price'          => $product_pet['pet_price'],
            'snapshot'       => json_encode($product_pet),
            'quantity'       => 1,
        ];


        if ($this->product_pet_model->where(['id' => $product_pet_id])->save(['status' => ProductPetModel::LOCK_ON]) === false)
            $iscommit = false;

        if ($this->order_pet_model->create($data_pet)) {
            if (!$this->order_pet_model->add($data_pet)) $iscommit = false;
        } else {
            $iscommit = false;
        }


        if ($score == 2 && $score_number != 0) {
            if (!$this->com_record_model->addOne($score_number, '订单抵扣', $mid, 2)) {
                $iscommit = false;
//                $error .= '1';
            }

            if (!$this->com_sco_model->decScore($mid, $score_number)) {
//                $error .= '2';
                $iscommit = false;
            }

        }

        if ($iscommit) {
            $this->order_model->commit();
            $data = [
                'order_sn'       => $order_sn,
            ];

            $this->success('下单成功',U('Web/Pay/pay',$data));

        } else {
            $this->order_model->rollback();
            exit($this->error( '错误'));
        }
    }

    /**
     * 新增购物车
     */
    public function addCart()
    {
        $mid = session('mid');

        $this->check_login();
        $id = I('post.product_id');
        $quantity = I('post.quantity');
        $option = I('post.option');

            $result = $this->product_model->find($id);
            if( !$result ) exit($this->returnApiError(BaseController::FATAL_ERROR, '该种类商品不存在'));

            $has = $this->cart_model
                ->where(['product_id' => $id, 'mid' => $mid ,'cart_type'=> OrderModel::ORDER_TYPE_GOODS ,'option_key_id' => $option ])
                ->select();
            if( $has ){
                $data = [
                    'quantity'    => ["exp", "quantity+" . $quantity],
                    'create_time' => time(),
                ];

                $result = $this->cart_model->where(['product_id' => $id, 'mid' => $mid ,'cart_type'=> OrderModel:: ORDER_TYPE_GOODS ,'option_key_id' => $option ])->save($data);
                if ($result === false) exit($this->returnApiError(BaseController::FATAL_ERROR,'修改失败'));
            }else{
                $data = [
                    'mid'           => $mid,
                    'product_id'    => $id,
                    'quantity'      => $quantity,
                    'create_time'   => time(),
                    'option_key_id' => $option,
                    'cart_type'     => OrderModel:: ORDER_TYPE_GOODS,

                ];
                $result = $this->cart_model->add($data);
                if (!$result) exit($this->returnApiError(BaseController::FATAL_ERROR));
            }


        exit($this->returnApiSuccess());
    }



    /**
     * 删除商品
     */
    public function delProduct()
    {
        if (!IS_POST) exit($this->returnApiError(BaseController::INVALID_INTERFACE));
        $mid = session('mid');
        $id = I('post.cartid');
     
        $result = $this->cart_model->delete($id);

        if ($result)
            exit($this->ajaxReturn(1));
        else
            exit($this->ajaxReturn(2));
    }

    /**
     * 立即购买 结算
     * 1.判断是否为第三方平台
     * 2.判断库存
     * 3.判断收获地址
     *
     * 4.如果有优惠券，锁定选择的优惠券
     * 5.如果有积分，扣除积分，用户订单取消的时候，如果有勾选积分，将积分返还给用户
     *
     */
    public function buyItNow()
    {


        $this->is_login();
        $mid = session('mid');

        $product_id = I('post.product_id');
        $product_option_id = I('post.product_option_id');
        $quantity = I('post.quantity');
        $address_id = I('post.address_id');

        $remarks    =   I('post.remarks');

        $coupon_id = I('post.coupon_id'); //优惠券 id  可选参数
        $score = I('post.score'); //积分  1/2


        if( !$score ) $score = 1;

        if (!checkNumber($quantity)) exit($this->error( '参数错误'));

        $product = $this->product_model->find($product_id);
        if (!$product) exit($this->error(  '商品不存在'));

        if ($product['pro_shop_type'] == 2) {
            exit($this->error(  '第三方平台商品,请到第三方平台结算'));
        }

        $data_address = $this->address_model->find($address_id);
        if (!$data_address) exit($this->error(  '收货地址错误'));


        $product_option = $this->product_option_model->find($product_option_id);
        if (!$product_option) exit($this->error(  '商品不存在'));

        if ($product_option['inventory'] < $quantity)
            exit($this->error(  '商品库存不足'));


        $price_unit = $product_option['option_price'];
        //商品价值
        $order_price = $price_unit * $quantity;
        //订单号
        $order_sn = $this->order_product_model->getOrderNumber();
        //物流价格
        $logistics_cost = $this->logistics_model->computational_cost($product['logistics_id'], $quantity, $product_option['option_price']);
        //总价
        $total = $order_price + $logistics_cost;
        //积分
        $score_number = '';

        if ($score == 2) {
            //积分查询
            $score_number = $this->com_sco_model->scoExchange($mid, $total, true);
            $score_price = $this->com_sco_model->scoExchange($mid, $total);
            $total = $total - $score_price['price'];
        }

        // 优惠券计算
        if ($coupon_id) {
            $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'ticket as b on a.tid = b.id';
            $coupon_data = $this->coupon_model
                ->alias('a')
                ->where(['mid' => $mid, 'coupon_id' => $coupon_id])
                ->join($join)
                ->field('a.*,b.full_use,b.price')
                ->find();

            if (!$coupon_data)
                exit($this->error(  '优惠券不存在'));

            if ($coupon_data['expiration_time'] < time()) {
                exit($this->error(  '优惠券已过期'));
            }

            if ($coupon_data['cou_status'] == CouponModel::STATUS_USED) {
                exit($this->error(  '优惠券已被使用'));
            }

            $total = $total - $coupon_data['price'];

        }

        $data_order = [
            'order_type'     => OrderModel::ORDER_TYPE_GOODS,
            'order_sn'       => $order_sn,
            'order_price'    => $total,
            'mid'            => $mid,
            'status'         => OrderProductModel::STATUS_WAIT_FOR_PAY,
            'address'        => json_encode($data_address),
            'logistics_cost' => $logistics_cost,
            'create_time'    => time(),
            'score'          => $score_number,
            'remarks'        => $remarks,
        ];

        if ($coupon_id) {
            $data_order['coupon_id'] = $coupon_id;
        }

        if (!$this->order_model->create($data_order))
            exit($this->error(  $this->order_model->getError()));

        $iscommit = true;
        $error = '';

        $this->order_model->startTrans();
        if (!$this->order_model->add($data_order)) $iscommit = false;
        $order_id = $this->order_model->getLastInsID();


        if ($score == 2 && $score_number != 0) {
            if (!$this->com_record_model->addOne($score_number, '订单抵扣', $mid, 2)) {
                $iscommit = false;
                $error .= '1';
            }

            if (!$this->com_sco_model->decScore($mid, $score_number)) {
                $error .= '2';
                $iscommit = false;
            }

        }

        if ($coupon_id) {
            if ($this->coupon_model->where(['coupon_id' => $coupon_id])->setField('cou_status', CouponModel::STATUS_USED) === false) {
                $error .= '3';
                $iscommit = false;
            }
        }


        $data_order_product = [
            'order_id'   => $order_id,
            'product_id' => $product_id,
            'full_name'  => $product['pro_name'],
            'quantity'   => $quantity,
            'snapshot'   => json_encode($product_option),
        ];

        if ($this->order_product_model->create($data_order_product)) {
            if (!$this->order_product_model->add($data_order_product)) {
                $error .= '4';
                $iscommit = false;
            }
        } else {
            $iscommit = false;
            $error .= $this->order_product_model->getError();
        }

        if ($iscommit) {
            $this->order_model->commit();


            $data = [
                'order_sn'       => $order_sn,
            ];

            $this->success('下单成功',U('Web/Pay/pay',$data));
        } else {
            $this->order_model->rollback();

            exit($this->error( '下单失败' . $error));
        }
    }



}
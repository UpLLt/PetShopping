<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/30
 * Time: 15:04
 */

namespace Appapi\Controller;

/**
 * 购物车综合表
 */

use Common\Model\AddressModel;
use Common\Model\CartModel;
use Common\Model\LogisticsTempModel;
use Common\Model\OrderModel;
use Common\Model\OrderProductModel;
use Common\Model\ProductModel;
use Common\Model\ProductOptionModel;
use Community\Model\ComRecordModel;
use Community\Model\ComScoreModel;
use Consumer\Model\CouponModel;

class ShoppingCartController extends ApibaseController
{
    private $order_model, $cart_model, $product_model , $product_option_model, $com_record_model, $coupon_model, $com_sco_model, $order_product_model, $logistics_model, $address_model;

    public function __construct()
    {
        parent::__construct();
        $this->order_model = new OrderModel();
        $this->cart_model = new CartModel();
        $this->product_model = new ProductModel();
        $this->product_option_model = new ProductOptionModel();
        $this->com_record_model = new ComRecordModel();
        $this->coupon_model = new CouponModel();
        $this->com_sco_model = new ComScoreModel();
        $this->order_product_model = new OrderProductModel();
        $this->logistics_model = new LogisticsTempModel();
        $this->address_model = new AddressModel();
    }

    /**
     * 新增购物车
     */
    public function addCart()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $token = I('post.token');
        $id = I('post.product_id');
        $product_type = I('post.product_type');
        $quantity = I('post.quantity');
        $option = I('post.option');
        $this->checkparam([$mid, $token, $id, $quantity, $product_type]);

        $this->checkisNumber([$quantity]);

        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }


       if(  $product_type == OrderModel:: ORDER_TYPE_GOODS ){
            $result = $this->product_model->find($id);
            if( !$result ) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '该种类商品不存在'));

            $has = $this->cart_model
                ->where(['product_id' => $id, 'mid' => $mid ,'cart_type'=> OrderModel::ORDER_TYPE_GOODS ,'option_key_id' => $option ])
                ->select();
            if( $has ){
                $data = [
                    'quantity'    => ["exp", "quantity+" . $quantity],
                    'create_time' => time(),
                ];

                $result = $this->cart_model->where(['product_id' => $id, 'mid' => $mid ,'cart_type'=> OrderModel:: ORDER_TYPE_GOODS ,'option_key_id' => $option ])->save($data);
                if ($result === false) exit($this->returnApiError(ApibaseController::FATAL_ERROR,'修改失败'));
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
                if (!$result) exit($this->returnApiError(ApibaseController::FATAL_ERROR));
            }
        }else{
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '商品类型不正确'));
        }

        exit($this->returnApiSuccess());
    }

    /**
     * 修改购物车
     */
    public function editCart()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $id = I('post.cartid');
        $product_type = I('post.product_type');
        $quantity = I('post.quantity');
        $this->checkparam(array($mid, $token, $quantity, $id ,$product_type));

        $this->checkisNumber([$quantity]);
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $cart_data = $this->cart_model->find($id);
        if (!$cart_data) exit($this->returnApiSuccess(ApibaseController::FATAL_ERROR, '请刷新购物车'));

        if( $cart_data['cart_type'] != OrderModel::ORDER_TYPE_GOODS ){
            exit($this->returnApiSuccess(ApibaseController::FATAL_ERROR, '不是商品，不能加入购物车'));
        }


        $option = $cart_data['product_id'];

        $inventory = $this->product_option_model->where(['id' => $option])->getField('inventory');

        if( $quantity > $inventory['inventory'] ){
            exit($this->returnApiSuccess(ApibaseController::FATAL_ERROR, '超过最大库存'));
        }else{

            $result = $this->cart_model
                ->where(['id' => $id])
                ->save([
                    'quantity'    => $quantity,
                    'create_time' => time(),
                ]);
            if ($result === false) exit($this->returnApiError(ApibaseController::FATAL_ERROR));
            exit($this->returnApiSuccess());
        }

    }


    /**
     * 删除商品
     */
    public function delProduct()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $token = I('post.token');
        $id = I('post.cartid');

        $this->checkparam([$mid, $token, $id]);
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $result = $this->cart_model->delete($id);
        if ($result)
            exit($this->returnApiSuccess());
        else
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '商品不存在，请刷新页面'));
    }



    /**
     * 购物车列表
     */
    public function cartList()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');

        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR, 'Token无效'));
        }

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
            $data_pro[$k]['price'] = $v['option_price'] * $v['quantity'];
            $data_pro[$k]['inventory'] = $v['inventory'];
            $data_pro[$k]['total_price'] = sprintf("%0.2f", $v['option_price']*$v['quantity']);

        }
        if(!$cart_product) {
            $data_pro = array();
        }

        $data['list'] = $data_pro;
        $datas[] = $data;

        exit( $this->returnApiSuccess($datas));
    }

    /**
     * 购物车结算（下单前）
     */
    public function clearCart() {
        $mid = I('post.mid');
        $token = I('post.token');
        $cart_ids = I('post.cart_ids');

        $this->checkparam([$mid, $token, $cart_ids]);
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $total = '';//总价
        $ids = explode(',', $cart_ids);
        $logistics_cost = '';//物流价格
        $list = array();
        //查购物车信息
        $carts = $this->cart_model
            ->where(array('id' => array('in', $ids)))
            ->select();//dump($carts);
        foreach ($carts as $k => $v) {
            $product = $this->product_model->find($v['product_id']);
            if (!$product) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '商品不存在'));

            if ($product['pro_shop_type'] == 2) {
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '第三方平台商品,请到第三方平台结算'));
            }
            $product_option = $this->product_option_model->find($v['option_key_id']);
            if (!$product_option) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '商品不存在'));

            if ($product_option['inventory'] < $v['quantity'])
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '商品库存不足'));
            //单种商品价值
            $product_one = $product_option['option_price']*$v['quantity'];
            $total += $product_one;

            //物流价格
            $logistics_one = $this->logistics_model->computational_cost($product['logistics_id'], $v['quantity'], $product_option['option_price']);
            $logistics_cost += $logistics_one;
            $images = json_decode($product['smeta'],  true);
            $list[] = array(
                'cart_id' => $v['id'],
                'product_id' => $v['product_id'],
                'name' => $product['pro_name'],
                'quantity' => $v['quantity'],
                'one_price' => sprintf("%0.2f", $product_option['option_price']),
                'option_name' => $product_option['option_name'],
                'total_price' => sprintf("%0.2f", $product_one),
                'logistics_cost' => sprintf("%0.2f", $logistics_one),
                'product_image' => $this->setUrl($images[0]['url']),
            );
        }
        $total += $logistics_cost;
//        dump($list);
        //积分查询
        $score_number = $this->com_sco_model->scoExchange($mid, $total, true);
        $score_price = $this->com_sco_model->scoExchange($mid, $total);
        $data = array(
            'score' => $score_price['score'],
            'score_use' => $score_number,
            'score_price' => $score_price['price'],
            'total_price_sum' => sprintf("%0.2f", $total),
            'total_logistics_sum' => sprintf("%0.2f", $logistics_cost),
            'cart_ids' => $cart_ids,
            'lists' => $list,

        );
        exit($this->returnApiSuccess($data));
    }

    /**
     * 添加订单
     */
    public function createOrder() {
        $mid = I('post.mid');
        $token = I('post.token');
        $cart_ids = I('post.cart_ids');
        $remarks  = I('post.remarks');
        $address_id = I('post.address_id');
        $coupon_id = I('post.coupon_id'); //优惠券 id  可选参数
        $score = I('post.score'); //积分  1/2
        $this->checkparam([$mid, $token, $cart_ids,$address_id]);
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $total = '';//总价
        $ids = explode(',', $cart_ids);
        $logistics_cost = '';//物流价格

        //查购物车信息
        $carts = $this->cart_model->where(array('id' => array('in', $ids)))->select();
        foreach ($carts as $k => $v) {
            $product = $this->product_model->find($v['product_id']);
            if (!$product) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '商品不存在'));

            if ($product['pro_shop_type'] == 2) {
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '第三方平台商品,请到第三方平台结算'));
            }
            $product_option = $this->product_option_model->find($v['option_key_id']);
            if (!$product_option) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '商品不存在'));

            if ($product_option['inventory'] < $v['quantity'])
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '商品库存不足'));
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
        if (!$data_address) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '收货地址错误'));

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
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '优惠券不存在'));

            if ($coupon_data['expiration_time'] < time()) {
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '优惠券已过期'));
            }

            if ($coupon_data['cou_status'] == CouponModel::STATUS_USED) {
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '优惠券已被使用'));
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
            'remarks'        => $remarks
        ];

        if ($coupon_id) {
            $data_order['coupon_id'] = $coupon_id;
        }

        if (!$this->order_model->create($data_order))
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, $this->order_model->getError()));

        $iscommit = true;

        $this->order_model->startTrans();
        if (!$this->order_model->add($data_order)) $iscommit = false;


            $order_id = $this->order_model->getLastInsID();


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
                'order_id'       => $order_id,
                'order_sn'       => $order_sn,
                'total_price'    => sprintf("%0.2f", $total),
                'logistics_cost' => sprintf("%0.2f", $logistics_cost),
            ];

//            Log::record(json_encode($data), Log::WARN);

            exit($this->returnApiSuccess($data));
        } else {
            $this->order_model->rollback();

            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '下单失败'));
        }
    }

    public function delAll(){
        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam([$mid, $token]);
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $rst = $this->cart_model->where(array('mid' => $mid))->delete();
        if($rst) {
            exit($this->returnApiSuccess());
        } else {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '删除失败'));
        }
    }
}
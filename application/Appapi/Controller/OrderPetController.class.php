<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/24
 * Time: 下午3:58
 */

namespace Appapi\Controller;


use Common\Model\AddressModel;
use Common\Model\OrderModel;
use Common\Model\OrderPetModel;
use Community\Model\ComRecordModel;
use Community\Model\ComScoreModel;
use Consumer\Model\CouponModel;
use Issue\Model\ProductPetModel;

/**
 * 宠物订单
 * Class OrderPetController
 * @package Appapi\Controller
 */
class OrderPetController extends ApibaseController
{
    private $order_pet_model;
    private $product_pet_model;
    private $order_model;
    private $com_record_model;
    private $coupon_model;
    private $com_sco_model;
    private $address_model;


    public function __construct()
    {
        $this->order_pet_model = new OrderPetModel();
        $this->product_pet_model = new ProductPetModel();
        $this->order_model = new OrderModel();
        $this->com_record_model = new ComRecordModel();
        $this->coupon_model = new CouponModel();
        $this->com_sco_model = new ComScoreModel();
        $this->address_model = new AddressModel();

        parent::__construct();
    }


    /**
     * 下单前
     */
    public function buyItNowBefour()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $token = I('post.token');
        $product_pet_id = I('post.pet_id');

        $this->checkparam([$mid, $token, $product_pet_id]);
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));


        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'pet_type as b on a.pet_variety_id = b.pet_variety_id';
        $product_pet = $this->product_pet_model
            ->join($join)
            ->alias('a')
            ->field('a.id,a.pet_type,a.pet_variety_id,a.pet_name,a.pet_price,a.pet_picture,b.pet_variety')
            ->find($product_pet_id);


        if (!$product_pet)
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '商品不存在'));
        if ($product_pet['status'] == ProductPetModel::LOCK_ON)
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '商品已被其他人下单'));


//        $cover = $product_pet['smeta'];
        $product_pet['pet_picture'] = json_decode($product_pet['pet_picture'], ture);
        if ($product_pet['pet_picture']) {
            $product_pet['pet_picture'] = $product_pet['pet_picture'][0]['url'];
            $product_pet['pet_picture'] = $this->setUrl($product_pet['pet_picture']);
        }

        $score_number = $this->com_sco_model->scoExchange($mid, $product_pet['pet_price'], true);
        $score_price = $this->com_sco_model->scoExchange($mid, $product_pet['pet_price']);


        $data['Product'] = $product_pet;
        $data['score'] = $score_price['score'];
        $data['score_use'] = $score_number;
        $data['score_price'] = $score_price['price'];

        exit($this->returnApiSuccess($data));
    }


    public function buyItNow()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $product_pet_id = I('post.pet_id');
        $address_id = I('post.address_id');
        $remarks  = I('post.remarks');
        $score = I('post.score');   // 1/2
        $coupon_id = I('post.coupon_id');

        $this->checkparam([$mid, $product_pet_id, $token, $address_id]);

        if (!$this->checktoken($mid, $token))
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));


        $product_pet = $this->product_pet_model->find($product_pet_id);

        if (!$product_pet)
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '商品不存在'));

        if ($product_pet['status'] == ProductPetModel::LOCK_ON)
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '商品已被其他人下单'));

        $data_address = $this->address_model->find($address_id);
        if (!$data_address) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '收货地址错误'));
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
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '优惠券不存在'));

            if ($coupon_data['expiration_time'] < time()) {
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '优惠券已过期'));
            }

            if ($coupon_data['cou_status'] == CouponModel::STATUS_USED) {
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '优惠券已被使用'));
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
            'remarks'     => $remarks
        ];

        if ($coupon_id) $data['coupon_id'] = $coupon_id;

        if (!$this->order_model->create($data))
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, $this->order_model->getError()));


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
        if ($coupon_id) {
            if ($this->coupon_model->where(['coupon_id' => $coupon_id])->setField('cou_status', CouponModel::STATUS_USED) === false)
                $iscommit = false;
        }

        if ($iscommit) {
            $this->order_model->commit();
            exit($this->returnApiSuccess([
                'order_id'    => $order_id,
                'order_sn'    => $order_sn,
                'order_type'  => OrderModel::ORDER_TYPE_PET,
                'order_price' => $total,
            ]));
        } else {
            $this->order_model->rollback();
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '错误'));
        }
    }
}


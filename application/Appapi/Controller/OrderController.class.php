<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/12/5
 * Time: 下午3:14
 */

namespace Appapi\Controller;


use Common\Model\AddressModel;
use Common\Model\CommentModel;
use Common\Model\LogisticsTempModel;
use Common\Model\OrderModel;
use Common\Model\OrderPetModel;
use Common\Model\OrderProductModel;
use Common\Model\OrderRefundModel;
use Common\Model\ProductModel;
use Common\Model\ProductOptionModel;
use Common\Model\RegionModel;
use Community\Model\ComRecordModel;
use Community\Model\ComScoreModel;
use Consumer\Model\CouponModel;
use Consumer\Model\MemberModel;
use Foster\Model\FosterModel;
use Funeral\Model\BuriedModel;
use Issue\Model\ProductPetModel;
use Merchant\Model\HospitalModel;
use Think\Log;
use Transport\Model\TransportModel;


/**
 * 订单
 * Class OrderController
 * @package Appapi\Controller
 */
class OrderController extends ApibaseController
{
    private $order_product_model;
    private $product_model;
    private $product_option_model;
    private $order_model;
    private $address_model;
    private $logistics_model;

    private $com_sco_model;
    private $com_record_model;

    private $coupon_model;

    private $order_pet_model;
    private $product_pet_model;

    private $order_refund_model;
    private $transport_model;
    private $buired_model;
    private $foster_model;


    private $hospital_model;
    private $region_model;

    public function __construct()
    {
        $this->transport_model = new TransportModel();
        $this->buired_model = new BuriedModel();
        $this->foster_model = new FosterModel();
        $this->product_model = new ProductModel();
        $this->product_option_model = new ProductOptionModel();
        $this->order_product_model = new OrderProductModel();
        $this->order_model = new OrderModel();
        $this->address_model = new AddressModel();
        $this->logistics_model = new LogisticsTempModel();
        $this->com_sco_model = new ComScoreModel();

        $this->com_record_model = new ComRecordModel();

        $this->coupon_model = new CouponModel();
        $this->order_pet_model = new OrderPetModel();
        $this->product_pet_model = new ProductPetModel();

        $this->order_refund_model = new OrderRefundModel();

        $this->hospital_model = new HospitalModel();
        $this->region_model = new RegionModel();
        parent::__construct();
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
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $product_id = I('post.product_id');
        $product_option_id = I('post.product_option_id');
        $quantity = I('post.quantity');
        $address_id = I('post.address_id');
        $remarks  = I('post.remarks');
        $coupon_id = I('post.coupon_id'); //优惠券 id  可选参数
        $score = I('post.score'); //积分  1/2

        $this->checkparam([$mid, $token, $product_id, $quantity, $address_id, $product_option_id]);
//        exit($this->returnApiSuccess([$mid, $token, $product_id, $quantity, $address_id, $product_option_id]));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        if (!checkNumber($quantity)) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '参数错误'));

        $product = $this->product_model->find($product_id);
        if (!$product) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '商品不存在'));

        if ($product['pro_shop_type'] == 2) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '第三方平台商品,请到第三方平台结算'));
        }

        $data_address = $this->address_model->find($address_id);
        if (!$data_address) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '收货地址错误'));


        $product_option = $this->product_option_model->find($product_option_id);
        if (!$product_option) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '商品不存在'));

        if ($product_option['inventory'] < $quantity)
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '商品库存不足'));


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
                'order_id'       => $order_id,
                'order_sn'       => $order_sn,
                'total_price'    => $order_price,
                'logistics_cose' => $logistics_cost,
            ];

            Log::record(json_encode($data), Log::WARN);

            exit($this->returnApiSuccess($data));
        } else {
            $this->order_model->rollback();

            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '下单失败' . $error));
        }
    }


    /**
     * 计算物流价格
     */
    public function SettlementLogisticsCost()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $product_id = I('post.product_id');
        $product_option_id = I('post.product_option_id');
        $quantity = I('post.quantity');

        $this->checkparam([$mid, $token, $product_id, $quantity]);

        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $product = $this->product_model->find($product_id);
        if (!$product) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '商品不存在'));

        $product_option = $this->product_option_model->find($product_option_id);
        if (!$product_option) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '规格不存在'));


        if ($product_option['inventory'] < $quantity)
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '商品库存不足'));

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

        $data['score'] = $score_price['score'];
        $data['score_use'] = $score_number;
        $data['score_price'] = $score_price['price'];
        $data['LogisticsCost'] = $LogisticsCost;
        $data['quantity'] = $quantity;
        $data['total'] = $total;

        exit($this->returnApiSuccess($data));
    }


    /**
     * 积分计算
     */
    public function SettlementSco()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $total = I('post.total');

        $this->checkparam([$mid, $token, $total]);

        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $data = $this->com_sco_model->scoExchange($mid, $total);
        exit($this->returnApiSuccess($data));
    }


    /**
     * 订单列表
     */
    public function OrderList()
    {
        $mid = I('post.mid');
        $token = I('post.token');
        $order_status_type = I('post.type');
        $page = I('post.page');
        $pagenum = I('post.pagenum');


        $this->checkparam([$mid, $token, $order_status_type, $page, $pagenum]);
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $where = $this->order_model->getOrderTypeByCode($order_status_type);


        $count = $this->order_model
            ->where(['mid' => $mid ,'shows'=> 1])
            ->where($where)
            ->count();
//        $data['sql'] = $this->order_model->getLastSql();


        $result = $this->order_model
            ->where(['mid' => $mid ,'shows'=> 1])
            ->where($where)
            ->limit($page * ($page - 1), $pagenum)
            ->field('id,mid,order_sn,order_price,order_type,create_time,cover,status,comment_status')
            ->order('id desc')
            ->select();


        foreach ($result as $k => $v) {
            $result[$k]['create_time'] = dateDefault($v['create_time']);
            $result[$k]['cover'] = $v['cover'] ? setUrl($v['cover']) : '';

            $result[$k]['app_key_type'] = $v['order_type'] == OrderModel::ORDER_TYPE_GOODS ? '2' : '1';

            $result[$k]['app_key_return'] = '1';
            $result[$k]['status_value'] = $order_status_type;

            $result[$k]['refund'] = '';
            $result[$k]['refund_status'] = '';

            if ($order_status_type == 'refund') {
                $refund = $this->order_refund_model->where(['mid' => $mid, 'order_id' => $v['id']])->find();
                $result[$k]['refund'] = $this->order_refund_model->getStatusToString($refund['status']);
                $result[$k]['refund_status'] = $refund['status'];
            }

            //退货条件： 1、限定为商品; 2、付款成功; 3、待评价
            if ($v['order_type'] == OrderModel::ORDER_TYPE_GOODS &&
                ($v['status'] == OrderModel::STATUS_PAY_SUCCESS || ($v['status'] == OrderModel::STATUS_COMPLETE && $v['comment_status'] == '0'))
            ) {
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
                    ->field('a.snapshot,a.quantity, b.pro_name as name,b.smeta')
                    ->select();

                foreach ($list as $key => $val) {
                    $val['snapshot'] = json_decode($val['snapshot'], true);
                    $list[$key]['price'] = $val['snapshot']['option_price'] * $val['quantity'];
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
                    ->field('snapshot,price,quantity')
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

                $result[$k]['list'][] = [
                    'name'  => $this->order_model->getOrdrTypetoString($result[$k]['order_type']),
                    'price' => $v['order_price'],
                    'cover' => $v['cover'],
                ];
            }

        }

        if ($count > 0) {
            $Totalpage = $count / $pagenum;
            $Totalpage = floor($Totalpage);
            $b = $count % $pagenum;
            if ($b) $Totalpage += 1;

            $data['Lists'] = $result;
            $data['Page'] = $page;
            $data['Totalpage'] = $Totalpage;

        } else {
            $data['Lists'] = [];
            $data['Page'] = 0;
            $data['Totalpage'] = 0;
        }

        exit($this->returnApiSuccess($data));

    }


    public function detail()
    {
        $mid = I('post.mid');
        $token = I('post.token');
        $order_id = I('post.order_id');

        $this->checkparam([$mid, $token]);
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $result = $this->order_model->find($order_id);

        exit($this->returnApiSuccess($result));
    }


    /**
     * 优惠
     */
    public function preferential()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $order_id = I('post.order_id');
        $coupon_id = I('post.coupon_id'); //优惠券 id  可选参数
        $score = I('post.score'); //积分  1/2

        $this->checkparam([$mid, $token, $order_id, $coupon_id, $score]);
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));


        $order = $this->order_model->find($order_id);
        if (!$order)
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单不存在'));

        $total = $order['order_price'];
        if ($score) {
            $score_price = $this->com_swwco_model->scoExchange($mid, $total);
        }

        $this->order_model->where(['id' => $order_id])->save([
            'score' => $score_price,
        ]);
    }


    /**
     * 取消订单
     */
    public function cancel()
    {
        $mid = I('post.mid');
        $token = I('post.token');
        $order_id = I('post.order_id');

        $this->checkparam([$mid, $token, $order_id]);

        $this->checkparam([$mid, $token]);
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $result = $this->order_model->find($order_id);
        if (!$result) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单不存在'));


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
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '错误'));
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
     * 物流查询接口
     */
    public function logisticsQuery()
    {
        $mid = I('post.mid');
        $token = I('post.token');
        $order_id = I('post.order_id');

        $this->checkparam([$mid, $token, $order_id]);
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $order = $this->order_model->find($order_id);
        if (!$order) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单不存在'));


        if (!$order['logistics_number']) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '没有物流信息'));

        if( $order['order_type'] == OrderModel:: ORDER_TYPE_PET ){
            $data = [
                'logistics_company' => $order['logistics_company'],
                'logistics_number'  => $order['logistics_number'],
                'order_sn'          => $order['order_sn'],
                'list'              => [],
            ];
            exit($this->returnApiSuccess($data));
        }


        vendor('Jisukdcx.JisukdcxMarket');
        $jisukdcx = new \JisukdcxMarket(C('ALI_JISUKDCX_KEY'));
        $result = $jisukdcx->query($order['logistics_number']);
        $result = json_decode($result, true);



        if ($result['msg'] == 'ok') {
            $data = [
                'logistics_company' => $order['logistics_company'],
                'logistics_number'  => $order['logistics_number'],
                'order_sn'          => $order['order_sn'],
                'list'              => $result['result']['list'],

            ];
            exit($this->returnApiSuccess($data));
        } else {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, $result['msg']));
        }

    }


    /**
     * 退货理由
     */
    public function getRefundDescriptionList()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $data['list'] = $this->order_model->refundDescList;
        $data['refund_address'] = '成都市高新区环球中心';
        $data['refund_phone'] = '400-820-888';
        $data['refund_name'] = '张三';
        exit($this->returnApiSuccess($data));
    }


    /**
     * 申请 退款/退货
     */
    public function applyRefund()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $token = I('post.token');
        $order_id = I('post.order_id');
        $refund_id = I('post.refund_id'); //是否需要上传图片  1/2  否/是

        $this->checkparam([$mid, $token, $order_id, $refund_id]);
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $result = $this->order_model->find($order_id);
        if (!$result) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单不存在'));

        //退货条件： 1、限定为商品; 2、付款成功; 3、待评价
        if (!($result['order_type'] == OrderModel::ORDER_TYPE_GOODS && ($result['status'] == OrderModel::STATUS_PAY_SUCCESS || ($result['status'] == OrderModel::STATUS_COMPLETE && $result['comment_status'] == '0')))) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '不满足申请条件'));
        }

        if ($result['returns_status'] == 1) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '已申请退货'));
        }


        $list = $this->order_model->refundDescList;
        foreach ($list as $k => $v) {
            if ($refund_id == $v['id'] && $v['need_photo'] == 2) {
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '需要上传图片'));
            }
        }
        $data = [
            'mid'         => $mid,
            'order_id'    => $order_id,
            'argument'    => $refund_id,
            'create_time' => time(),
        ];

        if (!$this->order_refund_model->create($data)) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, $this->order_refund_model->getError()));
        }

        $iscommit = true;
        $this->order_model->startTrans();
        if ($this->order_model->where(['id' => $order_id])->save(['returns_status' => 1]) === false) {
            $iscommit = false;
        }
        if (!$this->order_refund_model->add($data))
            $iscommit = false;

        if ($iscommit) {
            $this->order_model->commit();
            exit($this->returnApiSuccess());
        } else {
            $this->order_model->rollback();
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '成功'));
        }
    }

    /**
     * 上传
     */
    public function applyRefundPhoto()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $token = I('post.token');
        $order_id = I('post.order_id');
        $refund_id = I('post.refund_id');

        $this->checkparam([$mid, $token, $order_id, $refund_id]);
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $result = $this->order_model->find($order_id);
        if (!$result) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单不存在'));

        //退货条件： 1、限定为商品; 2、付款成功; 3、待评价
        if (!($result['order_type'] == OrderModel::ORDER_TYPE_GOODS && ($result['status'] == OrderModel::STATUS_PAY_SUCCESS || ($result['status'] == OrderModel::STATUS_COMPLETE && $result['comment_status'] == '0')))) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '申请失败'));
        }

        if ($result['returns_status'] == 1) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '已申请退货'));
        }

        $list = $this->order_model->refundDescList;
        foreach ($list as $k => $v) {
            if ($refund_id == $v['id'] && $v['need_photo'] == 1) {
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '不需要上传图片'));
            }
        }

        $imagurl = upload_img('photo');
        if (!$imagurl) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '图片上传失败'));
        }

        $data = [
            'mid'         => $mid,
            'order_id'    => $order_id,
            'argument'    => $refund_id,
            'image'       => $imagurl[0],
            'create_time' => time(),
        ];

        if (!$this->order_model->create($data)) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, $this->order_model->getError()));
        }

        $iscommit = true;
        $this->order_model->startTrans();
        if ($this->order_model->where(['id' => $order_id])->save(['returns_status' => 1]) === false)
            $iscommit = false;

        if (!$this->order_refund_model->add($data))
            $iscommit = false;

        if ($iscommit) {
            $this->order_model->commit();
            exit($this->returnApiSuccess());
        } else {
            $this->order_model->rollback();
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '成功'));
        }
    }


    /**
     * 拉去评论内容
     */
    public function comment()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $token = I('post.token');
        $order_id = I('post.order_id');

        $this->checkparam([$mid, $token, $order_id]);
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $order = $this->order_model->getOrderData($mid, $order_id, 'id,order_type,comment_status');
        if (!$order) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单不存在'));


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

        } else if( $order['order_type'] == OrderModel:: ORDER_TYPE_TRANSPORT){

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
            $result = $this->buired_model
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

        exit($this->returnApiSuccess($data));
    }

    /**
     * 评论
     */
    public function comment_post()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $token = I('post.token');
        $order_id = I('post.order_id');
        $content = I('post.content'); // id`star`text|id`star`text|id`star`text|id`star`text


        $this->checkparam([$mid, $token, $content, $order_id]);

        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $orderdata = $this->order_model->getOrderData($mid, $order_id);
        if (!$orderdata) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单不存在'));

        if ($orderdata['comment_status'] == 1) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单已评价'));
        }

        $comment_model = new CommentModel();
        $member_model = new MemberModel();
        $full_name = $member_model->getNickNameByid($mid);

        $list = explode('|', $content);
        $data = [];
        foreach ($list as $k => $v) {
            if ($v) {
                $list_item = explode('`', $v);

                if (empty($list_item[0]) || empty($list_item[1]) || empty($list_item[2]))
                    exit($this->returnApiError(ApibaseController::FATAL_ERROR, '数据不能为空'));

                $data[] = [
                    'mid'          => $mid,
                    'star'         => $list_item[1],
                    'relevance_id' => $list_item[0],
                    'content'      => $list_item[2],
                    'order_type'   => $orderdata['order_type'],
                    'full_name'    => $full_name,
                    'full_name'    => $full_name,
                    'create_time'  => time(),
                ];
            }
        }
        if (!$data || empty($data) || count($data) < 1) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '数据为空'));
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
            exit($this->returnApiSuccess());
        } else {
            $comment_model->rollback();
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '评论失败'));
        }
    }


    /**
     *
     * 确认收货
     */
    public function confirmReceipt()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $token = I('post.token');
        $order_id = I('post.order_id');
        $this->checkparam([$mid, $token, $order_id]);
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));


        $order = $this->order_model->getOrderData($mid, $order_id);
        if (!$order) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单不存在'));

        if ($order['status'] != OrderModel::STATUS_SEND) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '商品未发货或已完成'));
        }

        if ($this->order_model->setStatus($order_id, OrderModel::STATUS_COMPLETE) === false) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '操作失败,请重试'));
        }

        exit($this->returnApiSuccess());
    }


    public function hide()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $token = I('post.token');
        $order_id = I('post.order_id');
        $this->checkparam([$mid, $token, $order_id]);
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));


        $order = $this->order_model->getOrderData($mid, $order_id);
        if (!$order) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单不存在'));

        if ($order['status'] != OrderModel::STATUS_COMPLETE && $order['comment_status'] != 1) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单未完成,不可隐藏'));
        }

        if ($this->order_model->where(['id' => $order_id])->save(['shows' => 0]) === false) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '操作失败,请重试'));
        }

        exit($this->returnApiSuccess());
    }


    /**
     * 退货->物流信息
     */
    public function refundLogistics()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $token = I('post.token');
        $order_id = I('post.order_id');
        $logistics_company = I('post.logistics_company');
        $logistics_number = I('post.logistics_number');

        $this->checkparam([$mid, $token, $order_id, $logistics_company, $logistics_number]);
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $result = $this->order_refund_model->where(['order_id' => $order_id])->find();
        if (!$result) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '申请不存在'));

        if ($result['status'] != OrderRefundModel::STATUS_APPLY_OK) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '请等待审核通过'));
        }

        $save = $this->order_refund_model->where(['order_id' => $order_id])->save(
            [
                'logistics_company' => $logistics_company,
                'logistics_number'  => $logistics_number,
                'status'            => OrderRefundModel::STATUS_APPLY_SEND,
            ]);

        if ($save === false)
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '物流编号设置错误'));
        exit($this->returnApiSuccess());
    }

}
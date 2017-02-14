<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/12/12
 * Time: 17:30
 */

namespace Appapi\Controller;


use Common\Model\MarriageModel;
use Common\Model\OrderModel;
use Community\Model\ComRecordModel;
use Community\Model\ComScoreModel;
use Consumer\Model\CouponModel;
use Foster\Model\FosterModel;
use Funeral\Model\BuriedModel;
use Transport\Model\TransportModel;

class ScoCouController extends ApibaseController
{

    private $buried_model, $order_model, $coupon_model, $com_score_model,$com_record_model, $marriage_model, $trasport_model, $foster_model;

    public function __construct()
    {
        parent::__construct();
        $this->buried_model = new BuriedModel();
        $this->order_model = new OrderModel();
        $this->coupon_model = new CouponModel();
        $this->com_score_model = new ComScoreModel();
        $this->com_record_model = new ComRecordModel();
        $this->marriage_model = new MarriageModel();
        $this->trasport_model = new TransportModel();
        $this->foster_model = new FosterModel();
    }

    /**
     * 修改订单积分优惠券使用信息
     */
    public function editOrder() {
        $postdata = get_data(1);
        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $this->checkparam($postdata['mid'] ,$postdata['token'], $postdata['score'],$postdata['order_id']);

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
                'total_price' => sprintf("%0.2f", $total),
                'order_id' => $info['id'],
                'score_price'=> '',
                'coupon_price' => '',
            );
            exit($this->returnApiSuccess($return));
        }

        // 优惠券计算
        if ($postdata['coupon_id']) {
            $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'ticket as b on a.tid = b.id';
            $coupon_data = $this->coupon_model
                ->alias('a')
                ->where(['mid' => $mid, 'coupon_id' => $postdata['coupon_id']])
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
            if($this->trasport_model->where(array('order_id' => $postdata['order_id']))->save($data) == false) {
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
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '修改订单信息失败'));
        }

        $return = array(
            'total_price' => sprintf("%0.2f", $total),
            'order_id' => $info['id'],
            'score_price'=> sprintf("%0.2f", $score_price['price']),
            'coupon_price' => sprintf("%0.2f", $coupon_data['price']),
        );
        exit($this->returnApiSuccess($return));

    }
}
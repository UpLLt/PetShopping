<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/12/7
 * Time: 11:41
 */

namespace Appapi\Controller;
define("EARTH_RADIUS",6378.137);//地球半径，单位：公里
define("RADIUS_DISTANCE", 30);//半径30公里

use Common\Model\CommentModel;
use Common\Model\OrderModel;
use Common\Model\WithdrawalsModel;
use Community\Model\ComRecordModel;
use Community\Model\ComScoreModel;
use Consumer\Model\CouponModel;
use Consumer\Model\WalletBillModel;
use Consumer\Model\WalletModel;
use Merchant\Model\HospitalModel;
use Merchant\Model\HospitalShopModel;
use Merchant\Model\MemberShopModel;
use Merchant\Model\ShopTypeModel;

class HospitalController extends ApibaseController
{
    private $hos_shopmodel, $shop_typemodel, $hospital_model, $member_shopmodel, $withdrawal_model, $wallet_model,$wallet_bill_model, $order_model, $com_score_model, $com_record_model, $coupon_model, $comment_model;
    public function __construct()
    {
        parent::__construct();
        $this->hos_shopmodel = new HospitalShopModel();
        $this->shop_typemodel = new ShopTypeModel();
        $this->hospital_model = new HospitalModel();
        $this->member_shopmodel = new MemberShopModel();
        $this->withdrawal_model = new WithdrawalsModel();
        $this->wallet_model = new WalletModel();
        $this->wallet_bill_model = new WalletBillModel();
        $this->order_model = new OrderModel();
        $this->com_score_model = new ComScoreModel();
        $this->com_record_model = new ComRecordModel();
        $this->coupon_model = new CouponModel();
        $this->comment_model = new CommentModel();
    }

    /**
     * 附近列表
     */
    public function getList() {
        $postdata = get_data(1);
        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $this->checkparam($postdata);

//        dump(EARTH_RADIUS);exit;
        $range = 180 / pi() * RADIUS_DISTANCE / EARTH_RADIUS;
        $lngR = $range / cos($postdata['lat'] * pi() / 180);
        $data = array();
        $data["maxLat"] = $postdata['lat'] + $range;
        $data["minLat"] = $postdata['lat'] - $range;
        $data["maxLng"] = $postdata['lng'] + $lngR ;//最大经度
        $data["minLng"] = $postdata['lng'] - $lngR ;//最小经度
        $where['hos_latitude'] = array('between', array($data['minLat'], $data['maxLat']));
        $where['hos_longitude'] = array('between', array($data['minLng'], $data['maxLng']));

        $list = $this->hos_shopmodel
            ->alias('a')
            ->join('left join ego_region as b on a.bu_city = b.code')
            ->join('left join ego_region as c on a.bu_country = c.code')
            ->where($where)
            ->field('a.id as hid, a.hos_name, a.hos_address, a.hos_longitude as lng, a.hos_latitude as lat, a.hos_image, b.name as city, c.name as country')
            ->select();

        if(!$list) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '附近暂无商家入驻'));
        }
        vendor('Coordinate.Coordinate');
        $Coordinate = new \Coordinate();
        $here = array('lng' => $postdata['lng'], 'lat' => $postdata['lat']);
        $distance = $Coordinate->latitude_and_longitude($list,$here);
        $datas  = array();
        foreach ($distance as $k=>$v) {
            $images = json_decode($v['hos_image'], true);
            foreach ($images as $key => $val) {
                $img[] = $val['url'];
            }
            if ($img )
                $imageUrl = setUrl($img);
            else
                $imageUrl = [];

            $datas[] = array(
                'hid' => $v['hid'],
                'hos_name' => $v['hos_name'],
                'hos_address' => $v['city'].$v['country'].$v['hos_address'],
                'lng' => $v['lng'],
                'lat' => $v['lat'],
                'hos_image' => $imageUrl,
                'headimg' => $imageUrl[0] ? $imageUrl[0] : '',
                'distance' => sprintf("%0.2f",$v['len']/1000),
            );
        }
        exit($this->returnApiSuccess($datas));
    }


    /**
     * 医院详情,评论列表
     */
    public function getComment() {
        $postdata = get_data(1);
        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $this->checkparam($postdata);

        //页码
        $page = isset($postdata['page']) && intval($postdata['page'])>1 ? $postdata['page'] : '1';
        //每页显示数量
        $perpage = isset($postdata['perpage']) && intval($postdata['perpage'])>1 ? $postdata['perpage'] : '10';
        $count = $this->comment_model
            ->alias('a')
            ->join('left join ego_member as b on a.mid = b.id')
            ->join('left join ego_com_score as c on a.mid = c.sco_member_id')
            ->where(array('relevance_id' => $postdata['hid'], 'order_type' => OrderModel::ORDER_TYPE_HOSPITAL))
            ->count();
        $list = $this->comment_model
            ->alias('a')
            ->join('left join ego_member as b on a.mid = b.id')
            ->join('left join ego_com_score as c on a.mid = c.sco_member_id')
            ->field('a.content, a.replay, a.create_time, a.star, b.nickname, b.headimg, c.sco_level')
            ->where(array('relevance_id' => $postdata['hid'], 'order_type' => OrderModel::ORDER_TYPE_HOSPITAL, 'status' => CommentModel::ON_EFFECT))
            ->order('create_time desc')
            ->page($page, $perpage)
            ->select();
        /*if(!$list) {
            exit($this->returnApiSuccess());
        }*/
        $datas = array();
        foreach($list as $k => $v) {
            $datas[] = array(
                'nickname' => $v['nickname'] ? $v['nickname'] : '',
                'headimg' => $v['headimg'] ? setUrl($v['headimg']) : '',
                'ho_evaluate' => $v['content'],
                'star' => $v['star'],
                'ho_reply' => $v['replay'],
                'ho_time' => date('Y-m-d', $v['create_time']),
                'sco_level' => $v['sco_level'],
            );
        }
        $rst['lists'] = $datas;
        $totalpage = $count/ $perpage;
        $totalpage = floor($totalpage);
        if($count % $perpage) {
            $totalpage += 1;
        }
        $rst['count'] = $totalpage;
        exit($this->returnApiSuccess($rst));
    }

    /**
     * 医院详情（二维码扫描进入）
     */
    public function getDetail() {
        $postdata = get_data(1);
        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $this->checkparam($postdata);

        $rst = $this->hos_shopmodel
            ->alias('a')
            ->join('left join ego_region as b on a.bu_city = b.code')
            ->join('left join ego_region as c on a.bu_country = c.code')
            ->field('a.sid, a.id as hid, a.hos_name, a.hos_address, a.hos_image, a.shop_status, a.hos_describe, b.name as city, c.name as country')
            ->where(array('a.id' => $postdata['hid']))
            ->find();
        if($rst['shop_status'] == 4) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '该商家账户已被冻结，详情请商家联系客服'));
        }
        $images = json_decode($rst['hos_image'], true);
        foreach ($images as $key => $val) {
            $img[] = $val['url'];
        }
        $datas = array(
            'sid' => $rst['sid'],
            'hid' => $rst['hid'],
            'hos_name' => $rst['hos_name'],
            'hos_address' => $rst['city'].$rst['country'].$rst['hos_address'],
            'cover' => setUrl($img[0]),
//            'images' => setUrl($img),
//            'hos_describe' => $rst['hos_describe'],
        );
        exit($this->returnApiSuccess($datas));
    }

    /**
     * 生成订单
     */
    public function createOrder() {
        $postdata = get_data(1);
        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $this->checkparam(array($postdata['mid'], $postdata['token'], $postdata['order_price'], $postdata['hid'], $postdata['score']));

        $detail = $this->hos_shopmodel
            ->where(array('id' => $postdata['hid']))
            ->field('hos_image,sid')
            ->find();
        $images = json_decode($detail['hos_image'], true);
        $total = $postdata['order_price'];
        //积分
        $score_number = '';

        if ($postdata['score'] == 2) {
            //积分查询
            $score_number = $this->com_score_model->scoExchange($mid, $total, true);
            $score_price = $this->com_score_model->scoExchange($mid, $total);
            $total = $total - $score_price['price'];
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
        $data = array(
            'order_sn' => $this->order_model->getOrderNumber(),
            'order_type' => OrderModel::ORDER_TYPE_HOSPITAL,
            'status' => OrderModel::STATUS_WAIT_FOR_PAY,
            'create_time' => time(),
            'comment_status' => 0,
            'cover' => $images ? setUrl($images[0]['url']) : '',
            'mid' => $mid,
            'order_price' => $total,
            'coupon_id'      => $postdata['coupon_id'],
            'score'          => $score_number,
        );
        $this->order_model->startTrans();
        $iscommit = true;
        $rst = $this->order_model->add($data);
        if(!$rst) {
            $iscommit = false;
        }
        if ($postdata['score'] == 2 && $score_number) {
            if (!$this->com_record_model->addOne($score_number, '订单抵扣', $mid, 2))
                $iscommit = false;

            if (!$this->com_score_model->decScore($mid, $score_number))
                $iscommit = false;
        }

        if ($postdata['coupon_id']) {
            if ($this->coupon_model->where(['coupon_id' => $postdata['coupon_id']])->setField('cou_status', CouponModel::STATUS_USED) === false)
                $iscommit = false;
        }
        $info = array(
            'order_sid' => $rst,
            'ho_price' => $total,
            'sid' => $detail['sid'],
            'hid' => $postdata['hid'],
        );
        $res = $this->hospital_model->add($info);
        if(!$res) {
            $iscommit = false;
        }
        if($iscommit) {
            $this->order_model->commit();
        } else {
            $this->order_model->rollback();
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单生成失败'));
        }
        $return = array(
            'order_id' => $rst,
            'order_price' => sprintf("%0.2f", $total),
            'hid' => $postdata['hid'],
        );
        exit($this->returnApiSuccess($return));
    }

    /**
     * 未支付删除订单
     */
    public function delOrder() {
        $postdata = get_data(1);
        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $this->checkparam($postdata['mid'], $postdata['token '], $postdata['order_id']);

        $this->order_model->startTrans();
        $iscommit = true;
        $info = $this->order_model->where(array('id' => $postdata['order_id']))->find();
        $rst = $this->order_model->where(array('id' => $postdata['order_id']))->delete();
        if(!$rst) {
            $iscommit = false;
        }
        if($info['coupon_id']) {
            if($this->coupon_model->where(['coupon_id' => $info['coupon_id']])->setField('cou_status', CouponModel::STATUS_VALIDITY) == false) {
                $iscommit = false;
            }
        }
        if($info['score']) {
            if($this->com_score_model->where(array('sco_member_id' => $mid)) -> setInc('sco_now', $info['score']) == false) {
                $iscommit = false;
            }
        }
        $res = $this->hospital_model->where(array('order_sid' => $postdata['order_id']))->delete();
        if(!$res) {
            $iscommit = false;
        }
        if($iscommit) {
            $this->order_model->commit();
        } else {
            $this->order_model->rollback();
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '删除订单失败'));
        }
        exit(($this->returnApiSuccess()));

    }
}
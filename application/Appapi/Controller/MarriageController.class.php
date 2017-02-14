<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/28
 * Time: 17:11
 */

namespace Appapi\Controller;



use Common\Model\MarriageModel;
use Common\Model\OrderModel;
use Common\Model\PetModel;
use Common\Model\PetTypeModel;
use Common\Model\RegionModel;
use Community\Model\ComRecordModel;
use Community\Model\ComScoreModel;
use Consumer\Model\CouponModel;
use Marriage\Model\WeddingRulesModel;

class MarriageController extends ApibaseController
{
    private $marriage_model, $pet_typemodel, $pet_model, $order_model, $wedding_model, $coupon_model, $com_score_model,$com_record_model;
    private $region_model;
    public function __construct()
    {
        parent::__construct();
        $this->marriage_model = new MarriageModel();
        $this->pet_typemodel = new PetTypeModel();
        $this->pet_model = new PetModel();
        $this->order_model = new OrderModel();
        $this->wedding_model = new WeddingRulesModel();
        $this->coupon_model = new CouponModel();
        $this->com_score_model = new ComScoreModel();
        $this->com_record_model = new ComRecordModel();
        $this->region_model = new RegionModel();
    }

    /**
     *婚介发布
     */
    public function publish()
    {
        $postdata = get_data(1);
        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $this->checkparam(array($postdata['mid'], $postdata['pe_name'], $postdata['pet_variety_id'], $postdata['pe_age'], $postdata['pe_breeding'], $postdata['pe_phone'], $postdata['token']));

        if(strlen($postdata['pe_phone']) != 11) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '手机号格式错误'));
        }
        $imagurl = upload_img('Marriage');
        if(!$imagurl) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '图片上传失败'));
        }
        $pe_province  = $this->region_model->getNamForCode($postdata['pe_province']);
        $pe_city  = $this->region_model->getNamForCode($postdata['pe_city']);

        $data = array(
            'pe_area' => $pe_province .'/'.$pe_city,
            'pe_type' => $postdata['pet_variety_id'],
            'pe_name' => $postdata['pe_name'],
            'pe_age' => $postdata['pe_age'],
            'pe_picture' => json_encode($imagurl),
            'pe_breeding' => $postdata['pe_breeding'],
            'pe_phone' => $postdata['pe_phone'],
            'pe_status' => 1,
            'create_time' => time(),
            'pe_member_id' => $mid,
        );
        $rst = $this->pet_model->add($data);
        if($rst) {
            exit($this->returnApiSuccess());
        } else {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '发布失败'));
        }
    }

    /**
     * 生成订单
     */
    public function order() {
        $postdata = get_data(1);
        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $this->checkparam(array($mid, $token, $postdata['order_price'], $postdata['pid'], $postdata['is_sperm'], $postdata['is_ovulation']));

        $this->order_model->startTrans();
        $iscommit = true;
        $we_sperm = 0;
        $we_ovulation = 0 ;
        if($postdata['is_sperm'] == 1) {//验精
            $we_sperm = $postdata['we_sperm'];

        }
        if($postdata['is_ovulation'] == 1) {//测排卵
            $we_ovulation = $postdata['we_ovulation'];
        }
        $total = $postdata['order_price'];

        //查宠物婚介图片
        $petinfo = $this->pet_model
            ->join('ego_pet_type on ego_pet.pe_type = ego_pet_type.pet_variety_id')
            ->where(array('pid' => $postdata['pid']))
            ->field('pet_variety, pe_name, pe_age, pe_picture, pe_breeding, pid')
            ->find();
        $petimg = json_decode($petinfo['pe_picture'], true);
        $order = array(
            'order_sn' => $this->order_model->getOrderNumber(),
            'order_type' => OrderModel::ORDER_TYPE_MARRIAGE,
            'status' => OrderModel::STATUS_WAIT_FOR_PAY,
            'order_price' => $total,
            'create_time' => time(),
            'comment_status' => 1,
            'cover' => setUrl($petimg[0]),
            'mid' => $mid,
        );
        $rst = $this->order_model->add($order);
        if(!$rst) {
            $iscommit = false;
        }
        $marriage = array(
            'order_sid' => $rst,
            'pid' => $postdata['pid'],
            'ma_breeding_price' => $postdata['ma_breeding_price'],
            'ma_ovulation' => $we_ovulation,
            'ma_sperm' => $we_sperm,
            'ma_sprice' => $postdata['order_price'],
        );
        $res = $this->marriage_model->add($marriage);
        if(!$res) {
            $iscommit = false;
        }

        if($iscommit) {
            $this->order_model->commit();
        } else {
            $this->order_model->rollback();
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单生成失败'));
        }
        //积分查询
        $score_number = $this->com_score_model->scoExchange($mid, $postdata['order_price'], true);
        $score_price = $this->com_score_model->scoExchange($mid, $postdata['order_price']);
        $return = array(
            'order_id' => $rst,
            'order_price' => $postdata['order_price'],
            'cover' => setUrl($petimg[0]),
            'name' => $petinfo['pe_name'],
            'score' => $score_price['score'],
            'score_use' => $score_number,
            'score_price' => $score_price['price'],
            'total_logistics_sum' => 0,
//            'pe_age' => $petinfo['pe_age'],
//            'pet_variety' => $petinfo['pet_variety'],
            //            'ma_breeding_price' =>  $postdata['ma_breeding_price'],
        );
        exit($this->returnApiSuccess($return));
    }

    /**
     * 自己发布的婚介信息
     * status 1、待审核，2、审核通过，3、审核拒绝
     */
    public function ownList() {
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
        if($postdata['pe_type']) {
            $where['pe_type'] = $postdata['pe_type'];
        }
        $where['pet_type'] = $postdata['pet_type'];//1/猫 2/狗
        $where['pe_status'] = $postdata['status'];
        $where['pe_member_id'] = $mid;
        $count = $this->pet_model
            ->join('ego_pet_type on ego_pet.pe_type = ego_pet_type.pet_variety_id')
            ->where($where)
            ->count();
        $list = $this->pet_model
            ->join('ego_pet_type on ego_pet.pe_type = ego_pet_type.pet_variety_id')
            ->where($where)
            ->order($order)
            ->page($page, $perpage)
            ->field('pet_variety, pe_name, pe_age, pe_picture, pe_breeding, pid')
            ->select();
        $data = array();
        foreach($list as $k => $v) {
            $imgurl = json_decode($v['pe_picture'], true);
            $url = setUrl($imgurl);
            $data['list'][] = array(
                'pid' => $v['pid'],
                'pe_name' => $v['pe_name'],
                'pe_picture' => $url,
                'pe_breeding' => $v['pe_breeding'],
                'pe_age' => $v['pe_age'],
                'pet_variety' => $v['pet_variety'],
                'status' => $postdata['status'],
            );
        }
        $totalpage = $count/ $perpage;
        $totalpage = floor($totalpage);
        if($count % $perpage) {
            $totalpage += 1;
        }
        $data['count'] = $totalpage;
        if($data) {
            exit($this->returnApiSuccess($data));
        } else {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '暂无'));
        }

    }

    /**
     * 获取婚介首页已通过並且沒有下架的列表
     */
    public function lists() {
        $postdata = get_data(1);
        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $this->checkparam(array($mid,$token,$postdata['pet_type'],$postdata['price']));

        //页码
        $page = isset($postdata['page']) && intval($postdata['page'])>1 ? $postdata['page'] : '1';
        //每页显示数量
        $perpage = isset($postdata['perpage']) && intval($postdata['perpage'])>1 ? $postdata['perpage'] : '10';

        if($postdata['keyword']) {
            $where['pe_name'] = array('like', '%'.$postdata['keyword'].'%');
        }
        if($postdata['pe_type']) {
            $where['pe_type'] = $postdata['pe_type'];
        }
        $order = 'ego_pet.create_time desc';
        if($postdata['price'] == 1) {
            $order = 'pe_breeding desc';
        } elseif($postdata['price'] == 2) {
            $order = 'pe_breeding asc';
        }
        $where['pet_type'] = $postdata['pet_type'];//1/猫 2/狗
        $where['pe_status'] = 2;
        $where['pe_state'] = 1;
        $count = $this->pet_model
            ->join('ego_pet_type on ego_pet.pe_type = ego_pet_type.pet_variety_id')
            ->where($where)
            ->count();
        $list = $this->pet_model
            ->join('ego_pet_type on ego_pet.pe_type = ego_pet_type.pet_variety_id')
            ->where($where)
            ->order($order)
            ->page($page, $perpage)
            ->field('pet_variety, pe_name, pe_age, pe_picture, pe_breeding, pid ,pe_area')
            ->select();
        $data = array();
        $price = $this->wedding_model->select();
        foreach($list as $k => $v) {
            $imgurl = json_decode($v['pe_picture'], true);
            $url = setUrl($imgurl);
            $data['list'][] = array(
                'pid' => $v['pid'],
                'pe_name' => $v['pe_name'],
                'pe_picture' => $url,
                'pe_breeding' => $v['pe_breeding'],
                'pe_age' => $v['pe_age'],
                'pet_variety' => $v['pet_variety'],
                'pe_area'=>$v['pe_area']

            );
        }
        $totalpage = $count/ $perpage;
        $totalpage = floor($totalpage);
        if($count % $perpage) {
            $totalpage += 1;
        }
        $data['count'] = $totalpage;
        $data['we_sperm'] = $price[0]['we_sperm'];
        $data['we_ovulation'] = $price[0]['we_ovulation'];
        if(!$list) {
            $data['list'] = array();
        }

        exit($this->returnApiSuccess($data));

    }


}
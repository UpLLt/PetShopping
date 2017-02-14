<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/22
 * Time: 19:13
 */

namespace Appapi\Controller;

use Common\Model\OrderModel;
use Common\Model\RegionModel;
use Community\Model\ComScoreModel;
use Foster\Model\FosterModel;
use Foster\Model\FosterRulesModel;
use Transport\Model\TransportRulesModel;

class FosterController extends ApibaseController
{
    private $region_model;
    private $transport_rules_model;
    private $foster_rules_model;
    private $foster_model;
    private $order_model;
    private $com_score_model;

    public function __construct()
    {
        parent::__construct();
        $this->region_model = new RegionModel();
        $this->transport_rules_model = new TransportRulesModel();
        $this->foster_rules_model = new FosterRulesModel();
        $this->foster_model = new FosterModel();
        $this->order_model = new OrderModel();
        $this->com_score_model = new ComScoreModel();
    }


    /**
     * 寄养服务
     */
    public function foster_service(){

        if( !IS_POST ) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $country = I('post.country');

        $this->checkparam(array($mid, $token,$country));
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $foster_rules = $this->foster_rules_model->where('fos_country='.$country)->find();

        if(empty($foster_rules)) exit($this->returnApiError(ApibaseController::FATAL_ERROR,'该地区尚未设置'));

        $fos_weight = json_decode($foster_rules['fos_weight'],true);


        foreach( $fos_weight as $k => $v){
            $weight[$k]['weight'] = $v['start'].'kg - '.$v['end'].'kg';
            $weight[$k]['id']     = $k;
            $weight[$k]['price']  = $v['price'];
        }
        $data['send_address'] = $foster_rules['fos_send_addre'];
        $data['weight'] = $weight;
        $data['service'] = 'https://www.mixiupet.com/Wap/Company/serviceInfo/type/2/code/'.$country;

        exit($this->returnApiSuccess($data));
    }


    /**
     * 生成订单
     */
    public function foster(){

        $mid         = I('post.mid');
        $token       = I('post.token');
        $ptype       = I('post.ptype');
        $fo_age      = I('post.fo_age');
        $vaccine_status  = I('post.vaccine_status'); //是否疫苗
        $vaccine_content  = I('post.vaccine_content');//疫苗情况
        $vaccine_time  = I('post.vaccine_time');//疫苗时间
        $fo_service  = I('post.fo_service'); //传 ID
        $time_start  = I('post.time_start');
        $time_end    = I('post.time_end');
        $fo_dog_food = I('post.fo_dog_food'); //    1/2  自带/不自带
        $fo_contacts = I('post.fo_contacts');
        $fo_contacts_phone  = I('post.fo_contacts_phone'); //
        $fo_pickup = I('post.fo_pickup'); //    1/2  门店自取/客户送货上门
        $fo_address  = I('post.fo_address');
        $fo_country = I('post.fo_country');

        if(!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $this->checktoken($mid,$token,$ptype,$fo_age,$vaccine_status,$fo_service,$time_start,$time_end,$fo_dog_food,$fo_contacts,$fo_contacts_phone,$fo_pickup,$fo_address,$fo_country);
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        if( strlen($fo_contacts_phone) != 11 ) exit($this->returnApiError(ApibaseController::FATAL_ERROR,'电话号码格式不正确'));

        $foster_rules = $this->foster_rules_model->where('fos_country='.$fo_country)->find();

        if( !$foster_rules ) exit($this->returnApiError(ApibaseController::FATAL_ERROR,'该地区尚未设置'));


        //宠物总量价格选择
        $foster_rules['fos_weight'] = json_decode($foster_rules['fos_weight'],true);

        foreach(  $foster_rules['fos_weight'] as $k => $v){
            if( $fo_service == $k ){
                $now['service_price'] = $v['price'];
                $now['service'] = $v['start'].'kg - '.$v['end'].'kg';
            }
        }

        //宠物时间计算
        $startdate=strtotime($time_start);
        $enddate=strtotime($time_end);
        $days= round(($enddate-$startdate)/3600/24) ;

        //判断折扣
        $foster_rules['fos_discount'] = json_decode($foster_rules['fos_discount'],true);

        $sort = array(
            'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
            'field'     => 'date',
        );
        $arrSort = array();
        foreach($foster_rules['fos_discount'] AS $uniqid => $row){
            foreach($row AS $key=>$value){
                $arrSort[$key][$uniqid] = $value;
            }
        }
        if($sort['direction']){
            array_multisort($arrSort[$sort['field']], constant($sort['direction']), $foster_rules['fos_discount']);
        }

        for ($i = 0; $i < count($foster_rules['fos_discount']); $i++) {
            if ( $days >= $foster_rules['fos_discount'][$i]['date'] ) {
                $now['num'] = $foster_rules['fos_discount'][$i]['num'] / 10;
            }
        }
        if( !$now['num'] )  $now['num'] = 1;

        //取货价格
        $now['pick_up'] = $fo_pickup == 1 ? $foster_rules['fos_price'] : '0' ;
        $address        = $fo_pickup == 1 ? $fo_address :$foster_rules['fos_send_addre'];

        //狗粮价格
        $now['fo_dog_food'] = ($fo_dog_food == '2' ? ($foster_rules['fos_dog_food'] * $days) : '0' );

        //寄养费用
        $fo_foster_price = ( $days * $now['service_price'] ) * $now['num'] ;

        $price = $fo_foster_price + $now['pick_up'] +  $now['fo_dog_food'];
//        dump($price);exit;
        $this->foster_model->startTrans();
        $is_commit = true;

        $order = array(
            'order_sn' => $this->order_model->getOrderNumber(),
            'order_type' => OrderModel::ORDER_TYPE_FOSTER,
            'status'=> OrderModel::STATUS_WAIT_FOR_PAY,
            'order_price'=>$price,
            'create_time'=>time(),
            'mid'=>$mid,
            'cover' => C('DEFAULT_JIYANG_URL'),
            'comment_status' => 0,
        );

        $order_id = $this->order_model->add($order);
        if( !$order_id ) {
            $is_commit = false ;
        }

        //宠物疫苗情况
        if( $vaccine_status == 1 ){
            $vaccine['status'] = $vaccine_status;
            $vaccine['content'] = $vaccine_content;
            $vaccine['time'] = $vaccine_time;
        }else{
            $vaccine['status'] = $vaccine_status;
        }
        $vaccine = json_encode($vaccine);


        $data = array(
            'order_id'=>$order_id,
            'ptype'   =>$ptype,
            'fo_age'=>$fo_age,
            'fo_vaccine'=>$vaccine,
            'fo_service'=>$now['service'],
            'fo_faster_time'=>$time_start.'至'.$time_end,
            'fo_dog_food'=>$fo_dog_food,
            'fo_contacts'=>$fo_contacts,
            'fo_contacts_phone'=>$fo_contacts_phone,
            'fo_pickup'=>$fo_pickup,
            'fo_area'=>$fo_country,
            'fo_address'=>$address,
            'fo_foster_price'=>$fo_foster_price,
            'fo_pickup_price'=>$now['pick_up'],
            'fo_price'=>$price,

        );
        $result = $this->foster_model->add($data);
        if( !$result ) {
            $is_commit = false ;
        }

        if($is_commit){
            $this->foster_model->commit();
        }else{
            $this->foster_model->rollback();
            exit($this->returnApiError(ApibaseController::FATAL_ERROR,'生成订单失败'));
        }
        //积分查询
        $score_number = $this->com_score_model->scoExchange($mid, $price, true);
        $score_price = $this->com_score_model->scoExchange($mid, $price);

        /* $return['order_price'] = sprintf("%.2f",$price);
         $return['foster_price'] = sprintf("%.2f",$fo_foster_price);
         $return['pickup_price'] = $now['pick_up'];
         $return['order_id'] = $order_id;*/
        $return = array(
            'order_id' => $order_id,
            'total_logistics_sum' => $now['pick_up'],
            'order_price' => sprintf("%.2f",$price),
            'name' => '宠物寄养',
            'cover' => C('DEFAULT_JIYANG_URL'),
            'score' => $score_price['score'],
            'score_use' => $score_number,
            'score_price' => $score_price['price'],
        );
        exit($this->returnApiSuccess($return));
    }




}
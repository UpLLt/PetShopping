<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/20
 * Time: 15:38
 */

namespace Appapi\Controller;
use Common\Model\AddressModel;
use Common\Model\CommentModel;
use Common\Model\OrderModel;
use Community\Model\ComScoreModel;
use Consumer\Model\MemberModel;
use Transport\Model\TransportModel;
use Transport\Model\TransportRulesModel;


/**
 * 运输订单添加
 * Class AddressController
 * @package Appapi\Controller
 */
class TransportController extends ApibaseController
{

    private $trasport_model ,$trasport_rules_model ,$order_model, $com_score_model ,$comment_model ,$member_model ,$Com_scoModel;

    public function __construct()
    {
        parent::__construct();
        $this->trasport_model = new TransportModel();
        $this->trasport_rules_model = new TransportRulesModel();
        $this->order_model = new OrderModel();
        $this->com_score_model = new ComScoreModel();
        $this->comment_model = new CommentModel();
        $this->member_model = new MemberModel();
        $this->Com_scoModel = new ComScoreModel();

    }

    /**
     * 用户评论
     */
    public function comment(){

        if( !IS_POST ) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $order_type = I('post.order_type');
        $page = I('post.page');
        $perpage = I('post.perpage');
        $this->checkparam(array($order_type,$page,$perpage));

        $page = isset($page) && intval($page)>1 ? $page : '1';
        $perpage = isset( $perpage ) && intval( $perpage )>1 ? $perpage : '10';

        $count = $this->comment_model
            ->where(array('order_type'=>$order_type, 'status' => 2))
            ->field('content,full_name,star,create_time,mid')
            ->count();

        $comment = $this->comment_model
                        ->where(array('order_type'=>$order_type, 'status' => 2))
                        ->field('content,full_name,star,create_time,mid, replay')
                        ->order('id desc')
                        ->page($page, $perpage)
                        ->select();

        foreach( $comment as $k => $v ){
            $info = $this->Com_scoModel->info($v['mid']);
            $comment[$k]['create_time'] = date('Y-m-d',$v['create_time']);
            $comment[$k]['heading'] = $this->member_model->getNickNameByHeading($v['mid']);
            $comment[$k]['sco_level'] = $info['sco_level'];
        }

        $totalpage = $count/ $perpage;
        $totalpage = floor($totalpage);
        if($count % $perpage) {
            $totalpage += 1;
        }
        $data['count'] = $totalpage;
        $data['lists'] = $comment;
        $data['about_company'] = $this->geturl('/Wap/Company/about_company/order_type/'.$order_type);
        exit($this->returnApiSuccess($data));
    }

    public function airport(){
        $transport = D('transport_airport')->where('keyword="transport"')->find();
        $lists = explode(',',$transport['content']);
        foreach( $lists as $k => $v ){
            $data[$k]['airport'] = $v;
        }

        exit($this->returnApiSuccess($data));
    }

    /**
     * 获取笼子价格 推荐合适的
     */
    public function cage(){

        if( !IS_POST ) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $weight = I('post.weight');
        $country = I('post.country');

        $this->checkparam(array($mid, $token,$weight,$country));
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $trasport_rules = $this->trasport_rules_model->where('tr_country='.$country)->field('tr_cage,tr_pratique,tr_send_addre')->find();

        if(empty($trasport_rules)) exit($this->returnApiError(ApibaseController::FATAL_ERROR,'该地区尚未设置'));

        $trcage = json_decode($trasport_rules['tr_cage'],true);
        foreach( $trcage as $k => $v){
            if( $weight > $v['start'] && $weight<= $v['end'] ){
                $toweight['price'] = $v['price'];
                $toweight['mark'] = $k;
                $toweight['name'] =  $this->trasport_rules_model->getCagename($k);

            }
        }
            $cage[] = $toweight;
            if( !$toweight ) exit($this->returnApiError(ApibaseController::FATAL_ERROR,'体重超过运输承受限制'));

            $cage[] = array(
                    'price'=> '0',
                    'mark'=>'own',
                    'name'=>$this->trasport_rules_model->getCagename('own'),
            );
            $data['pratique'] = $trasport_rules['tr_pratique'];
            $data['send_address'] = $trasport_rules['tr_send_addre'];
            $data['cage'] = $cage;
            $data['service'] = 'https://www.mixiupet.com/Wap/Company/serviceInfo/type/3/code/'.$country;

            exit($this->returnApiSuccess($data));
    }


    /**
     * 生成订单
     */
    public function transport(){

        $mid         = I('post.mid');
        $token       = I('post.token');
        $Air         = I('post.air'); //传值 1/ 2 厦航/其他
        $weight      = I('post.weight');
        $tr_receiver = I('post.tr_receiver');
        $tr_receive_phone = I('post.tr_receive_phone');
        $tr_contacts = I('post.tr_contacts');
        $tr_contacts_phone = I('post.tr_contacts_phone');
        $tr_pickup   = I('post.tr_pickup'); //    1/2  自取/客户送货上门
        $country     = I('post.country');
        $cage        = I('post.cage'); //    1/2  带购/自带
        $tr_pratique = I('post.tr_pratique'); //    1/2  带购/自带
        $tr_address  = I('post.tr_address');
        $tr_receiver_air = I('post.tr_receiver_air');

        if(!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $this->checkparam(array($mid,$token,$Air,$weight,$tr_receiver,$tr_receive_phone,$tr_contacts,$tr_contacts_phone,$tr_pickup,$country,$cage,$tr_pratique,$tr_address,$tr_receiver_air));

        if( strlen($tr_receive_phone) != 11 ||  strlen($tr_contacts_phone) != 11 ) exit($this->returnApiError(ApibaseController::FATAL_ERROR,'电话号码格式不正确'));
        $trasport_rules = $this->trasport_rules_model->where('tr_country='.$country)->find();

        if( !$trasport_rules ) exit($this->returnApiError(ApibaseController::FATAL_ERROR,'该地区尚未设置'));


        //宠物重量价格
        $trasport_rules['tr_weight'] = json_decode($trasport_rules['tr_weight'],true);
        foreach( $trasport_rules['tr_weight'] as $k => $v){
            if( $weight > $v['start'] && $weight<= $v['end'] ){
                $now['weight_price'] = $v['price'];
            }
        }
        //宠物笼子价格及重量
        $trasport_rules['tr_cage'] = json_decode($trasport_rules['tr_cage'],true);
        foreach( $trasport_rules['tr_cage'] as $k => $v){
            if( $weight > $v['start'] && $weight<= $v['end'] ){
                $now['cage_price']  = $v['price'];
                $now['cage_weight'] = $v['cage'];
            }
        }

        //对比价格
        $compare_price = $Air == 1 ? $trasport_rules['tr_eastair'] : $trasport_rules['tr_otherair'] ;

        //运输价格
        $transport_price = ( $weight +  $now['cage_weight'] ) * $now['weight_price'] ;
        $transport_price = $transport_price >= $compare_price ? $transport_price : $compare_price;

        //笼子价格
        $cage_price     = $cage == 1 ? $now['cage_price'] : 0;

        //检疫证价格
        $pratique_price = $tr_pratique == 1 ? $trasport_rules['tr_pratique'] : 0;

        $transport_price = $transport_price + $cage_price + $pratique_price ;
        //取货价格
        $tr_pickup_price = $tr_pickup == 1 ? $trasport_rules['tr_price'] : '0' ;

        $price = $transport_price + $tr_pickup_price;

        $this->trasport_model->startTrans();
        $is_commit = true;

        $order = array(
            'order_sn' => $this->order_model->getOrderNumber(),
            'order_type' => OrderModel::ORDER_TYPE_TRANSPORT,
            'status'=> OrderModel::STATUS_WAIT_FOR_PAY,
            'order_price'=>$price,
            'create_time'=>time(),
            'mid'=>$mid,
            'cover' => C('DEFAULT_YUNSHU_URL'),
            'comment_status' => 0,
        );

        $order_id = $this->order_model->add($order);
        if( !$order_id ) {

            $is_commit = false ;
        }

        $address = $tr_pickup == 1 ? $tr_address : $trasport_rules['tr_send_addre'] ;

        $data = array(
            'order_id'=>$order_id,
            'tr_flight'=>$Air,
            'tr_weight'=>$weight,
            'tr_receiver'=>$tr_receiver,
            'tr_receive_phone'=>$tr_receive_phone,
            'tr_pickup'=>$tr_pickup,
            'tr_area'=>$country,
            'tr_address'=>$address,
            'tr_cage'=>$cage,
            'tr_pratique'=>$tr_pratique,
            'tr_price'=>$price,
            'tr_contacts'=>$tr_contacts,
            'tr_contacts_phone'=>$tr_contacts_phone,
            'tr_trans_price'=>$transport_price,
            'tr_pickup_price'=>$tr_pickup_price,
            'tr_receiver_air'=>$tr_receiver_air
        );
        $result = $this->trasport_model->add($data);
        if( !$result ) {
            $is_commit = false ;
        }
        if($is_commit){
            $this->trasport_model->commit();
        }else{
            $this->trasport_model->rollback();
            exit($this->returnApiError(ApibaseController::FATAL_ERROR,'生成订单失败'));
        }

        //积分查询
        $score_number = $this->com_score_model->scoExchange($mid, $price, true);
        $score_price = $this->com_score_model->scoExchange($mid, $price);
        /*$return['price'] = sprintf("%.2f",$price);
        $return['transport_price'] = sprintf("%.2f",$transport_price);
        $return['tr_pickup_price'] = $tr_pickup_price;
        $return['order_id'] = $order_id;*/

        $return = array(
            'order_id' => $order_id,
            'total_logistics_sum' => $tr_pickup_price,
            'order_price' => sprintf("%.2f",$price),
            'name' => '宠物运输',
            'cover' => C('DEFAULT_YUNSHU_URL'),
            'score' => $score_price['score'],
            'score_use' => $score_number,
            'score_price' => $score_price['price'],
        );
        exit( $this->returnApiSuccess($return) );
    }

}
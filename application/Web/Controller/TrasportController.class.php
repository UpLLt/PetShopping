<?php
namespace Web\Controller;

use Advertisement\Model\BannerModel;
use App\Model\DocumentsModel;
use Common\Model\CommentModel;
use Common\Model\CommonModel;
use Common\Model\OrderModel;
use Consumer\Model\MemberModel;
use Think\Controller;
use Transport\Model\TransportModel;
use Transport\Model\TransportRulesModel;

/**
 * 宠物运输
 * Class IndexController
 * @package Web\Controller
 */

class TrasportController extends BaseController
{
    private $trasport_rules_model ,$trasport_model , $order_model,$documents_model ,$comment_model,$member_model;
    private $banner_model;

    public function __construct()
    {
        parent::__construct();
        $this->trasport_rules_model = new TransportRulesModel();
        $this->order_model = new OrderModel();
        $this->trasport_model = new TransportModel();
        $this->documents_model = new DocumentsModel();
        $this->comment_model = new CommentModel();
        $this->member_model = new MemberModel();
        $this->banner_model = new BannerModel();
    }

    public function index()
    {
        $this->is_login();
        $this->about_company();
        $this->banner_transport();
        $this->comment();
        $this->display();
    }



    public function banner_transport(){
        $join   =  "LEFT JOIN ". C('DB_PREFIX') ."banner_image as b on a.id = b.banner_id";
        $banner =  $this->banner_model
            ->alias('a')
            ->join($join)
            ->order('b.sort_order')
            ->where(array('a.sign_key'=>'company-trans_pc'))
            ->select();

        foreach( $banner as $k => $v ){
            $banner[$k]['image'] = setUrl($v['image']);
            if( $v['type'] == 1 )  $url = U('Product/index',array('ptype'=>'1','pid'=>$v['link']));
            if( $v['type'] == 2 )  $url = U('Product/index',array('ptype'=>'2','pid'=>$v['link']));
            if( $v['type'] == 3 )  $url = U('Web/Community/bannerDis',array('id'=>$v['link'],'banner_id'=>$v['id'],'key'=>$v['title']));
            $banner[$k]['url'] = $url;
        }

        $this->assign('banner_list',$banner);
    }



    /**
     * 用户评论
     */
    public function comment(){
        $comment = $this->comment_model->where(array('order_type'=>3))->order('id desc')->select();

        foreach( $comment as $k => $v ){
            $comment[$k]['create_time'] = date('Y-m-d',$v['create_time']);
            $comment[$k]['heading'] = $this->member_model->getNickNameByHeading($v['mid']);
            if( $v['star'] == 1 ){
                $comment[$k]['star'] = "16px";
            }
            if( $v['star'] == 2 ){
                $comment[$k]['star'] = "34px";
            }
            if( $v['star'] == 3 ){
                $comment[$k]['star'] = "52px";
            }
            if( $v['star'] == 4 ){
                $comment[$k]['star'] = "68px";
            }
            if( $v['star'] == 5 ){
                $comment[$k]['star'] = "85px";
            }
            if( $v['replay'] ) $comment[$k]['replay'] = "<p><span>回复：</span>". $v['replay'] ."</p>";

        }

        $this->assign('comment',$comment);
    }

    /**
     * 公司简介
     */
    public function about_company(){
       $company = $this->documents_model->where('doc_class = "company_transport"')->field('content')->find();
       $this->assign('company',$company['content']);
    }

    public function pet_pet(){
        $join = "LEFT JOIN ".C('DB_PREFIX')."region as b on a.tr_province=b.code";
        $transport_rules = $this->trasport_rules_model
            ->alias('a')
            ->join($join)
            ->field('b.name,b.code')
            ->group('b.name,b.code')
            ->select();

        if(!$transport_rules) $this->error('该模块尚未开放');
        $province = '';
        foreach( $transport_rules as $k => $v ){
            $province .= " <option  value='".$v['code']."'>".$v['name']."</option>";
        }

        $area = $this->getTransportArea($transport_rules['0']['code']);
        $area['province'] = $province;
        $this->airport();
        $this->assign('area',$area);
        $this->display();
    }


    /**
     * 获取运输的地址（点击省）
     * @param null $tr_province
     */
    public function getTransportArea($tr_province = null){
        $trprovince = $tr_province ? $tr_province : I('tr_province');


        $join = "LEFT JOIN ".C('DB_PREFIX')."region as b on a.tr_city=b.code";
        $transport_rules = $this->trasport_rules_model
                                ->alias('a')
                                ->join($join)
                                ->where(['tr_province='.$trprovince])
                                ->field('b.name,b.code')
                                ->group('b.name,b.code')
                                ->select();
            $city = '';
        foreach( $transport_rules as $k => $v ){
            $city .= " <option  value='".$v['code']."'>".$v['name']."</option>";
        }

            $country = $this->getTransportCountry($transport_rules[0]['code']);
            $data['city'] = $city;
            $data['country'] = $country;

            if( I('tr_province') ){
                $this->ajaxReturn($data);
            }else{
                return $data;
            }


    }

    /**
     * 获取运输的地址（点击县）
     * @param null $tr_city
     * @return string
     */
   public function getTransportCountry($tr_city = null){

       $tr_city = $tr_city ? $tr_city : I('tr_city');
       $join = "LEFT JOIN ".C('DB_PREFIX')."region as b on a.tr_country = b.code";
       $transport_rules = $this->trasport_rules_model
           ->alias('a')
           ->join($join)
           ->where(['tr_city='.$tr_city])
           ->field('b.name,b.code')
           ->group('b.name,b.code')
           ->select();

       $country = '';
       foreach( $transport_rules as $k => $v ){
           $country .= " <option  value='".$v['code']."'>".$v['name']."</option>";
       }
       if( I('tr_city') ){
          $this->ajaxReturn($country);
       }else{
           return $country;
       }

   }

    /**
     * 获取机场
     */
    public function airport(){
        $transport = D('transport_airport')->where('keyword="transport"')->find();
        $lists = explode(',',$transport['content']);
        foreach( $lists as $k => $v ){
            $data[$k]['airport'] = $v;
        }

        $this->assign('airport_se',$data);
    }


    /**
     * 获取笼子价格 推荐合适的
     */
    public function cage(){

        if( !IS_POST ) exit($this->returnApiError(BaseController::INVALID_INTERFACE));

        $weight = I('post.weight');
        $country = I('post.country');
        $this->checkparam(array( $weight,$country));

        $trasport_rules = $this->trasport_rules_model->where('tr_country='.$country)->field('tr_cage,tr_pratique,tr_send_addre')->find();

        if(empty($trasport_rules)) exit($this->returnApiError(BaseController::FATAL_ERROR,'该地区尚未设置'));

        $trcage = json_decode($trasport_rules['tr_cage'],true);
        foreach( $trcage as $k => $v){
            if( $weight > $v['start'] && $weight<= $v['end'] ){
                $toweight['price'] = $v['price'];
                $toweight['mark']  = $k;
                $toweight['name'] =  $this->trasport_rules_model->getCagename($k);
                $cage_str = '<b>运输笼：</b><label><input type="radio" name="cage" value="1" class="checkbox mag" checked />'.$this->trasport_rules_model->getCagename($k).' : ￥'.$v['price'].'元</label>
					         <label><input type="radio" name="cage" value="2"  class="checkbox mag" />自带</label>';
            }
        }


        $data['pratique'] =   '<b>检疫证：</b> 	<label><input type="radio" name="tr_pratique" value="1" class="checkbox mag" checked /> 代办 ￥'.$trasport_rules['tr_pratique'].'元</label>
			                    <label><input type="radio" name="tr_pratique" value="2" class="checkbox mag" />自办</label>';
        $data['send_address'] = $trasport_rules['tr_send_addre'];
        $data['cage'] = $cage_str;

        exit($this->returnApiSuccess($data));
    }


    public function protocol_pet_pic(){
        $tr_country = I('tr_country');
        $trasport_rules = $this->trasport_rules_model->where('tr_country='.$tr_country)->field('tr_service')->find();
        $this->assign('tr_service',$trasport_rules['tr_service']);
        $this->display();
    }

    /**
     * 生成订单
     */
    public function transport(){
        $mid         = session('mid');
        $this->is_login();
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

        if( strlen($tr_receive_phone) != 11 ||  strlen($tr_contacts_phone) != 11 ) exit($this->error('电话号码格式不正确'));
        $trasport_rules = $this->trasport_rules_model->where('tr_country='.$country)->find();

        if( $tr_pickup == 2 ) $tr_address = $trasport_rules['tr_send_addre'];

        if( !$trasport_rules ) exit($this->error('该地区尚未设置'));


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
            'cover'=> C('DEFAULT_YUNSHU_URL'),
            'comment_status'=> '0',
            'order_price'=>$price,
            'create_time'=>time(),
            'mid'=>$mid,
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
            $this->error('生成订单失败');
        }

        $return['price'] = sprintf("%.2f",$price);
        $return['transport_price'] = sprintf("%.2f",$transport_price);
        $return['tr_pickup_price'] = $tr_pickup_price;

        $this->success('生成订单成功',U('Web/Order/oneOrderBefore',array('order_id'=>$order_id)));
    }

    public function order_pay_pet(){
        $data = I('get.');
        $this->display();
    }

}
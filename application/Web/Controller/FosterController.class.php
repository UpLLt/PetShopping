<?php
namespace Web\Controller;

use Advertisement\Model\BannerModel;
use App\Model\DocumentsModel;
use Common\Model\CommentModel;
use Common\Model\PetModel;
use Common\Model\OrderModel;
use Common\Model\PetTypeModel;
use Consumer\Model\MemberModel;
use Foster\Model\FosterModel;
use Foster\Model\FosterRulesModel;
use Think\Controller;

/**
 * 宠物寄养
 * Class IndexController
 * @package Web\Controller
 */
class FosterController extends BaseController
{
    private $foster_rules_model;
    private $foster_model;
    private $order_model;
    private $pettype_model;
    private $comment_model;
    private $member_model;
    private $documents_model;
    private $banner_model;

    public function __construct()
    {
        parent::__construct();
        $this->pettype_model = new PetTypeModel();
        $this->foster_rules_model = new FosterRulesModel();
        $this->foster_model = new FosterModel();
        $this->order_model = new OrderModel();
        $this->comment_model = new CommentModel();
        $this->member_model = new MemberModel();
        $this->documents_model = new DocumentsModel();
        $this->banner_model = new BannerModel();
    }


    public function index()
    {
        $this->is_login();
        $this->comment();
        $this->about_company();
        $this->banner_transport();
        $this->display();
    }


    public function banner_transport(){
        $join   =  "LEFT JOIN ". C('DB_PREFIX') ."banner_image as b on a.id = b.banner_id";
        $banner =  $this->banner_model
            ->alias('a')
            ->join($join)
            ->order('b.sort_order')
            ->where(array('a.sign_key'=>'company-fos_pc'))
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
        $comment = $this->comment_model->where(array('order_type'=>5))->order('id desc')->select();

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
        $company = $this->documents_model->where('doc_class = "company_foster"')->field('content')->find();
        $this->assign('company',$company['content']);
    }


    public function foster_pet(){
        $this->is_login();
        $join = "LEFT JOIN " . C('DB_PREFIX') . "region as b on a.fos_province=b.code";
        $foster_rules = $this->foster_rules_model
            ->alias('a')
            ->join($join)
            ->field('b.name,b.code')
            ->group('b.name,b.code')
            ->select();
        if(!$foster_rules) $this->error('该模块尚未开放');
        $province = '';
        foreach ($foster_rules as $k => $v) {
            $province .= " <option  value='" . $v['code'] . "'>" . $v['name'] . "</option>";
        }

        $area = $this->getFosteerArea($foster_rules['0']['code']);
        $area['province'] = $province;
        $area['country']  = $area['service']['country'];

        $this->getPetCategory();
        $this->assign('area',$area);
        $this->display();
    }


    /**
     * 获取品种列表
     */
    public function getPetCategory()
    {

        $pet_type = session('ptype');
        if( !$pet_type ) $pet_type = '2';

        if ($pet_type == PetModel::PET_TYPE_CAT) {
            $result = F('PCPetCategoryCat');
        } else if ($pet_type == PetModel::PET_TYPE_DOG) {
            $result = F('PCPetCategoryDog');
        } else {

        }
        if (!$result) {
            $result = $this->pettype_model
                ->where(['pet_type' => $pet_type])
                ->field('pet_variety_id,pet_variety,pet_letter')
                ->order('pet_letter asc')
                ->select();

            if ($pet_type == PetModel::PET_TYPE_CAT) {
                F('PCPetCategoryCat', $result);
            } else if ($pet_type == PetModel::PET_TYPE_DOG) {
                F('PCPetCategoryDog', $result);
            } else {
            }
        }

        $this->assign( 'PetCategory',$result );


    }


    /**
     * 获取寄养的地址（点击省）
     * @param null $fos_province
     */
    public function getFosteerArea($fos_province = null)
    {
        $fos_province = $fos_province ? $fos_province : I('fos_province');


        $join = "LEFT JOIN " . C('DB_PREFIX') . "region as b on a.fos_city = b.code";
        $foster_rules = $this->foster_rules_model
            ->alias('a')
            ->join($join)
            ->where(['fos_province=' . $fos_province])
            ->field('b.name,b.code')
            ->group('b.name,b.code')
            ->select();
        $city = '';
        foreach ($foster_rules as $k => $v) {
            $city .= " <option  value='" . $v['code'] . "'>" . $v['name'] . "</option>";
        }

        $country = $this->getFosterCountry($foster_rules[0]['code']);
        $data['city'] = $city;
        $data['service'] = $country;

        if( I('fos_province') ){
            $this->ajaxReturn($data);
        }else{
            return $data;
        }
    }

    /**
     * 获取寄养的地址（点击县）
     * @param null $fos_city
     * @return string
     */
    public function getFosterCountry($fos_city = null)
    {

        $fos_city = $fos_city ? $fos_city : I('fos_city');
        $join = "LEFT JOIN " . C('DB_PREFIX') . "region as b on a.fos_country = b.code";
        $foster_rules = $this->foster_rules_model
            ->alias('a')
            ->join($join)
            ->where(['fos_city=' . $fos_city])
            ->field('b.name,b.code')
            ->group('b.name,b.code')
            ->select();

        $country = '';
        foreach ($foster_rules as $k => $v) {
            $country .= " <option  value='" . $v['code'] . "'>" . $v['name'] . "</option>";
        }
        $service = $this->foster_service($foster_rules[0]['code']);
        $data['country'] = $country;
        $data['fos_service'] = $service;

        if( I('fos_city') ){
            $this->ajaxReturn($data);
        }else{
            return $data;
        }

    }

    /**
     * 寄养服务
     * @param $country
     * @return mixed
     */
    public function foster_service($country)
    {

        $country = $country ? $country : I('country');
        $foster_rules = $this->foster_rules_model->where('fos_country=' . $country)->find();

        if (empty($foster_rules)) exit($this->returnApiError(BaseController::FATAL_ERROR, '该地区尚未设置'));

        $fos_weight = json_decode($foster_rules['fos_weight'], true);

        $service = '';

        foreach ($fos_weight as $k => $v) {
            $service .= '<label><input type="radio" name="fo_service" id="fo_service" value="' . $k . '" class="checkbox mag" />' . $v['start'] . 'kg - ' . $v['end'] . 'kg' . ' &nbsp;&nbsp; ' . $v['price'] . '元/天 </label> ';
        }
        $data['send_address'] = $foster_rules['fos_send_addre'];
        $data['weight'] = $service;

        if( I('country') ){
            $this->ajaxReturn($data);
        }else{
            return $data;
        }

    }

    public function protocol_pet_pic(){
        $this->is_login();
        $fos_country = I('fos_country');
        $trasport_rules = $this->foster_rules_model->where('fos_country='.$fos_country)->field('fos_service')->find();
        $this->assign('fos_service',$trasport_rules['fos_service']);
        $this->display();
    }


    /**
     * 生成订单
     */
    public function foster()
    {
        $this->check_login();
        $mid = session('mid');
        $ptype = I('post.ptype');
        $fo_age = I('post.fo_age');
        $vaccine_status = I('post.vaccine_status'); //是否疫苗

        $vaccine_time = I('post.vaccine_time');//疫苗时间
        $fo_service = I('post.fo_service'); //传 ID
        $time_start = I('post.time_start');
        $time_end = I('post.time_end');
        $fo_dog_food = I('post.fo_dog_food'); //    1/2  需要/不需要
        $fo_contacts = I('post.fo_contacts');
        $fo_contacts_phone = I('post.fo_contacts_phone'); //
        $fo_pickup = I('post.fo_pickup'); //    1/2  门店自取/客户送货上门
        $fo_address = I('post.fo_address');
        $fo_country = I('post.fos_country');

        $vaccine_content = I('post.vaccine_content_start').'针'.I('post.vaccine_content_end').'联';
        if ( !IS_POST ) exit($this->returnApiError(BaseController::INVALID_INTERFACE));
        $this->checktoken($mid, $ptype, $fo_age, $vaccine_status, $fo_service, $time_start, $time_end, $fo_dog_food, $fo_contacts, $fo_contacts_phone, $fo_pickup, $fo_address, $fo_country);

        if (strlen($fo_contacts_phone) != 11) exit($this->returnApiError(BaseController::FATAL_ERROR, '电话号码格式不正确'));
        $foster_rules = $this->foster_rules_model->where('fos_country = ' . $fo_country)->find();
        if( $fo_pickup == 2 ) $tr_address = $foster_rules['fos_send_addre'];
        if (!$foster_rules) exit($this->returnApiError(BaseController::FATAL_ERROR, '该地区尚未设置'));

        //宠物总量价格选择
        $foster_rules['fos_weight'] = json_decode($foster_rules['fos_weight'], true);
        foreach ($foster_rules['fos_weight'] as $k => $v) {
            if ($fo_service == $k) {
                $now['service_price'] = $v['price'];
                $now['service'] = $v['start'] . 'kg - ' . $v['end'] . 'kg';
            }
        }

        //宠物时间计算
        $startdate = strtotime($time_start);
        $enddate = strtotime($time_end);
        $days = round(($enddate - $startdate) / 3600 / 24);

        //判断折扣
        $foster_rules['fos_discount'] = json_decode($foster_rules['fos_discount'], true);


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
        $now['pick_up'] = $fo_pickup == 1 ? $foster_rules['fos_price'] : '0';
        $address = $fo_pickup == 1 ? $fo_address : $foster_rules['fos_send_addre'];


        //狗粮价格
        $now['fo_dog_food'] = $fo_dog_food == 2 ? $foster_rules['fos_dog_food'] * $days : '0';

        //寄养费用
        $fo_foster_price = ($days * $now['service_price']) * $now['num'];

        $price = $fo_foster_price + $now['pick_up'] + $now['fo_dog_food'];

        $this->foster_model->startTrans();
        $is_commit = true;

        $order = array(
            'order_sn' => $this->order_model->getOrderNumber(),
            'order_type' => OrderModel::ORDER_TYPE_FOSTER,
            'status' => OrderModel::STATUS_WAIT_FOR_PAY,
            'comment_status'=> '0',
            'cover'=> C('DEFAULT_JIYANG_URL'),
            'order_price' => $price,
            'create_time' => time(),
            'mid' => $mid,
        );

        $order_id = $this->order_model->add($order);
        if (!$order_id) {
            $is_commit = false;
        }

        //宠物疫苗情况
        if ($vaccine_status == 1) {
            $vaccine['status'] = $vaccine_status;
            $vaccine['content'] = $vaccine_content;
            $vaccine['time'] = $vaccine_time;
        } else {
            $vaccine['status'] = $vaccine_status;
        }
        $vaccine = json_encode($vaccine);


        $data = array(
            'order_id' => $order_id,
            'ptype' => $ptype,
            'fo_age' => $fo_age,
            'fo_vaccine' => $vaccine,
            'fo_service' => $now['service'],
            'fo_faster_time' => $time_start . '至' . $time_end,
            'fo_dog_food' => $fo_dog_food,
            'fo_contacts' => $fo_contacts,
            'fo_contacts_phone' => $fo_contacts_phone,
            'fo_pickup' => $fo_pickup,
            'fo_area' => $fo_country,
            'fo_address' => $address,
            'fo_foster_price' => $fo_foster_price,
            'fo_pickup_price' => $now['pick_up'],
            'fo_price' => $price,

        );
        $result = $this->foster_model->add($data);
        if (!$result) {
            $is_commit = false;
        }

        if ($is_commit) {
            $this->foster_model->commit();
        } else {
            $this->foster_model->rollback();
            exit($this->returnApiError(BaseController::FATAL_ERROR, '生成订单失败'));
        }

        $return['price'] = sprintf("%.2f", $price);
        $return['foster_price'] = sprintf("%.2f", $fo_foster_price);
        $return['pickup_price'] = $now['pick_up'];

        $this->success('生成订单成功',U('Web/Order/oneOrderBefore',array('order_id'=>$order_id)));
    }


}
<?php
namespace Web\Controller;

use Common\Model\MarriageModel;
use Common\Model\OrderModel;
use Common\Model\PetModel;
use Common\Model\PetTypeModel;
use Common\Model\RegionModel;
use Community\Model\ComScoreModel;
use Marriage\Model\WeddingRulesModel;
use Purchase\Model\SellRulesModel;
use Think\Controller;

/**
 * 宠物婚介
 * Class HospitalController
 * @package Web\Controller
 */
class MarriageController extends BaseController
{
    private $pet_model;
    private $wedding_model;
    private $pet_type_model;
    private $order_model;
    private $marriage_model;
    private $com_score_model;
    private $sell_rulesmodel;
    private $region_model;


    public function __construct()
    {
        parent::__construct();
        $this->pet_model = new PetModel();
        $this->wedding_model = new WeddingRulesModel();
        $this->pet_type_model = new PetTypeModel();
        $this->order_model = new OrderModel();
        $this->marriage_model = new MarriageModel();
        $this->com_score_model = new ComScoreModel();
        $this->sell_rulesmodel = new SellRulesModel();
        $this->region_model = new RegionModel();

    }

    public function index()
    {
        $pet_type = $this->pet_type_model->getPetLetter();
        $this->lists();
        $this->assign('pet_type',$pet_type);
        $this->display();
    }


    /**
     * @return mixed
     * 获取省份的地址
     */
    public function getArea(){
        $province = $this->region_model->getProvince();

        $city = '';
        foreach( $province as $k => $v ){
            $city .= " <option  value='".$v['code']."'>".$v['name']."</option>";
        }

        if( I('ma_province') ){
            $country = $this->getTransportCountry( I('ma_province') );
        }else{
            $country = $this->getTransportCountry($province[0]['code']);
        }

        $data['city'] = $city;
        $data['country'] = $country;

        if( I('ma_province') ){
            $this->ajaxReturn($country);
        }else{
            $this->assign('area',$data);
        }


    }

    /**
     * 获取运输的地址（点击县）
     * @param null $tr_city
     * @return string
     */
    public function getTransportCountry($tr_city = null){
        $province = $this->region_model->getCitytoStr($tr_city);
        return $province;
    }


    /**
     * 婚介首页列表
     */
    public function lists() {

        $postdata['pet_type'] = session('ptype') ? session('ptype') : 2;

        if( $postdata['pe_type'] ) {
            $where['pe_type'] = $postdata['pe_type'];
        }
        $order = 'pe_breeding desc';
        $where['pet_type'] = $postdata['pet_type'];//1/猫 2/狗
        $where['pe_status'] = 2;
        $where['pe_state'] = 1;
        $count = $this->pet_model
            ->join('ego_pet_type on ego_pet.pe_type = ego_pet_type.pet_variety_id')
            ->where($where)
            ->count();

        $page = $this->page($count,12);
        $list = $this->pet_model
            ->join('ego_pet_type on ego_pet.pe_type = ego_pet_type.pet_variety_id')
            ->where($where)
            ->limit( $page->firstRow .' , ' .$page->listRows )
            ->order($order)
            ->field('pet_variety, pe_name, pe_age, pe_picture, pe_breeding, pid')
            ->select();

        $data = array();

        foreach($list as $k => $v) {
            $imgurl = json_decode($v['pe_picture'], true);
            $url = setUrl($imgurl['0']);

            $str = '<li>
                        <a href="'.U('Web/Marriage/macth_list',array('id'=>$v['pid'])).'">
						<img style="width:181px;height:164px" src="'. $url .'" />
						<h1>'. $v['pe_name'] .'</h1>
						<p>￥'.$v['pe_breeding'] .'</p>
					    </a>
					 </li>';

            $data[$k]= $str;
        }

        $this->assign('lists',$data);
        $this->assign('Page',$page->show('Admin'));
    }

    /**
     * 获取婚介首页已通过並且沒有配种的列表
     */
    public function ajaxGetPet() {

        $postdata['pet_type'] = session('ptype') ? session('ptype') : 2;

        $postdata['price']    = I('post.price');
        $postdata['pe_type']  = I('post.pe_type');



        //页码
        $page = isset($postdata['page']) && intval($postdata['page']) > 1 ? $postdata['page'] : '1';
        //每页显示数量

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

        $star = ($page - 1) * 12;
        $Allpage = ceil($count /12);
        $list = $this->pet_model
            ->join('ego_pet_type on ego_pet.pe_type = ego_pet_type.pet_variety_id')
            ->where($where)
            ->order($order)
            ->page($star, 12)
            ->field('pet_variety, pe_name, pe_age, pe_picture, pe_breeding, pid')
            ->select();

        $pro = $page - 1;
        if ($pro == 0) $pro = 1;
        $next = $page + 1;
        if ($next > $Allpage) $next = $page;

        $str1 = '<a href="javascript:void(0)"  onclick="page(this)" name="1" >首页</a><a href="javascript:void(0)"  onclick="page(this)" name="' . $pro . '">上一页</a>';

        //大于页数省略
        $str2 = '';
        if( $Allpage > 15 ) {
            $str2 .= '<a  href="javascript:void(0);"   >...</a>';
            $page_end =  $page + 5;
            $page_start   =  $page - 5;
        }
        //页数判定
        for( $i=1 ; $i<=$Allpage ;$i++ ){
            if( $Allpage > 15 ){
                if( $page == $i ){
                    $str2 .= '<span class="current" href="javascript:void(0);" onclick="page(this)"  name="' . $i . '" >' . $i . '</span>';
                }else if( $i >= $page_start && $i <= $page_end){
                    $str2 .= '<a  href="javascript:void(0);" onclick="page(this)"  name="' . $i . '" >' . $i . '</a>';
                }
            }else{
                if( $page == $i ){
                    $str2 .= '<span class="current" href="javascript:void(0);" onclick="page(this)"  name="' . $i . '" >' . $i . '</span>';
                }else{
                    $str2 .= '<a  href="javascript:void(0);" onclick="page(this)"  name="' . $i . '" >' . $i . '</a>';
                }
            }
        }
        if( $Allpage > 20 ) {
            $str2 .= '<a  href="javascript:void(0);"   >...</a>';
        }
        $str3 = '<a href="javascript:void(0)"  onclick="page(this)" name="' . $next . '">下一页</a>';
        $page = $str1.$str2.$str3;

        $str = '';
        foreach($list as $k => $v) {
            $imgurl = json_decode($v['pe_picture'], true);
            $url = setUrl($imgurl['0']);
            $str .= '<li>
                        <a href="'.U('Web/Marriage/macth_list',array('id'=>$v['pid'])).'">
						<img style="width:181px;height:164px" src="'. $url .'" />
						<h1>'. $v['pe_name'] .'</h1>
						<p>￥'.$v['pe_breeding'] .'</p>
					    </a>
					 </li>';


        }
        if( $Allpage == 1 || $Allpage == 0 ) $page = '';
        $data['str'] = $str;
        $data['count'] = $count;
        $data['Page']  = $page;


        $data['order_type'] = $postdata['price']== 1 ? 2 : 1;
        $this->ajaxReturn($data);

    }


    public function macth_list(){
        $id = I('id');
        $list = $this->pet_model
            ->join('ego_pet_type on ego_pet.pe_type = ego_pet_type.pet_variety_id')
            ->where('pid='.$id)
            ->field('pet_variety, pe_name, pe_age, pe_picture, pe_breeding, pid , pe_type ,pe_area')
            ->find();

        $list['pe_picture'] = json_decode($list['pe_picture'],true);

        foreach($list['pe_picture'] as $k => $v ){
            $list['pe_picture'][$k] = setUrl($v);
        }
        $wedding  = $this->wedding_model->find();
        $list['we_ovulation'] = $wedding['we_ovulation'];
        $list['we_sperm']     = $wedding['we_sperm'];

        $where['pet_type'] = session('ptype') ? session('ptype') : 2;;//1/猫 2/狗
        $where['pe_status'] = 2;
        $where['pe_state'] = 1;
        $where['pe_type'] = $list['pe_type'];
        $other_pet  = $this->pet_model->where( $where )->limit(8)->select();

        $str = '';
        foreach($other_pet as $k => $v) {
            $imgurl = json_decode($v['pe_picture'], true);
            $url = setUrl($imgurl['0']);

            $str .= '<li>
                        <a href="'.U('Web/Marriage/macth_list',array('id'=>$v['pid'])).'">
						<img style="width:181px;height:164px" src="'. $url .'" />
						<h1>'. $v['pe_name'] .'</h1>
						<p>￥'.$v['pe_breeding'] .'</p>
					    </a>
					 </li>';


        }
        $this->service_agreement();
        $this->assign('other_pet',$str);
        $this->assign( 'lists',$list );
        $this->display();

    }

    public function service_agreement(){
        $info = $this->sell_rulesmodel->where("type = 2")->getField('se_service');
        $this->assign('service_agree',$info);
    }

    /**
     * 生成订单
     */
    public function order() {
        $postdata = get_data(1);
        $mid = session('mid');
        $this->is_login();
        $wedding  = $this->wedding_model->find();
        $list = $this->pet_model->where('pid='.$postdata['pid'])->find();
        $postdata['we_sperm'] = $wedding['we_sperm'];
        $postdata['we_ovulation'] = $wedding['we_ovulation'];
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

        $total = $we_sperm + $we_ovulation + $list['pe_breeding'] ;

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
            'ma_breeding_price' => $list['pe_breeding'],
            'ma_ovulation' => $we_ovulation,
            'ma_sperm' => $we_sperm,
            'ma_sprice' => $total,
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
        $score_number = $this->com_score_model->scoExchange($mid, $total, true);
        $score_price = $this->com_score_model->scoExchange($mid, $total);
        $return = array(
            'order_id' => $rst,
            'order_price' => $total,
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
        $this->success('生成订单成功',U('Web/Order/oneOrderBefore',array('order_id'=>$rst)));
    }



    /**
     * 发布的宠物
     */
    public function macth_pet(){

        $this->ownList();
        $this->display();
    }

    /**
     * 自己发布的婚介信息
     * status 1、待审核，2、审核通过，3、审核拒绝
     */
    public function ownList() {

        $mid = session('mid');

        $this->is_login();

        $where['pe_member_id'] = $mid;
        $where['pe_state']  = 1;


        $list = $this->pet_model
            ->join('ego_pet_type on ego_pet.pe_type = ego_pet_type.pet_variety_id')
            ->where($where)
            ->field('pet_variety, pe_name, pe_age, pe_picture, pe_breeding, pid ,pe_status')
            ->select();

        foreach($list as $k => $v) {
            $imgurl = json_decode($v['pe_picture'], true);
            $url = setUrl($imgurl['0']);

            $str = '	<li>
					        <span><img src="'. $url .'" /><b>' .$v['pe_name']  . '</b></span>
					        <span>￥'. $v['pe_breeding'] .'</span>
					        <span>'.$this->pet_model->getCheck($v['pe_status']).'</span>
				        </li>';

            if( $v['pe_status'] == PetModel:: CHECK_1 ){
                $data['wait']   .=  $str;
            }
            if( $v['pe_status'] == PetModel:: CHECK_2 ){
                $data['pass']   .=  $str;
            }
            if( $v['pe_status'] == PetModel:: CHECK_3 ){
                $data['refuse'] .=  $str;
            }
        }
       $this->assign('lists',$data);

    }


    /**
     * 发布新的宠物
     */
    public function macth_news_pet(){
        $this->getArea();
        $this->getPetCategory();
        $this->display();
    }

    /**
     * 获取品种列表
     */
    public function getPetCategory()
    {
        $type   = session('ptype') ? session('ptype') : 2;
            $result = $this->pet_type_model
                ->where(['pet_type' => $type])
                ->field('pet_variety_id,pet_variety,pet_letter')
                ->order('pet_letter asc')
                ->select();
        $this->assign( 'PetCategory',$result );

    }

    /**
     *婚介发布
     */
    public function publish()
    {

        if( count($_FILES['fileselect']['name']) != 5 ){
            $this->error('请上传五张图片！');
        }
        $postdata = get_data(1);
        $mid = session('mid');


        $this->checkparam(array( $postdata['pe_name'], $postdata['pet_variety_id'], $postdata['pe_age'], $postdata['pe_breeding'], $postdata['pe_phone'] ));

        if(strlen($postdata['pe_phone']) != 11) {
            $this->error('手机号格式错误');
        }
        $imagurl = upload_img('Marriage');
        if(!$imagurl) {
            $this->error('图片上传失败,图片尺寸过大！');
        }
        $pe_province  = $this->region_model->getNamForCode($postdata['pe_province']);
        $pe_city  = $this->region_model->getNamForCode($postdata['pe_city']);
        $data = array(
            'pe_type' => $postdata['pet_variety_id'],
            'pe_name' => $postdata['pe_name'],
            'pe_age' => $postdata['pe_age'],
            'pe_picture' => json_encode($imagurl),
            'pe_breeding' => $postdata['pe_breeding'],
            'pe_phone' => $postdata['pe_phone'],
            'pe_status' => 1,
            'create_time' => time(),
            'pe_member_id' => $mid,
            'pe_area' =>  $pe_province .'/'. $pe_cityyyyyyy
        );
        $rst = $this->pet_model->add($data);
        if( $rst) {
           $this->success('发布成功');
        } else {
            $this->error('发布失败');
        }
    }


}
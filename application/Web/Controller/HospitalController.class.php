<?php
namespace Web\Controller;

use Common\Model\CommentModel;
use Common\Model\OrderModel;
use Merchant\Model\HospitalShopModel;
use Think\Controller;

/**
 * 宠物医疗
 * Class HospitalController
 * @package Web\Controller
 */
class HospitalController extends BaseController
{
    private $hospital_model, $comment_model;

    public function __construct()
    {
        parent::__construct();
        $this->hospital_model = new HospitalShopModel();
        $this->comment_model = new CommentModel();
    }
    public function index()
    {
        $bu_city = '510100';
        if(IS_POST) {
            $bu_city = I('get.code');
        }
        $citys = $this->hospital_model
            ->alias('a')
            ->join('LEFT JOIN ego_region as b on a.bu_city = b.code')
            ->field('a.bu_city, b.name')
            ->group('a.bu_city, b.name')
            ->where(array('shop_status' => 2))
            ->select();
        $hospitals = $this->hospital_model
            ->alias('a')
            ->join('LEFT JOIN ego_region as b on a.bu_province = b.code')
            ->join('LEFT JOIN ego_region as c on a.bu_city = c.code')
            ->join('LEFT JOIN ego_region as d on a.bu_country = d.code')
            ->field('a.id, a.hos_name, a.hos_longitude, a.hos_latitude, a.hos_address ,b.name as province_name, c.name as city_name, d.name as country_name')
            ->where(array('bu_city' => $bu_city,'shop_status' => 2))
            ->select();
        foreach($hospitals as $k => $v) {
            $count = $this->comment_model->where(array('order_type' => OrderModel::ORDER_TYPE_HOSPITAL, 'relevance_id' => $v['id']))->count();
            $address = $v['province_name'].$v['city_name'].$v['country_name'].$v['hos_address'];
            $lists  .= '<li>
					<div class="pet_med_li"><span class="locat_med_1"></span></div>
					<div class="pet_med_show">
						<h1><a href="' . U('Web/Hospital/pet_medical_show', array('hid' => $v['id'])) . '">'.$v['hos_name'].'</a></h1>
						<p>'.$address.'</p>
						<h4>评论：<span>'.$count.'</span>  <b><a href="' . U('Web/Hospital/pet_medical_show', array('hid' => $v['id'])) . '">查看详情>></a></b></h4>
					</div>';

        }

//        $this->assign('lists', $lists);
        $this->assign('citys', $citys);
        $this->display();
    }

    public function getPlace() {
//        $bu_city = '510100';//默认成都
        //取数据库第一个
        $firsthospital = $hospitals = $this->hospital_model->where(array('shop_status' => 2))->getField('bu_city');
        if(IS_POST) {
            $firsthospital = empty(I('post.code')) ? $firsthospital : I('post.code');//dump($bu_city);
        }
//        dump(I('post.code'));
        $hospitals = $this->hospital_model
            ->alias('a')
            ->join('LEFT JOIN ego_region as b on a.bu_province = b.code')
            ->join('LEFT JOIN ego_region as c on a.bu_city = c.code')
            ->join('LEFT JOIN ego_region as d on a.bu_country = d.code')
            ->field('a.id, a.hos_name,a.hos_contacts_phone, a.hos_longitude, a.hos_latitude, a.hos_address ,b.name as province_name, c.name as city_name, d.name as country_name')
            ->where(array('bu_city' => $firsthospital, 'shop_status' => 2))
            ->select();
        $lists = '';
        foreach($hospitals as $k => $v) {

            $count = $this->comment_model->where(array('order_type' => OrderModel::ORDER_TYPE_HOSPITAL, 'relevance_id' => $v['id']))->count();//评论数
            //地址
            $address = $v['province_name'].$v['city_name'].$v['country_name'].$v['hos_address'];
            //左边列表
            $lists  .= '<li>
					<div class="pet_med_li"><span class="locat_med_1"></span></div>
					<div class="pet_med_show">
						<h1><a href="' . U('Web/Hospital/pet_medical_show', array('hid' => $v['id'])) . '">'.$v['hos_name'].'</a></h1>
						<p>'.$address.'</p>
						<h4>评论：<span>'.$count.'</span>  <b><a href="' . U('Web/Hospital/pet_medical_show', array('hid' => $v['id'])) . '">查看详情>></a></b></h4>
					</div>';
            //地图打点
            $places[]  = array(
                'title' => $v['hos_name'],
                'point' => $v['hos_longitude'].','.$v['hos_latitude'],
                'address' => $v['province_name'].$v['city_name'].$v['country_name'].$v['hos_address'],
                'tel' => $v['hos_contacts_phone'],
            );
//        [
//        { title: "名称：成都一环宠物医院", point: "104.06906,30.657582", address: "成都一环宠物医院（专治各种不育不孕）", tel: "18600256742" },

        }
//        $this->ajaxReturn($bu_city);
        $return  = array(
            'code' => 200,
            'places' => $places,
            'location' => $hospitals[0]['city_name'],
            'list' => $lists
        );
        echo json_encode($return);exit;
//        dump($places[0]['hos_longitude']);
    }

    /**
     * 宠物医疗商店展示
     */
    public function pet_medical_show(){
        $hid =I('get.hid');
        $detail = $this->hospital_model
            ->alias('a')
            ->join('LEFT JOIN ego_region as b on a.bu_province = b.code')
            ->join('LEFT JOIN ego_region as c on a.bu_city = c.code')
            ->join('LEFT JOIN ego_region as d on a.bu_country = d.code')
            ->field('a.id,a.hos_image, a.hos_name,a.hos_contacts_phone, a.hos_longitude, a.hos_latitude, a.hos_address ,b.name as province_name, c.name as city_name, d.name as country_name')
            ->where(array('a.id' => $hid))
            ->find();
        $area = $detail['hos_address'];
        $address = $detail['province_name'].$detail['city_name'].$detail['country_name'].$detail['hos_address'];
        $json = json_decode($detail['hos_image'], true);
        foreach ($json as $k => $v) {
            $images[] = setUrl($v['url']);
        }


        //评论列表
        $count = $this->comment_model
//            ->alias('a')
//            ->join('LEFT JOIN ego_member as b on a.mid = b.id')
            ->where(array('order_type' => OrderModel::ORDER_TYPE_HOSPITAL, 'relevance_id' => $hid))
            ->count();//评论数
        $page = $this->page($count, C("PAGE_NUMBER"));
//        $page = $this->page($count, 1);
        $lists = $this->comment_model
            ->alias('a')
            ->join('LEFT JOIN ego_member as b on a.mid = b.id')
            ->join('LEFT JOIN ego_com_score as c on a.mid = c.sco_member_id')
            ->where(array('order_type' => OrderModel::ORDER_TYPE_HOSPITAL, 'relevance_id' => $hid))
            ->field('a.star,a.create_time, a.content, a.star, a.replay,b.nickname, b.headimg, c.sco_level')
            ->order('create_time desc')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($lists as $k => $v) {
            $comments[] = array(
                'nickname' => $v['nickname'],
                'headimg' => setUrl($v['headimg']),
                'create_time' => date('Y-m-d', $v['create_time']),
                'content' => $v['content'],
                'replay' => $v['replay'] ? $v['replay'] : '待回复',
                'sco_level' => 'red'.$v['sco_level'],
                'star' => $v['star'],
            );
        }
//        dump($comments);
        $this->assign('images', $images);
        $this->assign('area', $area);
        $this->assign('address', $address);
        $this->assign('count', $count);
        $this->assign('comments', $comments);
        $this->assign("Page", $page->show('Admin'));
        $this->display();
    }
}
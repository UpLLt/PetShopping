<?php
namespace Web\Controller;

use App\Model\DocumentsModel;
use Common\Model\RegionModel;
use Merchant\Model\HospitalShopModel;
use Merchant\Model\ShopTypeModel;
use Think\Controller;

/**
 * 投诉建议界面
 * Class IndexController
 * @package Web\Controller
 */

class ComplaintController extends BaseController
{
    private $region_model, $shop_typemodel, $hos_shopmodel;
    private $document_model;
    public function __construct()
    {
        parent::__construct();
        $this->region_model = new RegionModel();
        $this->shop_typemodel = new ShopTypeModel();
        $this->hos_shopmodel = new HospitalShopModel();
        $this->document_model = new DocumentsModel();
    }

    public function index()
    {
        $this->display();
    }

    /**
     * 商家入驻
     */
    public function merchant(){

        $shoptype = $this->shop_typemodel->select();
        $province = $this->region_model->getProvincetoStr();
        $provinceCode = $this->region_model->getProvince();
        $city = $this->region_model->getCitytoStr($provinceCode[0]['code']);
        $city_code = $this->region_model->getCity($provinceCode[0]['code']);
        $area = $this->region_model->getCitytoStr($city_code['0']['code']);

        if(IS_POST) {

            $postdata = get_data(1);

            if(empty($postdata['hos_name'])) {
                $this->error('请输入店铺名称');
            }
            if(empty($postdata['bu_country'])) {
                $this->error('请选择所属地区');
            }
            if(empty($postdata['hos_address'])) {
                $this->error('请输入详细地址');
            }
            if(empty($postdata['hos_registered_capital'])) {
                $this->error('请输入注册资本');
            }
            if(empty($postdata['hos_contacts'])) {
                $this->error('请输入联系人');
            }
            if(empty($postdata['hos_contacts_phone'])) {
                $this->error('请输入联系电话');
            }
            if(empty($postdata['hos_commany_size'])) {
                $this->error('请输入公司规模');
            }
            if(empty($postdata['hos_describe'])) {
                $this->error('请输入公司简介');
            }
            if(empty($postdata['hos_contacts_phone'])) {
                $this->error('请选择入驻类型');
            }
            if(empty($postdata['hos_contacts_phone']) || strlen($postdata['hos_contacts_phone']) != 11) {
                $this->error('请填写正确的手机号码');
            }
            if(empty($postdata['hos_contacts_phone'])) {
                $this->error('请选择入驻类型');
            }
            if(count($_FILES) < 3) {
                $this->error('请完善图片信息');
            }
            $telphone = $this->hos_shopmodel
                ->where(array('hos_contacts_phone' => $postdata['hos_contacts_phone'], 'shop_status' =>array('in', array(1,2,4))))
                ->find();
            if($telphone) {
                $this->error('该手机号已经绑定了店铺，请重新填写');
            }
            $images = upload_img('Store');
            if(empty($images)) {
                $this->error('图片上传失败，请重试');
            }
            $postdata['hos_business_license'] = $images[0];
            $postdata['hos_idcard'] = json_encode(array_slice($images, 1, 2));
            $postdata['shop_type'] = 2;
            $postdata['time'] = time();
            $rst = $this->hos_shopmodel->add($postdata);
            if($rst) {
                $this->success('申请成功,请等待审核');exit;
            } else {
                $this->error('申请失败，请重试');exit;
            }

        }

        $this->assign('province',$province);
        $this->assign('city',$city);
        $this->assign('area',$area);
        $this->assign('shoptype', $shoptype);
        $this->display();
    }

    /*public function getCity(){
        $city = $this->region_model->getCitytoStr(I('code'));
        $this->ajaxReturn($city);

    }*/
    public function getcity(){
        $province = I('post.province');
        $city     = $this->region_model->getCitytoStr( $province );
        $citycode = $this->region_model->getCity($province);
        $country  = $this->region_model->getCitytoStr( $citycode[0]['code'] );
        $data['city']    = $city;
        $data['country'] = $country;

        $this->ajaxReturn($data);

    }

    public function getcountry(){
        $city = I('post.city');
        $country  = $this->region_model->getCitytoStr( $city );
        $this->ajaxReturn($country);
    }

    public function about(){
        $about = $this->document_model->where('doc_class = "about"')->find();
        $this->assign( 'lists',$about );
        $this->display();
    }

    public function connect(){
        $about = $this->document_model->where('doc_class = "connect"')->find();
        $this->assign( 'lists',$about );
        $this->display();
    }

    public function pay(){
        $about = $this->document_model->where('doc_class = "pay"')->find();
        $this->assign( 'lists',$about );
        $this->display();
    }
    public function shopping(){
        $about = $this->document_model->where('doc_class = "shopping"')->find();
        $this->assign( 'lists',$about );
        $this->display();
    }
}
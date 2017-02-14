<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/20
 * Time: 15:38
 */

namespace Appapi\Controller;
use Common\Model\AddressModel;
use Common\Model\RegionModel;


/**
 * 管理收货地址
 * Class AddressController
 * @package Appapi\Controller
 */
class AddressController extends ApibaseController
{
    private $address_model , $region_model;

    public function __construct()
    {
        parent::__construct();
        $this->address_model = new AddressModel();
        $this->region_model  = new RegionModel();
    }


    /**
     * 地址列表
     */
    public function addressList()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $result = $this->address_model
            ->where(array('mid' => $mid))
            ->order('id desc')
            ->select();

        foreach( $result as $k => $v ){
            $province = $this->region_model->where('code='.$v['province'])->field('name')->find();
            $city = $this->region_model->where('code='.$v['city'])->field('name')->find();
            $country = $this->region_model->where('code='.$v['country'])->field('name')->find();
            $result[$k]['address'] = $province['name'].'/'.$city['name'].'/'.$country['name'].'/'.$v['address'];
        }
        exit($this->returnApiSuccess($result));
    }


    /**
     * 增加地址
     */
    public function addressAdd()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid      = I('post.mid');
        $token    = I('post.token');
        $fullname = I('post.fullname');
        $shopping_telephone = I('post.phone');
        $address  = I('post.address');
        $province = I('post.province');
        $city     = I('post.city');
        $country  = I('post.country');
        $status   = I('post.status');

        $this->checkparam(array($mid, $token, $fullname, $shopping_telephone, $address,$status,$province,$city,$country));
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }
        $data = array(
            'mid' => $mid,
            'fullname' => $fullname,
            'phone' => $shopping_telephone,
            'address' => $address,
            'province' => $province,
            'city' => $city,
            'country' => $country,
            'status' => $status,
        );

        $result = $this->address_model->add($data);
        if ($result){
            if( $status == AddressModel::ADDRESS_DEFAULT ){
                 $this->address_model->where('mid='.$mid )->save(array('status' => AddressModel::ADDRESS_NOT_DEFAULT));
                 $this->address_model->where('id='.$result)->save(array('status' => AddressModel::ADDRESS_DEFAULT));
            }

            exit($this->returnApiSuccess());
        }
        else exit($this->returnApiError(ApibaseController::FATAL_ERROR));
    }

    /**
     * 修改地址
     */
    public function addressEdit()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $addressid = I('post.id');
        $token = I('post.token');
        $fullname = I('post.fullname');
        $shopping_telephone = I('post.phone');
        $address = I('post.address');
        $province= I('post.province');
        $city    = I('post.city');
        $country = I('post.country');
        $status  = I('post.status');

        $this->checkparam(array($mid, $token, $fullname, $shopping_telephone, $address, $addressid,$province,$city,$country));
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $data = array(
            'fullname' => $fullname,
            'phone' => $shopping_telephone,
            'address' => $address,
            'status' => $status,
            'province'=>$province,
            'city'=>$city,
            'country' =>$country

        );

        $result = $this->address_model
            ->where(array('id' => $addressid))
            ->save($data);

        if ($result === false) exit($this->returnApiError(ApibaseController::FATAL_ERROR,'修改失败'));
        else
            if( $status == AddressModel::ADDRESS_DEFAULT ){
                $this->address_model->where( 'mid='.$mid )->save( array('status' => AddressModel::ADDRESS_NOT_DEFAULT) );
                $this->address_model->where( 'id='.$addressid)->save( array('status' => AddressModel::ADDRESS_DEFAULT) );
            }
        exit($this->returnApiSuccess());
    }

    /**
     * 修改默认值
     */
    public function edit_default(){
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $addressid = I('post.id');
        $token = I('post.token');

        $this->checkparam(array($mid, $token , $addressid));
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $result = $this->address_model
            ->where(array('id' => $addressid))
            ->find();

        if( $result['status'] == AddressModel::ADDRESS_NOT_DEFAULT ){
            $this->address_model->where('mid='.$mid )->save(array('status' => AddressModel::ADDRESS_NOT_DEFAULT));
            $this->address_model->where('id='.$addressid)->save(array('status' => AddressModel::ADDRESS_DEFAULT));
            exit($this->returnApiSuccess());
        }
            exit($this->returnApiError(ApibaseController::FATAL_ERROR,'该地址为默认地址'));

    }

    /**
     * 删除地址
     */
    public function addressDelete()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $token = I('post.token');
        $addressid = I('post.id');
        $this->checkparam(array($mid, $token, $addressid));
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $result = $this->address_model->delete($addressid);
        if( $result ) {
            exit($this->returnApiSuccess());

        }else{
            exit($this->returnApiError(ApibaseController::FATAL_ERROR,'删除失败'));
        }

    }

}
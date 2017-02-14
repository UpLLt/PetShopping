<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/20
 * Time: 15:38
 */

namespace Common\Model;
/**
 * 城市查询Model
 * Class RegionModel
 * @package Common\Model
 */

class RegionModel extends CommonModel
{


    /**
     * 获取城市名称
     * @param $code
     * @return mixed
     */
    public function getNamForCode($code){
        return $this->where('code = '.$code )->getField('name');
    }

    /**
     * 获取省数据
     * @return mixed
     */
    public function getProvince(){
        $where['code'] = array('like','%0000');
        return $this->where($where)->field('name,code,parentcode')->select();
    }

    /**
     * 获取省数据返回str
     * @param null $province_code
     * @return string
     */
    public function getProvincetoStr( $province_code = null){
        $where['code'] = array('like','%0000');
        $region =  $this->where($where)->field('name,code,parentcode')->select();
        $province = "";
        foreach( $region as $k => $v ) {

            $select    = $province_code == $v['code'] ? 'selected="selected"' : '';
            $province .= " <option ".$select." value='".$v['code']."'>".$v['name']."</option>";

        }

        return $province;

    }


    /**
     * 获取县/市数据
     * @param $code
     * @return mixed
     */
    public function getCity($code){
        return $this->where('parentcode='.$code)->field('name,code,parentcode')->select();
    }


    /**
     * 获取县/市数据返回str
     * @param $code
     * @param null $next_code
     * @return string
     */
    public function getCitytoStr($code,$next_code = null){
        $region = $this->where('parentcode='.$code)->field('name,code,parentcode')->select();
        $province = "";
        foreach( $region as $k => $v ) {
            $select    = $next_code == $v['code'] ? 'selected="selected"' : '';
            $province .= " <option ".$select." value='".$v['code']."'>".$v['name']."</option>";
        }
        return $province;
    }

    /**
     * @param $area
     * 传县级单位 获取完整区域的数据
     */
    public function getAllarea($area){
        $area = $this->where('code='.$area)->field('name,parentcode')->find();

        $city = $this->where('code='.$area['parentcode'])->field('name,parentcode')->find();

        $province = $this->where('code='.$city['parentcode'])->field('name')->find();

        return $province['name'].'/'.$city['name'].'/'.$area['name'];
    }

}
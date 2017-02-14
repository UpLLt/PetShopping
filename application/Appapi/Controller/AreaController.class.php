<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/22
 * Time: 19:13
 */

namespace Appapi\Controller;

use Common\Model\RegionModel;
use Foster\Model\FosterRulesModel;
use Transport\Model\TransportRulesModel;

class AreaController extends ApibaseController
{
    private $region_model;
    private $transport_rules_model;
    private $foster_rules_model;

    public function __construct()
    {
        parent::__construct();
        $this->region_model = new RegionModel();
        $this->transport_rules_model = new TransportRulesModel();
        $this->foster_rules_model = new FosterRulesModel();
    }


    public function getProvince(){

         $data = F('AreaProvince');
         if(!$data){
             $data = $this->region_model->getProvince();
             foreach( $data as $key => $val ){
                 $city = $this->region_model->getCity($val['code']);
                 foreach( $city as $k => $v ){
                     $country = $this->region_model->getCity($v['code']);
                     $city[$k]['country'] =  $country;
                 }
                 $data[$key]['city'] = $city;
             }
             F('AreaProvince',$data);
         }

         exit($this->returnApiSuccess($data));
    }


    public function getProvinceCity(){

        $data = F('AreaProvinceCity');
        if(!$data){
            $data = $this->region_model->getProvince();
            foreach( $data as $key => $val ){
                $city = $this->region_model->getCity($val['code']);
                $data[$key]['city'] = $city;
            }
            F('AreaProvinceCity',$data);
        }

        exit($this->returnApiSuccess($data));
    }



    public function getCity(){

        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $code = I('post.code');
        $this->checkparam(array($mid, $token,$code));
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }
        $data = $this->region_model->getCity($code);
        exit($this->returnApiSuccess($data));
    }

    /**
     * 获取运输的地址
     */
    public function getTransportArea(){
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid   = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }


        $tr_province = $this->transport_rules_model
             ->alias('a')
             ->join('LEFT JOIN '.C('DB_PREFIX') .'region as b on a.tr_province = b.code')
             ->group('tr_province,b.name')
             ->field('tr_province,b.name')
             ->select();
        if(!$tr_province)  exit($this->returnApiError(ApibaseController::FATAL_ERROR,'该地区尚未设置'));
        foreach( $tr_province as $key => $val){
            $tr_city = $this->transport_rules_model
                ->alias('a')
                ->join('LEFT JOIN '.C('DB_PREFIX') .'region as b on a.tr_city = b.code')
                ->where('tr_province='.$val['tr_province'])
                ->group('a.tr_city,b.name')
                ->field('a.tr_city,b.name')
                ->select();

            foreach( $tr_city as $k => $v ){
                $tr_country = $this->transport_rules_model
                    ->alias('a')
                    ->where('a.tr_city='.$v['tr_city'])
                    ->join('LEFT JOIN '.C('DB_PREFIX') .'region as b on a.tr_country = b.code')
                    ->group('a.tr_country,b.name')
                    ->field('a.tr_country,b.name')
                    ->select();
                $tr_city[$k]['tr_country'] = $tr_country;
            }
            $tr_province[$key]['tr_city'] = $tr_city;
        }

        exit($this->returnApiSuccess($tr_province));
    }

    /**
     * 获取寄养的地址
     */
    public function getFosterArea(){
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid   = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $tr_province = $this->foster_rules_model
            ->alias('a')
            ->join('LEFT JOIN '.C('DB_PREFIX') .'region as b on a.fos_province = b.code')
            ->group('fos_province,b.name')
            ->field('fos_province,b.name')
            ->select();

        if(!$tr_province)  exit($this->returnApiError(ApibaseController::FATAL_ERROR,'该地区尚未设置'));

        foreach( $tr_province as $key => $val){
            $tr_city = $this->foster_rules_model
                ->alias('a')
                ->join('LEFT JOIN '.C('DB_PREFIX') .'region as b on a.fos_city = b.code')
                ->where('fos_province='.$val['fos_province'])
                ->group('a.fos_city,b.name')
                ->field('a.fos_city,b.name')
                ->select();

            foreach( $tr_city as $k => $v ){
                $tr_country = $this->foster_rules_model
                    ->alias('a')
                    ->where('a.fos_city='.$v['fos_city'])
                    ->join('LEFT JOIN '.C('DB_PREFIX') .'region as b on a.fos_country = b.code')
                    ->group('a.fos_country,b.name')
                    ->field('a.fos_country,b.name')
                    ->select();
                $tr_city[$k]['fos_country'] = $tr_country;
            }
            $tr_province[$key]['fos_city'] = $tr_city;
        }

        exit($this->returnApiSuccess($tr_province));
    }

}
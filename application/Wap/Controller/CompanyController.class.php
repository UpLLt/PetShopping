<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/29
 * Time: 下午4:27
 */

namespace Wap\Controller;


use App\Model\DocumentsModel;
use Foster\Model\FosterRulesModel;
use Funeral\Model\BuriedRulesModel;
use Think\Controller;
use Transport\Model\TransportRulesModel;

/**
 * Class ProductPetController
 * @package Wap\Controller
 */
class CompanyController extends Controller
{
    private $documents_model, $buried_rule_model, $foster_rule_model, $trans_rule_model;

    public function __construct()
    {
        parent::__construct();
        $this->documents_model = new DocumentsModel();
        $this->buried_rule_model = new BuriedRulesModel();
        $this->foster_rule_model = new FosterRulesModel();
        $this->trans_rule_model = new TransportRulesModel();
    }

    /**
     * 公司简介
     */
    public function about_company(){
        $order_type = I('order_type');
        if( $order_type == 3 ) $type = 'company_transport';
        if( $order_type == 4 ) $type = 'company_buried';
        if( $order_type == 5 ) $type = 'company_foster';
        $company = $this->documents_model->where(array('doc_class'=>$type))->field('content')->find();
        $this->assign('company',$company['content']);
        $this->display();
    }

    public function serviceInfo() {
        $type = I('get.type');
        $code = I('get.code');
        if($type == 1) {//殡仪
            $info = $this->buried_rule_model->where(array('bu_country' => $code))->getField('bu_service');
        }
        if($type == 2) {//寄养
            $info = $this->foster_rule_model->where(array('fos_country' => $code))->getField('fos_service');
        }
        if($type == 3) {//运输
            $info = $this->trans_rule_model->where(array('tr_country' => $code))->getField('tr_service');
        }

        $this->assign('info', $info);
        $this->display();
    }
}
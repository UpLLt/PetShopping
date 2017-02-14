<?php
namespace Transport\Controller;
use Common\Controller\AdminbaseController;
use Common\Model\RegionModel;
use Think\Controller;
use Transport\Model\TransportModel;
use Transport\Model\TransportRulesModel;

class AirportController extends  AdminbaseController {
    private $region_model , $transport_model , $transport_rules_model ;

    public function __construct()
    {
        parent::__construct();
        $this->region_model = new RegionModel();
        $this->transport_model = new TransportModel();
        $this->transport_rules_model = new TransportRulesModel();
    }

    public function lists()
    {
        $this->_lists();
        $this->display('');
    }

    public function _lists(){
        $transport  = D('transport_airport')->where('keyword = "transport" ')->find();
        $this->assign('lists',$transport['content']);
    }


    public function edit(){

        $content = I('content');
        $airport = D('transport_airport')->where('keyword = "transport" ')->setField('content',$content);
        if( $airport == true ){
            $this->success('修改成功');
        }else{
            $this->error('修改失败');
        }
    }





}
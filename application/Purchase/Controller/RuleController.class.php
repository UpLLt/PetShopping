<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/12/11
 * Time: 17:26
 */

namespace Purchase\Controller;


use Common\Controller\AdminbaseController;
use Purchase\Model\SellRulesModel;

class RuleController extends AdminbaseController
{
    private $sell_rulesmodel;

    public function __construct()
    {
        parent::__construct();
        $this->sell_rulesmodel = new SellRulesModel();
    }



    public function serviceInfo() {
        $info = $this->sell_rulesmodel->where('type=1')->find();
        if(IS_POST) {
            $where['id'] =  I('post.id');
            $data['se_service'] = I('post.se_service');
            $data['create_time'] = time();
            $rst = $this->sell_rulesmodel->where($where)->save($data);
            if($rst) {
                $this->success('修改成功');exit;
            } else {
                $this->error();
            }
        }
        $this->assign('info', $info);
        $this->display();
    }


    /**
     * 婚介协议
     */
    public function MarriageInfo() {
        $info = $this->sell_rulesmodel->where('type=2')->find();
        if(IS_POST) {
            $where['id'] =  I('post.id');
            $data['se_service'] = I('post.se_service');
            $data['create_time'] = time();
            $rst = $this->sell_rulesmodel->where($where)->save($data);
            if($rst) {
                $this->success('修改成功');exit;
            } else {
                $this->error();
            }
        }
        $this->assign('info', $info);
        $this->display();
    }


    /**
     * 卖宠物协议
     */
    public function sellpetInfo() {
        $info = $this->sell_rulesmodel->where('type=3')->find();
        if(IS_POST) {
            $where['id'] =  I('post.id');
            $data['se_service'] = I('post.se_service');
            $data['create_time'] = time();
            $rst = $this->sell_rulesmodel->where($where)->save($data);
            if($rst) {
                $this->success('修改成功');exit;
            } else {
                $this->error();
            }
        }
        $this->assign('info', $info);
        $this->display();
    }


    /**
     * 医疗协议
     */
    public function MerchantInfo() {
        $info = $this->sell_rulesmodel->where('type=4')->find();
        if(IS_POST) {
            $where['id'] =  I('post.id');
            $data['se_service'] = I('post.se_service');
            $data['create_time'] = time();
            $rst = $this->sell_rulesmodel->where($where)->save($data);
            if($rst) {
                $this->success('修改成功');exit;
            } else {
                $this->error();
            }
        }
        $this->assign('info', $info);
        $this->display();
    }

}
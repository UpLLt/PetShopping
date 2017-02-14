<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/28
 * Time: 14:44
 */

namespace Appapi\Controller;


use Common\Model\OrderModel;
use Community\Model\ComRecordModel;
use Community\Model\ComScoreModel;
use Consumer\Model\CouponModel;
use Funeral\Model\BuriedModel;
use Funeral\Model\BuriedRulesModel;

class FuneralController extends ApibaseController
{
    private $buried_model, $buried_rulemodel, $order_model, $coupon_model, $com_score_model,$com_record_model;

    public function __construct()
    {
        parent::__construct();
        $this->buried_model = new BuriedModel();
        $this->buried_rulemodel = new BuriedRulesModel();
        $this->order_model = new OrderModel();
        $this->coupon_model = new CouponModel();
        $this->com_score_model = new ComScoreModel();
        $this->com_record_model = new ComRecordModel();
    }

    /**
     * 获取已设置的区域
     */
    public function getAddr() {
        $postdata = get_data(1);
        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $this->checkparam($postdata);

        //查询省级单位代码及名称
        $rst = $this->buried_rulemodel
            ->join('ego_region ON ego_buried_rules.bu_province = ego_region.code')
            ->field("bu_province,ego_region.name")
            ->group('bu_province,ego_region.name')->select();
        if( !$rst ) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '该地区尚未设置'));

        //查询市级单位代码及名称
        foreach($rst as $k => $v) {
            $children = $this->buried_rulemodel
                ->join('ego_region ON ego_buried_rules.bu_city = ego_region.code')
                ->field("bu_city,ego_region.name")
                ->group('bu_city,ego_region.name')
                ->where(array('bu_province' => $v['bu_province']))
                ->select();
            $rst[$k]['city'] = $children;
            //查询区县级代码及名称
            foreach ($children as $key => $val) {
                $grandch = $this->buried_rulemodel
                    ->join('ego_region ON ego_buried_rules.bu_country = ego_region.code')
                    ->field("bu_country,ego_region.name")
                    ->group('bu_country,ego_region.name')
                    ->where(array('bu_city' => $val['bu_city']))
                    ->select();
                $rst[$k]['city'][$key]['country'] = $grandch;
            }
        }

        if($rst) {
            exit($this->returnApiSuccess($rst));
        } else {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '该地区尚未设置'));
        }

    }

    /**
     * 获取区域价格
     */
    public function getPrice()
    {
        $postdata = get_data(1);
        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $this->checkparam($postdata);

        $rst = $this->buried_rulemodel->getDetail($postdata['country']);
//        dump($this->buried_rulemodel->getLastSql());
        $rst['service'] = 'https://www.mixiupet.com/Wap/Company/serviceInfo/type/1/code/'.$postdata['country'];
        if($rst) {
            exit($this->returnApiSuccess($rst));
        } else {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '暂时没有完善信息'));
        }
    }


    /**
     * 提交殡仪订单
     */
    public function funeral()
    {
        $postdata = get_data(1);
        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $this->checkparam(array($postdata['bu_contacts_phone'],$postdata['bu_area'], $postdata['funeral_price'], $postdata['bu_method'], $postdata['bu_buried'], $postdata['bu_weight'], $postdata['bu_bury'], $postdata['bu_contacts'], $postdata['bu_address'], $postdata['bu_cremation'], $postdata['bu_cremation_price'], $postdata['bu_overstep_price']));

        if(strlen($postdata['bu_contacts_phone']) != 11 ) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '手机号格式错误'));
        }
        $price = $this->buried_rulemodel->getDetail($postdata['bu_area']);


        $total = $postdata['funeral_price'];
        if($postdata['bu_method'] == 1) {//上门取货
            $total = $price['bu_price'];
        }

        if($postdata['bu_buried'] == 1) {//要火化
            if($postdata['bu_weight'] <= $postdata['bu_cremation']) {
                $fire_pri = $postdata['bu_cremation_price'];
                $total += $fire_pri;
            } else {
                $fire_pri = $price['bu_cremation_price']+ ($postdata['bu_weight']- $price['bu_cremation'])*$postdata['bu_overstep_price'];
                $total += $fire_pri;
            }
        }

        $this->buried_model->startTrans();
        $is_commit = true;
        $order = array(
            'order_sn' => $this->order_model->getOrderNumber(),
            'order_type' => OrderModel::ORDER_TYPE_FUNERAL,
            'status' => OrderModel::STATUS_WAIT_FOR_PAY,
            'order_price' => $total,
            'comment_status' => 0,
            'create_time' => time(),
            'cover' => C('DEFAULT_BINYI_URL'),
            'mid' => $mid,
        );
//        echo json_encode($order);exit;
        $rst = $this->order_model->add($order);

        if(!$rst) {
            $is_commit = false;
        }

        $buried = array(
            'order_id' => $rst,
            'bu_buried' => $postdata['bu_buried'],
            'bu_weight' => $postdata['bu_weight'],
            'bu_bury' => $this->buried_model->getMethod($postdata['bu_bury']),
            'bu_contacts' => $postdata['bu_contacts'],
            'bu_contacts_phone' => $postdata['bu_contacts_phone'],
            'bu_address' => $postdata['bu_address'],
            'bu_funeral_price' => $postdata['funeral_price'],
            'bu_price' => $total,
            'bu_pick_up_price' => $postdata['bu_method'] == 1 ? $price['bu_price'] : 0,
            'bu_method' => $postdata['bu_method'],
            'bu_area'=>$postdata['bu_area']
        );
        $res = $this->buried_model->add($buried);
        if(!$res) {
            $is_commit = false;
        }
        if($is_commit){
            $this->buried_model->commit();
        }else{
            $this->buried_model->rollback();
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单生成失败'));
        }
        //积分查询
        $score_number = $this->com_score_model->scoExchange($mid, $total, true);
        $score_price = $this->com_score_model->scoExchange($mid, $total);
        $return = array(
            'order_id' => $rst,
            'total_logistics_sum' => sprintf("%.2f",($postdata['bu_method'] == 1 ? $price['bu_price'] : '0')),
            'order_price' => sprintf("%.2f",$total),
            'name' => '宠物殡仪',
            'cover' => C('DEFAULT_BINYI_URL'),
            'score' => $score_price['score'],
            'score_use' => $score_number,
            'score_price' => $score_price['price'],
//            'funeral_price' => sprintf("%.2f", $buried['bu_funeral_price']),
//            'fire_price' => sprintf("%.2f", $fire_pri),
        );
        exit($this->returnApiSuccess($return));

    }


}
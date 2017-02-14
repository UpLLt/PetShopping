<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/22
 * Time: 19:13
 */

namespace Appapi\Controller;



use App\Model\DocumentsModel;
use Common\Model\MempetModel;
use Common\Model\PushmsgModel;
use Consumer\Model\MemberModel;

class UserCenterController extends ApibaseController
{
    private $member_model;
    private $mempet_model;
    private $push_msgmodel;
    private $documents_model;


    public function __construct()
    {
        parent::__construct();
        $this->member_model = new MemberModel();
        $this->mempet_model = new MempetModel();
        $this->push_msgmodel = new PushmsgModel();
        $this->documents_model = new DocumentsModel();
    }

    public function getHeadimg()
    {
        if (IS_POST) {
            $mid = I('post.mid');
            $token = I('post.token');
            $this->checkparam(array($mid, $token));
            if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

            $result = $this->member_model
                ->join('LEFT JOIN ego_com_score ON ego_member.id = ego_com_score.sco_member_id')
                ->where(array('id' => $mid))
                ->field('headimg,nickname, sco_level')
                ->find();

            $result['headimg'] = setUrl($result['headimg']);

            exit($this->returnApiSuccess($result));
        } else {
            exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        }
    }


    public function updateHeadimg()
    {
        if (IS_POST) {
            $mid = I('post.mid');
            $token = I('post.token');
            $this->checkparam(array($mid, $token));
            if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));


            $fileurl = upload_img('User');
            if (!$fileurl) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '图片上传失败'));
            $result = $this->member_model->where(array('id' => $mid))->save(array('headimg' => $fileurl[0]));

            if ($result === false) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '上传成功，写入数据库失败'));
            exit($this->returnApiSuccess());
        } else {
            exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        }
    }

//
//    public function updataSex()
//    {
//        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
//        $mid = I('post.mid');
//        $token = I('post.token');
//        $sex = I('post.sex');
//
//        $this->checkparam(array($mid, $token, $sex));
//        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
//
//
//        $result = $this->member_model->where(array('id' => $mid))->save(array('sex' => $sex));
//        if ($result === false)
//            exit($this->returnApiError(ApibaseController::FATAL_ERROR));
//
//        exit($this->returnApiSuccess());
//    }
//

    public function updataNickname()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $nickname = I('post.nickname');

        $this->checkparam(array($mid, $token, $nickname));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $result = $this->member_model->where(array('id' => $mid))->save(array('nickname' => $nickname));
        if ($result === false)
            exit($this->returnApiError(ApibaseController::FATAL_ERROR));
        exit($this->returnApiSuccess());
    }

    /**
     * 获取宠物信息
     */
    public function getPetmess(){
        if( !IS_POST ) exit( $this->returnApiError(ApibaseController::INVALID_INTERFACE ));
        $mid   = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid,$token));
        if( !$this->checktoken($mid,$token) ) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $mempet = $this->mempet_model->where(array('mid'=>$mid))->field('pid,pname,psex,pbirthday,headimg')->select();
        foreach( $mempet as $k => $v){
            $mempet[$k]['headimg']   = $this->setUrl($v['headimg']);
            $mempet[$k]['pbirthday'] = $this->mempet_model->getage(date('Y-m-d',$v['pbirthday']));
        }
        exit( $this->returnApiSuccess($mempet));
    }

    /**
     * 宠物信息
     */

    public function PetMessage(){
        if( !IS_POST ) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $pid = I('post.pid');
        $mid = I('post.mid');
        $token = I('post.token');

        $this->checkparam(array($pid,$mid,$token));
        if( !$this->checktoken($mid,$token) ) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $pet = $this->mempet_model->where(array('mid'=>$mid,'pid'=>$pid))->find();
        $pet['ptype']= $this->mempet_model->petTypetoString($pet['ptype']);
        $pet['pbirthday']= date('Y-m-d', $pet['pbirthday']);// $this->mempet_model->getage($pet['pbirthday']);
        $pet['headimg'] = $this->setUrl($pet['headimg']);
        $pet['insect_time'] = $pet['insect_time'] ? date('Y-m-d', $pet['insect_time']) : '';
        $pet['vaccine_time'] = $pet['vaccine_time'] ? date('Y-m-d', $pet['vaccine_time']) : '';
        exit($this->returnApiSuccess($pet));
    }

    /**
     * 添加宠物
     */
    public function addPet(){
        if( !IS_POST ) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $ptype    = I('post.ptype');
        $pname    = I('post.pname');
        $pbirthday= I('post.pbirthday');
        $psex     = I('post.psex');
        $Insect_time = I('post.Insect_time');
        $vaccine_time = I('post.vaccine_time');

        $this->checkparam(array($mid,$token,$ptype,$pname,$pbirthday,$psex));
        if( !$this->checktoken($mid,$token) ) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        if( strtotime($pbirthday) >= time() ) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '添加时间不正确'));
        if( strtotime($Insect_time) >= time() ) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '添加驱虫时间不正确'));
        if( strtotime($vaccine_time) >= time() ) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '添加疫苗时间不正确'));
        $fileurl = upload_img('Pet');
        if (!$fileurl) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '图片上传失败'));
        $data = array(
            'mid' => $mid,
            'ptype' => $ptype,
            'pname' => $pname,
            'pbirthday' => strtotime($pbirthday),
            'psex' => $psex,
            'headimg' => $fileurl[0],
            'Insect_time' => $Insect_time ? strtotime($Insect_time) : '',
            'vaccine_time' => $vaccine_time ? strtotime($vaccine_time) : '',
            'create_time' => time(),
        );
        $msg = array();
        if($Insect_time) {
            $msg[] = array(
                'mid' => $mid,
                'push_time' => ($this->getTime(strtotime($Insect_time),PushmsgModel::MSG_TYPE_INSECT)),
                'push_type' => PushmsgModel::MSG_TYPE_INSECT,
                'push_content' =>  '您的宠物该打疫苗了！',
            );
        }
        if($vaccine_time) {
            $msg[] = array(
                'mid' => $mid,
                'push_time' => ($this->getTime(strtotime($vaccine_time), PushmsgModel::MSG_TYPE_VACCINE)),
                'push_type' => PushmsgModel::MSG_TYPE_VACCINE,
                'push_content' =>  '您的宠物该打疫苗了！',
            );
        }
        $this->push_msgmodel->startTrans();
        $iscommit = true;
        if($Insect_time && $vaccine_time){
            if($this->push_msgmodel->addAll($msg) == false) {
                $iscommit = false;
            }
        }
        if($this->mempet_model->add($data) == false) {
            $iscommit = false;
        }
//        $iscommit = false;
        if($iscommit) {
            $this->push_msgmodel->commit();
            exit($this->returnApiSuccess());
        } else {
            $this->push_msgmodel->rollback();
            exit($this->returnApiError(ApibaseController::FATAL_ERROR,"添加失败"));
        }

    }


    /**
     * 修改宠物头像
     */
    public function editPetHeading(){
        if (IS_POST) {
            $mid = I('post.mid');
            $pid = I('post.pid');
            $token = I('post.token');

            $this->checkparam(array($mid, $token));
            if (!$this->checktoken($mid, $token,$pid)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

            $fileurl = upload_img('Pet');
            if (!$fileurl) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '图片上传失败'));
            $result = $this->mempet_model->where(array('pid' => $pid))->save(array('headimg' => $fileurl[0]));

            if ($result === false) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '上传成功，写入数据库失败'));
            exit($this->returnApiSuccess());
        } else {
            exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        }
    }

    /**
     * 修改宠物信息
     */
    public function editPetMess(){
        if( !IS_POST ) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $pid      = I('post.pid');
        $mid      = I('post.mid');
        $token    = I('post.token');
        $ptype    = I('post.ptype');
        $pname    = I('post.pname');
        $pbirthday= I('post.pbirthday');
        $psex     = I('post.psex');
        $Insect_time  = I('post.Insect_time');
        $vaccine_time = I('post.vaccine_time');


        $this->checkparam(array($mid,$token,$pid,$ptype,$pname,$pbirthday,$psex));
        if( !$this->checktoken($mid,$token) ) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        if($_FILES) {
            $fileurl = upload_img('Pet');
        }
        $data = array(
            'mid' => $mid,
            'ptype' => $ptype,
            'pname' => $pname,
            'pbirthday' => strtotime($pbirthday),
            'psex' => $psex,
            'Insect_time' => $Insect_time ? strtotime($Insect_time) : '',
            'vaccine_time' => $vaccine_time ? strtotime($vaccine_time) : '',
            'create_time' => time(),
        );
        if($fileurl) {
            $data['headimg'] = $fileurl[0];
        }
        $msg = array();
        if($Insect_time) {
            $msg[] = array(
                'mid' => $mid,
                'push_time' => ($this->getTime(strtotime($Insect_time),PushmsgModel::MSG_TYPE_INSECT)),
                'push_type' => PushmsgModel::MSG_TYPE_INSECT,
                'push_content' =>  '您的宠物该打疫苗了！',
            );
        }
        if($vaccine_time) {
            $msg[] = array(
                'mid' => $mid,
                'push_time' => ($this->getTime(strtotime($vaccine_time), PushmsgModel::MSG_TYPE_VACCINE)),
                'push_type' => PushmsgModel::MSG_TYPE_VACCINE,
                'push_content' =>  '您的宠物该打疫苗了！',
            );
        }
        $this->push_msgmodel->startTrans();
        $iscommit = true;
        if($Insect_time && $vaccine_time){
            if($this->push_msgmodel->addAll($msg) == false) {
                $iscommit = false;
            }
        }
        if($this->mempet_model->where(array('pid' => $pid))->save($data) == false) {
            $iscommit = false;
        }
//        $iscommit = false;
        if($iscommit) {
            $this->push_msgmodel->commit();
            exit($this->returnApiSuccess());
        } else {
            $this->push_msgmodel->rollback();
            exit($this->returnApiError(ApibaseController::FATAL_ERROR,"修改失败"));
        }
    }
    /**
     * 删除宠物
     */
    public function delete_pet(){
        if( !IS_POST ) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $pid      = I('post.pid');
        $mid      = I('post.mid');
        $token    = I('post.token');
        $this->checkparam(array($pid,$mid,$token));
        if( !$this->checktoken($mid,$token) ) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $result = $this->mempet_model->delete($pid);
        if( !$result ) exit($this->returnApiError(ApibaseController::FATAL_ERROR,'删除失败'));
        exit($this->returnApiSuccess());
    }

    //获取疫苗、驱虫下次推送时间
    public function getTime($time, $type) {
        $return = '';
        $year = date('Y', $time);
        $month = date('m', $time);
        $date = date('d', $time);
        if($type == PushmsgModel::MSG_TYPE_VACCINE) {
            if($date == 29 && $month == '02') {
                $date = 28;
            }
            $year += 1;
            $return = strtotime($year.'-'.$month.'-'.$date)-3600*24*15;
            if($return <= strtotime(date('Y-m-d 00:00:00',time()))) {
                if($date == 29 && $month == '02') {
                    $date = 28;
                }
                $year += 1;
                $return = strtotime($year.'-'.$month.'-'.$date);
            }
        }
        if($type == PushmsgModel::MSG_TYPE_INSECT) {
            $month += 3;
            if($month > 12) {
                $month = $month-12;
                $year += 1;
            }
            $return = strtotime($year.'-'.$month.'-'.$date)-3600*24*15;
            if($return <= strtotime(date('Y-m-d 00:00:00',time()))) {
                $month += 3;
                if($month > 12) {
                    $month = $month-12;
                    $year += 1;
                }
                $return = strtotime($year.'-'.$month.'-'.$date)-3600*24*15;
            }
        }
        return $return;
    }

    /**
     * 服务热线
     */
    public function service_phone(){
       $service_phone = $this->documents_model->where('doc_class="service_phone"')->find();
       exit($this->returnApiSuccess($service_phone['desc']));
    }


}
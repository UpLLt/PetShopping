<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/12/14
 * Time: 9:54
 */

namespace Appapi\Controller;


use Common\Model\PushmsgModel;
use Common\Model\SmslogModel;
use Consumer\Model\MemberModel;

class PushmsgController extends ApibaseController
{
    private $push_msgmodel ,$smslog_model ,$member_model;

    public function __construct()
    {
        parent::__construct();
        $this->push_msgmodel = new PushmsgModel();
        $this->smslog_model = new SmslogModel();
        $this->member_model = new MemberModel();
    }



    public function pushMsg() {
        $now = time();
        $starttime = strtotime(date('Y-m-d 00:00:00',$now));
        $endtime = strtotime(date('Y-m-d 23:59:59',$now));
        $where['push_time'] = array('between', array($starttime, $endtime));
        $where['push_status'] = PushmsgModel::PUSH_STATUS_NO;
        $lists = $this->push_msgmodel->where($where)->select();
        if(!$lists) {
            exit;
        }
        $savedata = array();//修改消息状态
        $adddata = array();//添加推送消息队列
        $insectids = array();//驱虫推送对象
        $vaccineidss = array();//疫苗推送对象
        $saveids = '';
//        dump($lists);
        foreach ($lists as $k => $v) {
            $saveids .= $saveids ? (','.$v['id']) : $v['id'];
            if($v['push_type'] == PushmsgModel::MSG_TYPE_VACCINE) {
                array_push($vaccineidss, $v['mid']);
            }
            if($v['push_type'] == PushmsgModel::MSG_TYPE_VACCINE) {
                array_push($insectids, $v['mid']);
            }
            $adddata[] = array(
                'mid' => $v['mid'],
                'push_time' => $this->getTime($v['push_time'], $v['push_type']),
                'push_type' => $v['push_type'],
                'push_content' => $v['push_type'] == PushmsgModel::MSG_TYPE_VACCINE ? '您的宠物该打疫苗了！' : '您的宠物该驱虫了！',
            );




        }
        $this->push_msgmodel->startTrans();
        $iscommit = true;
//        echo json_encode(array('id1' => $adddata, 'id2' => $insectids));exit;
        $rst = $this->push_msgmodel->where(array('id' => array('in', $saveids)))->save(array('push_status' => 2));
        if(!$rst) {
            $iscommit = false;
        }
        $res = $this->push_msgmodel->addAll($adddata);
        if(!$res) {
            $iscommit = false;
        }

        if($iscommit) {
            $this->push_msgmodel->commit();
            $mem_where['id'] = ['in',$vaccineidss];
            $mem_vacc = $this->member_model->where($mem_where)->field('username')->select();
            foreach(  $mem_vacc as $k => $v ){


                $content = C('YIMIAO_CONTENT');
                $data = [
                    'content'     => $content,
                    'mobile'      => $v['username'],
                    'create_time' => time(),
                    'end_time'    => time()
                ];
                vendor("Cxsms.Cxsms");
                $options = C('YIMIAO_CONTENT');

                $Cxsms = new \Cxsms($options);
                $result = $Cxsms->send($v['username'], $content);
                if ($result && $result['returnsms']['returnstatus'] == 'Success') {
                    $this->smslog_model->add($data);

                }
            }

            $is_where['id'] = ['in',$insectids];
            $mem_ins = $this->member_model->where($is_where)->field('username')->select();
            foreach(  $mem_ins as $k => $v ){

                $content = C('QUCHONG_CONTENT');
                $data = [
                    'content'     => $content,
                    'mobile'      => $v['username'],
                    'create_time' => time(),
                    'end_time'    => time()
                ];

                $result_int = $Cxsms->send($v['username'], $content);

                if ($result_int && $result_int['returnsms']['returnstatus'] == 'Success') {
                    $this->smslog_model->add($data);

                }
            }

            $this->pushMess('您的宠物该打疫苗了', $vaccineidss);
            $this->pushMess('您的宠物该驱虫了', $insectids);
            exit;
        } else {
            $this->push_msgmodel->rollback();exit;
        }
//        dump($savedata);dump($addddata);dump($insectids);
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
            $return = strtotime($year.'-'.$month.'-'.$date);
        }
        if($type == PushmsgModel::MSG_TYPE_INSECT) {
            $month += 3;
            if($month > 12) {
                $month = $month-12;
                $year += 1;
            }
            $return = strtotime($year.'-'.$month.'-'.$date);
        }
        return $return;
    }

    public function pushMess($contents,$recive,$extra = array("versionname"=>'1', "versioncode"=>'2')){
        import('.Org.Util.Jpush');
        $pushObj = new \JPush();
        //组装需要的参数
        // $receive = 'all';     //全部
        $receive = array('alias' => $recive) ;//dump($receive);exit;
        //$receive = array('tag'=>array('1','2','3'));      //标签
        //$receive = array('alias'=>array('111'));    //别名
        $title = '咪咻';//$_POST['title'];
        $content = $contents;
        $m_time = '432000';        //离线保留时间
        $extras = $extra;//array("versionname"=>'1', "versioncode"=>'2');   //自定义数组
        //调用推送,并处理
        $result = $pushObj->push($receive,$title,$content,$extras,$m_time);
         if($result){
             $res_arr = json_decode($result, true);
             if(isset($res_arr['error'])){   //如果返回了error则证明失败
                 //错误信息 错误码
                 // echo json_encode(array('ret' => '2001', 'msg' => '推送失败'));
                 $this->error($res_arr['error']['message'].'：'.$res_arr['error']['code']);//,U('Jpush/index')
             }else{
                 //处理成功的推送......
                 //可执行一系列对应操作~
                 echo json_encode(array('ret'=>'2000', 'msg' => '推送成功'));
                 // $this->success('推送成功~');
             }
         }else{      //接口调用失败或无响应
             $this->error('接口调用失败或无响应~');
         }



        /*vendor('JPush.JPush');
        $AppKey = "f60ee52c21da440ec74a656b";
        $MasterSecret = "05ad692b48e0612f1d7b1c09";

        $client = new \JPush($AppKey, $MasterSecret);
        $msg_content = $_POST['post']['post_excerpt'];
        $msg_title = $_POST['post']['post_title'];
        //简单推送
//                        $result33 = $client->push()
//                            ->setPlatform('all')
//                            ->addAllAudience()
//                            ->setNotificationAlert($msg_title)
//                            ->send();

        // 完整的推送示例,包含指定Platform,指定Alias,Tag,指定iOS,Android notification,指定Message等
        $result = $client->push()
            ->setPlatform(array('android', 'ios'))
            ->setAudience('all')
            ->setNotificationAlert('党务助理')
            ->addAndroidNotification($msg_content, $msg_title, 1, array("key1" => "value1", "key2" => "value2"))
            ->addIosNotification($msg_title, 'iOS sound', \JPush::DISABLE_BADGE, true, 'iOS category', array("key1" => "value1", "key2" => "value2"))
            ->setMessage($msg_content, $msg_title, 'type', array("key1" => "value1", "key2" => "value2"))
            ->setOptions(100000, 3600, null, true)
            ->send();*/

    }

    /**
     * 消息列表
     */
    public function msgList() {
        $postdata = get_data(1);
        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
//        $this->checkparam($postdata);

        //页码
        $page = isset($postdata['page']) && intval($postdata['page'])>1 ? $postdata['page'] : '1';
        //每页显示数量
        $perpage = isset($postdata['perpage']) && intval($postdata['perpage'])>1 ? $postdata['perpage'] : '10';
        $where = array(
            'mid' => $mid,
            'push_status' => PushmsgModel::PUSH_STATUS_YES,
        );
        $lists = $this->push_msgmodel
            ->where($where)
            ->page($page, $perpage)
            ->order('push_read asc, push_time desc')
            ->select();
        $count = $this->push_msgmodel
            ->where($where)
            ->count();
        foreach ($lists as $k => $v) {
            $return['list'][] = array(
                'id' => $v['id'],
                'push_time' => date('Y-m-d', $v['push_time']),
                'push_content' => $v['push_content'],
                'push_read' => $v['push_read'],
                'push_type' => $v['push_type'],
            );
        }
        $totalpage = $count/$perpage;
        $totalpage = floor($totalpage);
        if($count % $perpage) {
            $totalpage += 1;
        }
        $return['count'] = $totalpage;
        exit($this->returnApiSuccess($return));
    }

    /**
     * 修改消息为已读状态
     */
    public function editStatus() {
        $postdata = get_data(1);
        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $this->checkparam($postdata);

        $rst = $this->push_msgmodel->editRead($postdata['id']);
        if(!$rst) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '修改状态失败'));
        }
        exit($this->returnApiSuccess());
    }

    public function delMsg() {
        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $rst = $this->push_msgmodel
            ->where(array('mid' => $mid, 'push_status' => PushmsgModel::PUSH_STATUS_YES))
            ->delete();
        if(!$rst) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '删除失败'));
        }
        exit($this->returnApiSuccess());
    }

    public function isRead() {
        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $count = $this->push_msgmodel
            ->where(array('mid' => $mid, 'push_status' => PushmsgModel::PUSH_STATUS_YES, 'push_read' => PushmsgModel::READ_STATUS_NO))
            ->find();

        if(empty($count)) {
            $data['lists'] = 1;
        } else {
            $data['lists'] = 2;
        }
        exit($this->returnApiSuccess($data));
    }
}
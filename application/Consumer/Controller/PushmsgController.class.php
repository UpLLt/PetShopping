<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/12/15
 * Time: 9:56
 */

namespace Consumer\Controller;


use Common\Controller\AdminbaseController;
use Common\Model\PushmsgModel;
use Consumer\Model\MemberModel;

class PushmsgController extends AdminbaseController
{
    private $push_msgmodel, $member_model;

    public function __construct()
    {
        parent::__construct();
        $this->push_msgmodel = new PushmsgModel();
        $this->member_model = new MemberModel();
    }

    public function msgPush() {
        if(IS_POST) {
            $content = I('post.content');
            if(empty($content)) {
                $this->error('请输入推送内容');
            }
            $members = $this->member_model
                ->field('id')
                ->select();
            $time = time();
            $ids = array();
            $data = array();
            foreach($members as $k => $v) {
                $data[] = array(
                    'mid' => $v['id'],
                    'push_time' => $time,
                    'push_status' => 2,
                    'push_content' => $content,
                    'push_type' => PushmsgModel::MSG_TYPE_ALL,
                );
//                $ids .= $v['id'].',';
                array_push($ids, $v['id']);
            }
            $this->push_msgmodel->startTrans();
            $iscommit = true;
            $rst = $this->push_msgmodel->addAll($data);
//            dump($ids);exit;
//            $rst= true;
            if(!$rst) {
                $iscommit = false;
            }
            $res = $this->pushMess($content, $ids);
            if($res['ret'] != 2000) {
                $iscommit = false;
            }

            if($iscommit) {
                $this->push_msgmodel->commit();
                $this->success('推送成功');
            } else {
                $this->error('推送失败');
            }
        }
        $this->display();
    }

    public function pushMess($contents,$recive,$extra = array("versionname"=>'1', "versioncode"=>'2')){
        import('.Org.Util.Jpush');
        $pushObj = new \JPush();
        //组装需要的参数
        // $receive = 'all';     //全部
        $receive = $recive ? array('alias' => $recive) : 'all' ;//dump($receive);exit;
//        dump($receive);exit;
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
//                 $this->error($res_arr['error']['message'].'：'.$res_arr['error']['code']);//,U('Jpush/index')
                 return array('ret' => $res_arr['error']['code'], 'msg' => $res_arr['error']['message']);
             }else{
                 //处理成功的推送......
                 //可执行一系列对应操作~
//                 echo json_encode(array('ret'=>'2000', 'msg' => '推送成功'));
                 // $this->success('推送成功~');
                 return array('ret' => 2000, 'msg' => '推送成功');
             }
         }else{      //接口调用失败或无响应
//             $this->error('接口调用失败或无响应~');
             return array('ret' => 2001, 'msg' => '接口调用失败或无响应');
         }


    }
}
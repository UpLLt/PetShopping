<?php
namespace Comment\Controller;

use Common\Controller\AdminbaseController;
use Common\Model\CommentModel;
use Common\Model\OrderModel;

class CommentadminController extends AdminbaseController{
	
	/*protected $comments_model;

	public function _initialize(){
		parent::_initialize();
		$this->comments_model=D("Common/Comments");
	}

	// 后台评论列表
	public function index($table=""){
		$where=array();
		if(!empty($table)){
			$where['post_table']=$table;
		}

		$post_id=I("get.post_id");
		if(!empty($post_id)){
			$where['post_id']=$post_id;
		}
		$count=$this->comments_model->where($where)->count();
		$page = $this->page($count, 20);
		$comments=$this->comments_model
		->where($where)
		->limit($page->firstRow . ',' . $page->listRows)
		->order("createtime DESC")
		->select();
		$this->assign("comments",$comments);
		$this->assign("page", $page->show('Admin'));
		$this->display(":index");
	}

	// 后台评论删除
	public function delete(){
		if(isset($_GET['id'])){
			$id = intval(I("get.id"));
			if ($this->comments_model->where("id=$id")->delete()!==false) {
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}
		if(isset($_POST['ids'])){
			$ids=join(",",$_POST['ids']);
			if ($this->comments_model->where("id in ($ids)")->delete()!==false) {
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}
	}

	// 后台评论审核
	public function check(){
		if(isset($_POST['ids']) && $_GET["check"]){
			$data["status"]=1;

			$ids=join(",",$_POST['ids']);

			if ( $this->comments_model->where("id in ($ids)")->save($data)!==false) {
				$this->success("审核成功！");
			} else {
				$this->error("审核失败！");
			}
		}
		if(isset($_POST['ids']) && $_GET["uncheck"]){

			$data["status"]=0;
			$ids=join(",",$_POST['ids']);
			if ( $this->comments_model->where("id in ($ids)")->save($data)!==false) {
				$this->success("取消审核成功！");
			} else {
				$this->error("取消审核失败！");
			}
		}
	}*/


	private $comment_model;

    public function __construct()
    {
        parent::__construct();
        $this->comment_model = new CommentModel();
    }

    public function lists() {
        $fields = [
            'nickname' => ["field" => "b.nickname", "operator" => "=", 'datatype' => 'string'],
            'status' => ["field" => "a.status", "operator" => "=", 'datatype' => 'string'],
        ];
//        dump($_POST);
        $where_ands = [];
        if (IS_POST) {
            foreach ($fields as $param => $val) {
                if (isset($_POST[$param]) && !empty($_POST[$param])) {
                    $operator = $val['operator'];
                    $field = $val['field'];
                    $datatype = $val['datatype'];
                    $get = $_POST[$param];
                    $_GET[$param] = $get;

                    if ($operator == "like") {
                        $get = "%$get%";
                    }
                    if ($datatype == 'time')
                        $get = strtotime($get);
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        } else {
            foreach ($fields as $param => $val) {
                if (isset($_GET[$param]) && !empty($_GET[$param])) {
                    $operator = $val['operator'];
                    $field = $val['field'];
                    $datatype = $val['datatype'];
                    $get = $_GET[$param];
                    if ($operator == "like") {
                        $get = "%$get%";
                    }
                    if ($datatype == 'time')
                        $get = strtotime($get);
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        }

        $where = join(" and ", $where_ands);
        $count = $this->comment_model
            ->alias('a')
            ->join('left join ego_member as b on a.mid = b.id')
            ->where($where)
            ->count();
        $page = $this->page($count,C('PAGE_NUMBER'));
        $lists = $this->comment_model
            ->alias('a')
            ->join('left join ego_member as b on a.mid = b.id')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('a.id, a.status, content, star, a.create_time, replay, reply_status, order_type, b.nickname')
            ->order('a.create_time desc')
            ->where($where)
            ->select();
//        dump($lists);
        foreach($lists as $k => $v) {
            $method = '';
            if($v['status'] == CommentModel::OFF_EFFECT) {
                $method = '未生效 | '. '<a href="' . U('Commentadmin/edit_status', array('id' => $v['id'])) . '">立即生效</a>';
            }
            if($v['status'] == CommentModel::ON_EFFECT) {
                $method = '已生效';
            }
            $button = '<a data-toggle="modal" data-target="#myModal"  class="add_ext"  onclick="" name="'.$v['id'].'">回复</a>';
            $delete = '<a class="js-ajax-delete" href="' . U('Commentadmin/delete', array('id' => $v['id'])) . '">删除</a>';
            $comments .= '<tr>
                            <td>' . ($k + 1) . '</td>
                            <td>' . $v['nickname'] . '</td>
                            <td>' . $v['star']  . '</td>
                            <td>' . $v['content'] . '</td>
                            <td>' . date('Y-m-d H:i',$v['create_time']) . '</td>
                            <td>' . (($v['order_type'] == OrderModel::ORDER_TYPE_HOSPITAL ) ? '宠物医疗，不能回复' : ($v['reply_status'] == CommentModel::YES_REPLY ? $v['replay'] : $button)) . '</td>
                            <td>' . $method . '</td>
                            <td>' . $delete . '</td>
                          </tr>';
        }

        $this->assign('comments', $comments);
        $this->assign('Page',$page->show());
        $this->assign('formget', I(''));
        $this->display();
    }

    /**
     * 评论生效
     */
    public function edit_status() {
        $id = I('get.id');
        $rst = $this->comment_model->where(array('id' => $id))->setField('status', CommentModel::ON_EFFECT);
        if($rst) {
            $this->success();
        } else {
            $this->error();
        }
    }

    /**
     * 回复
     */
    public function reply() {
        $id = I('post.id');
        $data['replay'] = I('post.replay');
//        dump(strlen($data['replay']));exit;
//        if(strlen($data['replay']) > 30) {
//            $this->error('回复不能超过30个字');
//        }
        $data['reply_status'] = CommentModel::YES_REPLY;
        $rst = $this->comment_model->where(array('id' => $id))->save($data);
        if($rst) {
            $this->success();
        } else {
            $this->error();
        }
     }

     public function delete() {
         $id = I('get.id');
         $rst = $this->comment_model->where(array('id' => $id))->delete();
         if($rst) {
             $this->success();
         } else {
             $this->error();
         }
     }
}
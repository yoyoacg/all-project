<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/8
 * Time: 11:09
 */

namespace app\api\controller;


use app\common\controller\Api;
use think\Request;
use  app\common\model\Note as NoteModel;

class Note extends Api
{
    protected $noNeedLogin=[];

    protected $noNeedRight='*';
    protected  $model;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->model = new NoteModel();
    }

    /**
     * 新增
     */
    public function add(){
        $user_id = $this->auth->id;
        $address = $this->request->post('address');
        $category = $this->request->post('category');
        $price = $this->request->post('price');
        $desc = $this->request->post('desc');
        $create_time = $this->request->post('create_time');
        $imgs = $this->request->post('imgs');
        if($address||$category||$price||$desc||$create_time){
            $add_data=[
                'user_id'=>$user_id
            ];
            if($address) $add_data['address']=$address;
            if($category) $add_data['category']=$category;
            if($price) $add_data['price']=$price;
            if($desc) $add_data['desc']=$desc;
            if($imgs) $add_data['imgs']=$imgs;
            if($create_time){
                $add_data['create_time']=date('Y-m-d H:i:s',strtotime($create_time));
            } else{
                $add_data['create_time'] = date('Y-m-d H:i:s');
            }
           NoteModel::create($add_data);
            $this->success('success',null,200);
        }else{
            $this->error('缺少参数');
        }
    }

    /**
     * 获取列表
     * @throws \think\Exception
     */
    public function get_list(){
        $user_id = $this->auth->id;
        $page = $this->request->post('page', 1);
        $pageSize = $this->request->post('page_size', 10);
        $time = $this->request->post('year');
        if (intval($page) < 1) $page = 1;
        $offset = ($page - 1) * intval($pageSize);
        $order = 'create_time';
        $sort = 'desc';
        $where = [
            'user_id'=>$user_id,
        ];
        if($time){
            $where['create_time']=['between time',[$time.'-01-01',$time.'-12-31']];
        }
        $total = $this->model->where($where)->count('id');
        $list = $this->model->where($where)
            ->order($order,$sort)
            ->limit($offset,$pageSize)
            ->column('*');
        foreach ($list as $k=>$v){
            $v['imgs'] = explode(',',$v['imgs']);
            unset($v['user_id']);
            $list[$k]=$this->cUL($v,false);
        }
        $result = [
            'total' => $total,
            'currentPage' => $page,
            'list' => array_values($list)
        ];
        $this->result('success', $result, 200);
    }

    /**
     * 首页数据
     * @throws \think\Exception
     */
    public function index(){
        $time = $this->request->post('year',date('Y'));
        $user_id = $this->auth->id;
        $where = [
            'user_id'=>$user_id,
            'create_time'=>['between time',[$time.'-01-01',$time.'-12-31']]
        ];
        $total = $this->model->where($where)->count('id');
        $category_num = $this->model->where($where)->group('category')
            ->column('category,count(id) as num');
        $list=[
            'total'=>$total,
            'domestic'=>$category_num['Domestic travel']??0,
            'abroad'=>$category_num['Travel abroad']??0,
        ];
//        foreach ($category_num as $k=>$v){
//            $k = implode('',explode(' ',$k));
//            $list[$k]=$v;
//        }
//        $list['total']=$total;

        $this->result('success', $list, 200);
    }

    public function detail(){
        $id = $this->request->param('id');
        $data = $this->model->where('id',$id)->find();
        $data = [
            'id'=>$data['id'],
            'address'=>$data['address'],
            'category'=>$data['category'],
            'price'=>$data['price'],
            'desc'=>$data['desc'],
            'createTime'=>$data['create_time'],
            'imgs'=>explode(',',$data['imgs']),
        ];
        $this->result('success', $data, 200);
    }

}
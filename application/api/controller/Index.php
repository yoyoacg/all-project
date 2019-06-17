<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\KeyStorage;

/**
 * 首页接口
 */
class Index extends Api
{

    protected $noNeedLogin = ['index','getip'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     * 
     */
    public function index()
    {
        $this->success('请求成功');
    }
    public function getip(){
        $ip = $this->request->ip();
        $data=['ip'=>$ip];
        $this->result('',$data);
    }

    /**
     * 添加自定义存储
     */
    public function add_key_storage(){
        $param= $this->request->param();
        $user_id = $this->auth->id;
        if(empty($param['key'])||empty($param['values'])||!ctype_alnum($param['key'])) $this->result('fail',null,0);
        $is_exit = KeyStorage::where(['user_id'=>$user_id,'key'=>$param['key']])->value('id');
        $data=[
            'key'=>$param['key'],
            'values'=>$param['values']
        ];
        if($is_exit){
            $data['id']=$is_exit;
            KeyStorage::update($data);
        }else{
            $data['user_id'] = $user_id;
            $data['create_time'] = date('Y-m-d H:i:s');
            KeyStorage::create($data);
        }
        $this->result('SUCCESS',null,200);
    }

    /**
     * 获取自定义键值对
     */
    public function get_key_storage(){
        $param= $this->request->param();
        $user_id = $this->auth->id;
        if(empty($param['key'])||!ctype_alpha($param['key'])){
            $this->result('fail');
        }
        $where=[
            'user_id'=>$user_id,
            'key'=>$param['key']
        ];
        $values = KeyStorage::where($where)->value('values');
        $result = [
            'key'=>$param['key'],
            'values'=>$values
        ];
        $this->result('SUCCESS',$result,200);
    }



}

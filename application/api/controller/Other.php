<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/22
 * Time: 15:20
 */

namespace app\api\controller;


use app\common\controller\Api;
use app\common\model\Zuowen;

/**
 * 其余接口
 * Class Other
 * @package app\api\controller
 */
class Other extends Api
{
    protected $noNeedLogin='*';

    protected $noNeedRight='*';

    /**
     * 获取作文列表
     * @throws \think\Exception
     */
    public function get_z_list(){
        $cid = $this->request->post('cid');
        $size = $this->request->post('size');
        $keyword = $this->request->post('keyword');
        $page = $this->request->param('page', 1);
        $pageSize = $this->request->param('page_size', 10);
        if(intval($page)<1) $page=1;
        $offset = ($page-1)*$pageSize;
        $where = [];
        if($cid) $where['cid']=$cid;
        if(intval($size)){
            $size = intval($size);
            $where['cid']=['between',[$size,$size+100]];
        }
        if($keyword){
            $where['name']=['LIKE','%'.$keyword.'%'];
        }
        $total = Zuowen::where($where)->count('id');
        $list = Zuowen::where($where)
            ->order('view','desc')
            ->limit($offset,$pageSize)
            ->column('id,name,size,view,cate,content');
        foreach ($list as $k=>$v){
            $list[$k]['desc'] = mb_substr(strip_tags($v['content']),0,100).'....';
            unset($list[$k]['content']);
        }
        $result =[
            'total'=>$total,
            'currentPage'=>$page,
            'list'=>array_values($list)
        ];
        return $this->result('success',$result,200);
    }

    /**
     * 获取推荐
     */
    public function z_recommend(){
        $zuowen = new Zuowen();
        $list = $zuowen->order('view','desc')
            ->limit(0,10)
            ->column('id,name,size,view,cate,content');
        foreach ($list as $k=>$v){
            $list[$k]['desc'] = mb_substr(strip_tags($v['content']),0,100).'....';
            unset($list[$k]['content']);
        }
        $result =[
            'total'=>10,
            'currentPage'=>1,
            'list'=>array_values($list)
        ];
        return $this->result('success',$result,200);
    }

    /**
     * 详情
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function z_detail(){
        $id = $this->request->post('id');
        $data = Zuowen::get($id);
        Zuowen::where('id',$id)->setInc('view',1);
        $result = [
            'name'=>$data['name'],
            'size'=>$data['size'],
            'content'=>$data['content'],
            'view'=>$data['view'],
            'cate'=>$data['cate'],
        ];
        return $this->result('success',$result,200);
    }

}
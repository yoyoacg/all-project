<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/29
 * Time: 11:23
 */

namespace app\api\controller;


use app\common\controller\Api;
use app\common\model\Spot as SpotModel;
use app\common\model\ViewComment;
use app\common\model\ViewLove;
use app\common\model\ViewMood;


/**
 * 景点
 * Class Sport
 * @package app\api\controller
 */
class Sport extends Api
{
    protected $noNeedLogin = ['get_banner','get_list','detail',
        'recommend','get_comment','get_recomment','get_mood','get_nearby'];

    protected $noNeedRight='*';

    /**
     * 检测收藏
     * @param $data
     * @param $cate
     * @return array
     */
    protected function is_recommend($data, $cate)
    {
        if ($this->auth->id) {
            $where = [
                'user_id' => $this->auth->id,
                'cate' => $cate
            ];
            $list = ViewLove::where($where)->column('spot_id');
            if (is_array($data)) {
                foreach ($data as $k => $v) {
                    if ($list && in_array($v['id'], $list)) {
                        $v['isLove'] = 1;
                    } else {
                        $v['isLove'] = 0;
                    }
                    $data[$k] = $v;
                }
                return $data;
            }
            if (is_string($data) || is_numeric($data)) {
                $result = [];
                if ($list && in_array($data, $list)) {
                    $result['isLove'] = 1;
                } else {
                    $result['isLove'] = 0;
                }
                return $result;
            }
        } else {
            if (is_array($data)) {
                foreach ($data as $k => $v) {
                    $data[$k]['isLove'] = 0;
                }
                return $data;
            }
            if (is_string($data)) {
                return [
                    'isLove' => 0,
                ];
            }
        }
    }

    /**
     * 获取轮播图
     */
    public function get_banner()
    {
        $list = $this->getBannerList(2);
        $this->result('success', $list, 200);
    }

    /**
     * 获取列表
     * @throws \think\Exception
     */
    public function get_list()
    {
        $keyword = $this->request->post('keyword', '');
        $type = $this->request->post('type', '景点');
        $page = $this->request->param('page', 1);
        $is_hot = $this->request->post('hot', 0);
        $pageSize = $this->request->param('page_size', 10);
        $lat = $this->request->post('lat','');
        $lon = $this->request->post('lon','');
        if (intval($page) < 1) $page = 1;
        $offset = ($page - 1) * intval($pageSize);
        $where = [
            'city' => '成都',
            'type' => $type
        ];
        $order = 'love';
        if ($is_hot) {
            $order = 'views';
        }
        if (!empty($keyword)) {
            $where['name'] = ['like', '%' . $keyword . '%'];
        }
        $total = SpotModel::where($where)->count('id');
        $list = SpotModel::Where($where)
            ->order($order, 'desc')
            ->limit($offset, $pageSize)
            ->column('id,name,cover,imgs,content,tel,address,lon,lat,comment,price,views,love');
        foreach ($list as $k=>$v){
            $v['content'] = mb_substr(strip_tags($v['content']), 1, 50);
            $v['imgs'] = explode(',', $v['imgs']);
            if(!empty($lon)&&!empty($lat)){
                $s = ceil($this->getDistance($lat,$lon,$v['lat'],$v['lon']));
                if($s<=1000){
                    $s = $s.'m';
                }else{
                    $s = number_format($s/1000,1).'km';
                }
                $v['distance'] =$s;
            }else{
                $v['distance'] = '';
            }
            $list[$k]= $v;
        }
        $list = $this->is_recommend($list, 'view');
        $result = [
            'total' => $total,
            'currentPage' => $page,
            'list' => array_values($list)
        ];
        $this->result('success', $result, 200);
    }

    /**
     * 详情
     * @throws \think\Exception
     * @throws \think\exception\DbException\
     */
    public function detail()
    {
        $id = $this->request->post('id');
        $lat = $this->request->post('lat','');
        $lon = $this->request->post('lon','');
        if (empty($id)) $this->error('缺少参数');
        $data = SpotModel::get(intval($id));
        if ($data) {
            $result = [
                'id' => $data['id'],
                'name' => $data['name'],
                'imgs' => explode(',', $data['imgs']),
                'content' => strip_tags($data['content']),
                'tel' => $data['tel'],
                'address' => $data['address'],
                'lon' => $data['lon'],
                'lat' => $data['lat'],
                'imglist' => explode(',', $data['imglist']),
                'comment' => $data['comment'],
                'price' => $data['price'],
                'views' => $data['views'],
                'love' => $data['love'],
                'jt' => strip_tags($data['jt']),
                'open' => strip_tags($data['open']),
                'ticket' => strip_tags($data['ticket']),
            ];
            if(!empty($lon)&&!empty($lat)){
                $s = ceil($this->getDistance($lat,$lon,$data['lat'],$data['lon']));
                if($s<=1000){
                    $s = $s.'m';
                }else{
                    $s = number_format($s/1000,1).'km';
                }
                $result['distance'] =$s;
            }else{
                $result['distance'] = '';
            }
            SpotModel::where('id', $data['id'])->setInc('views', 1);
            $res = $this->is_recommend($data['id'], 'view');
            $this->result('success', array_merge($result, $res), 200);
        } else {
            $this->error('参数错误');
        }
    }


    /**
     * 获取推荐
     * @throws \think\exception\DbException
     */
    public function recommend()
    {
        $id = $this->request->post('id');
        if (empty($id)) $this->error('缺少参数');
        $data = SpotModel::get(intval($id));
        $where = [
            'type' => $data['type']
        ];
        $list = SpotModel::Where($where)
            ->order('love', 'desc')
            ->limit(0, 10)
            ->column('id,name,cover,imgs,content,tel,address,lon,lat,comment,price,views,love');
        $list = array_map(function ($val) {
            $val['content'] = mb_substr(strip_tags($val['content']), 1, 50);
            $val['imgs'] = explode(',', $val['imgs']);
            return $val;
        }, $list);
        $list = $this->is_recommend($list, 'view');
        $this->result('success', array_values($list), 200);
    }

    /**
     * 添加评论
     * @throws \think\Exception
     */
    public function add_comment()
    {
        $user_id = $this->auth->id;
        $content = $this->request->post('content');
        $view_id = $this->request->post('view_id');
        if (empty($user_id) || empty($content) || empty($view_id)) $this->error('缺少参数');
        $data = [
            'user_id' => $user_id,
            'content' => $content,
            'spot_id' => $view_id,
            'create_time' => date('Y-m-d H:i:s'),
        ];
        if (ViewComment::create($data)) {
            SpotModel::where('id', $view_id)->setInc('comment', 1);
            $this->result('success', null, 200);
        } else {
            $this->error('请稍后再试');
        }
    }

    /**
     * 获取评论
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_comment()
    {
        $view_id = $this->request->post('view_id', '');
        $user_id = $this->auth->id;
        $page = $this->request->param('page', 1);
        $pageSize = $this->request->param('page_size', 10);
        if (intval($page) < 1) $page = 1;
        $offset = ($page - 1) * intval($pageSize);
        $order = 'create_time';
        $sort = 'desc';
        $where = [];
        if (!empty($view_id)) {
            $where['spot_id'] = intval($view_id);
        } else {
            if ($user_id) {
                $where['user_id'] = $user_id;
            } else {
                $this->error('缺少参数');
            }
        }
        $total = ViewComment::where($where)->count('id');
        $list = ViewComment::with(['user', 'spot'])
            ->where($where)
            ->order($order, $sort)
            ->limit($offset, intval($pageSize))
            ->select();
        $list = collection($list)->toArray();
        foreach ($list as $k => $v) {
            $list[$k] = $this->cUL($v, false);
        }
        $list = $this->is_recommend($list, 'comment');
        $result = [
            'total' => $total,
            'currentPage' => $page,
            'list' => $list
        ];
        $this->result('success', $result, 200);
    }

    /**
     * 获取热门评论
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_recomment()
    {
        $page = $this->request->param('page', 1);
        $pageSize = $this->request->param('page_size', 10);
        if (intval($page) < 1) $page = 1;
        $offset = ($page - 1) * intval($pageSize);
        $where = [];
        $total = ViewComment::where($where)->count('id');
        $list = ViewComment::with(['user', 'spot'])
            ->where($where)
            ->order('create_time,love', 'DESC')
            ->limit($offset, intval($pageSize))
            ->select();
        $list = collection($list)->toArray();
        foreach ($list as $k => $v) {
            $list[$k] = $this->cUL($v, false);
        }
        $list = $this->is_recommend($list, 'comment');
        $result = [
            'total' => $total,
            'currentPage' => $page,
            'list' => $list
        ];
        $this->result('success', $result, 200);
    }

    /**
     * 添加心情
     */
    public function add_mood()
    {
        $user_id = $this->auth->id;
        $content = $this->request->post('content', '');
        $img = $this->request->post('img');
        if (empty($content)) $this->error('缺少参数');
        $data = [
            'user_id' => $user_id,
            'content' => mb_substr($content, 0, 250),
            'img' => $img,
            'create_time' => date('Y-m-d H:i:s')
        ];
        if (ViewMood::create($data)) {
            $this->result('success', null, 200);
        } else {
            $this->error('请稍后再试');
        }
    }

    /**
     * 获取心情
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_mood()
    {
        $user_id = $this->auth->id;
        $type = $this->request->post('type', 'all');
        $page = $this->request->param('page', 1);
        $pageSize = $this->request->param('page_size', 10);
        if (intval($page) < 1) $page = 1;
        $offset = ($page - 1) * intval($pageSize);
        $order = 'create_time';
        $sort = 'desc';
        $where = [];
        if ($type == 'my' && !$user_id) {
            if ($user_id) {
                $where['user_id'] = $user_id;
            } else {
                $this->error('请登录', '', 401);
            }
        }
        $total = ViewMood::where($where)->count('id');
        $list = ViewMood::with(['user'])->where($where)
            ->order($order, $sort)
            ->limit($offset, $pageSize)
            ->select();
        $list = collection($list)->toArray();
        foreach ($list as $k => $v) {
            $list[$k] = $this->cUL($v, false);
        }
        $list = $this->is_recommend($list, 'mood');
        $result = [
            'total' => $total,
            'currentPage' => $page,
            'list' => $list
        ];
        $this->result('success', $result, 200);
    }

    /**
     * 我的相册
     */
    public function my_photo()
    {
        $user_id = $this->auth->id;
        $list = ViewMood::where('user_id', $user_id)
            ->order('create_time', 'DESC')
            ->column('img');
        $res = [];
        foreach ($list as $k => $v) {
            $arr = explode(',', $v);
            $res = array_merge($res, $arr);
        }
        $this->result('success', $res, 200);
    }

    /**
     * 喜欢
     * @throws \think\Exception
     */
    public function loves()
    {
        $id = $this->request->post('id', '');
        $user_id = $this->auth->id;
        $type = $this->request->post('type', '');
        if (empty($id) || empty($type)) $this->error('缺少参数');
        $is_check = ViewLove::where(['user_id' => $user_id, 'spot_id' => $id, 'cate' => $type])->value('id');
        if ($is_check) $this->error('请忽重复点击');
        if ($type == 'mood') {
            ViewMood::where('id', $id)->setInc('love', 1);
        } elseif ($type == 'comment') {
            ViewComment::where('id', $id)->setInc('love', 1);
        } else {
            SpotModel::where('id', $id)->setInc('love', 1);
        }
        $data = [
            'user_id' => $user_id,
            'spot_id' => $id,
            'create_time' => date('Y-m-d H:i:s'),
            'cate' => $type
        ];
        ViewLove::create($data);
        $this->result('success', null, 200);
    }

    /**
     * 附近
     * @throws \think\Exception
     */
    public function get_nearby(){
        $keyword = $this->request->post('keyword');
        $page = $this->request->param('page', 1);
        $pageSize = $this->request->param('page_size', 10);
        if (intval($page) < 1) $page = 1;
        $offset = ($page - 1) * intval($pageSize);
        $where=[];
        if($keyword){
            $where['name']=['LIKE','%'.$keyword.'%'];
        }
        $total = SpotModel::where($where)->count('id');
        $list = SpotModel::Where($where)
            ->order('views', 'desc')
            ->limit($offset, $pageSize)
            ->column('id,name,cover,imgs,content,tel,address,lon,lat,comment,price,views,love');
        foreach ($list as $k=>$v){
            $v['content'] = mb_substr(strip_tags($v['content']), 1, 50);
            $v['imgs'] = explode(',', $v['imgs']);
            if(!empty($lon)&&!empty($lat)){
                $s = ceil($this->getDistance($lat,$lon,$v['lat'],$v['lon']));
                if($s<=1000){
                    $s = $s.'m';
                }else{
                    $s = number_format($s/1000,1).'km';
                }
                $v['distance'] =$s;
            }else{
                $v['distance'] = '';
            }
            $list[$k]= $v;
        }
        $list = $this->is_recommend($list, 'view');
        $result = [
            'total' => $total,
            'currentPage' => $page,
            'list' => array_values($list)
        ];
        $this->result('success', $result, 200);
    }

    public function user_info(){
        $user_id = $this->auth->id;
        $comment_count = ViewComment::where('user_id',$user_id)->count('id');
        $love_count= ViewLove::where('user_id',$user_id)->count('id');
//        $user = \app\common\model\User::get($user_id);
        $result = [
            'comment'=>$comment_count,
            'love'=>$love_count,
        ];
        $this->result('success', $result, 200);
    }

    public function get_love(){
        $user_id = $this->auth->id;
        $page = $this->request->param('page', 1);
        $pageSize = $this->request->param('page_size', 10);
        if (intval($page) < 1) $page = 1;
        $offset = ($page - 1) * intval($pageSize);
        $ViewLove = new ViewLove();
        $count = $ViewLove->where('user_id',$user_id)->count('id');
        $list = $ViewLove->where('user_id',$user_id)
            ->order('create_time','DESC')
            ->limit($offset,$pageSize)
            ->column('id,spot_id,create_time,cate');
        foreach ($list as $k=>$v){
            if($v['cate']=='view'){
                $res = $ViewLove->getView($v['spot_id']);
            }elseif ($v['cate']=='mood'){
                $res = $ViewLove->getMood($v['spot_id']);
            }else{
                $res = $ViewLove->getComment($v['spot_id']);
            }
            $v = array_merge($v,$res);
            $list[$k]=$this->cUL($v,false);
        }
        $result = [
            'total' => $count,
            'currentPage' => $page,
            'list' => array_values($list)
        ];
        $this->result('success', $result, 200);
    }


}
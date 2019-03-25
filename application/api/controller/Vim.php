<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/21
 * Time: 13:35
 */

namespace app\api\controller;


use app\common\controller\Api;
use app\common\model\VimContact;
use app\common\model\VimUserinfo;
use think\Db;

class Vim extends Api
{
    protected $noNeedLogin = [];

    protected $noNeedRight = '*';

    /**
     * 添加成员
     */
    public function add_member()
    {
        $data = $this->request->post();
        $user_id = $this->auth->id;
        if (empty($data['name'])) $this->error('请输入姓名');
        if (empty($data['mobile']) || !\think\Validate::regex($data['mobile'], '/^1\d{10}$/')) $this->error('请输入手机号码');
        $add_data = [
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'user_id' => $user_id,
            'driver' => $data['driver'] ?? '',
            'driver_age' => $data['driver_age'] ?? '',
            'license' => $data['license'] ?? '',
            'create_time' => date('Y-m-d H:i:s')
        ];
        if (isset($data['age']) && !empty($data['age'])) {
            $time = intval($data['age']);
            if ($time > 0) {
                $add_data['birthday'] = date('Y-m-d', strtotime('-' . $time . ' year'));
            }
        }
        $index = $this->getFirstCharter(trim($data['name']));
        if (empty($index)) {
            $index = strtoupper(substr(trim($data['name']), 0, 1));
        }
        $add_data['indexes'] = $index;
        if (VimContact::create($add_data)) {
            $this->result('success', null, 200);
        } else {
            $this->error('fail');
        }
    }

    /**
     * 获取列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_list()
    {
        $user_id = $this->auth->id;
        $where = [
            'c.user_id' => $user_id,
        ];
        $list = $this->getStatusList($where);
        $this->result('success', $list, 200);
    }

    /**
     * 个人设置
     * @throws \think\exception\DbException
     */
    public function set()
    {
        $user_id = $this->auth->id;
        $mobile = $this->request->post('mobile');
        $name = $this->request->post('name');
        $driver = $this->request->post('driver');
        $driver_age = $this->request->post('driver_age');
        $lon = $this->request->post('lon');
        $lat = $this->request->post('lat');
        $age = $this->request->post('age');
        $status = $this->request->post('status', 1);
        $data = [];
        if ($mobile) $data['mobile'] = $mobile;
        if ($name) $data['name'] = $name;
        if ($driver) $data['driver'] = $driver;
        if ($driver_age) $data['driver_age'] = $driver_age;
        if ($lon) $data['lon'] = $lon;
        if ($lat) $data['lat'] = $lat;
        if ($status) $data['status'] = intval($status);
        if ($age) {
            $time = intval($age);
            if ($time > 0) {
                $data['birthday'] = date('Y-m-d', strtotime('-' . $time . ' year'));
            }
        }
        if (count($data) > 0) {
            $is_check = VimUserinfo::get(['user_id' => $user_id]);
            if ($is_check) {
                $data['id'] = $is_check['id'];
                $data['update_time'] = date('Y-m-d H:i:s');
                VimUserinfo::update($data);
                $this->result('success', null, 200);
            } else {
                $data['user_id'] = $user_id;
                $data['create_time'] = date('Y-m-d H:i:s');
                $data['update_time'] = date('Y-m-d H:i:s');
                VimUserinfo::create($data);
                $this->result('success', null, 200);
            }
        } else {
            $this->error('参数错误');
        }
    }

    /**
     * 获取个人信息
     * @throws \think\exception\DbException
     */
    public function userinfo()
    {
        $user_id = $this->auth->id;
        $data = VimUserinfo::get(['user_id' => $user_id]);
        $result = [
            'name' => $data['name'] ?? '',
            'mobile' => $data['mobile'] ?? '',
            'driver' => $data['driver'] ?? '',
            'driverAge' => $data['driver_age'] ?? '',
            'status' => $data['status'] ?? 1
        ];
        if (empty($data['birthday'])) {
            $result['age'] = '';
        } else {
            $result['age'] = (string)ceil((time() - strtotime($data['birthday'])) / (365 * 24 * 60 * 60));
        }
        $this->result('success', $result, 200);
    }

    /**
     * 首页数据
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $user_id = $this->auth->id;
        $total = VimContact::where('user_id', $user_id)->count('id');
        $list = $this->getStatusList(['c.user_id' => $user_id]);
        $normal_list = $this->getStatusList(['c.user_id' => $user_id, 'u.status' => 1]);
        $repair_list = $this->getStatusList(['c.user_id' => $user_id, 'u.status' => 2]);
        $Standby_list = $this->getStatusList(['c.user_id' => $user_id, 'u.status' => 3]);
        $result = [
            'total' => [
                'total' => $total,
                'list' => $list
            ],
            'normal' => [
                'total' => count($normal_list),
                'list' => $normal_list
            ],
            'repair' => [
                'total' => count($repair_list),
                'list' => $repair_list
            ],
            'standby' => [
                'total' => count($Standby_list),
                'list' => $Standby_list
            ],
        ];
        $this->result('success', $result, 200);
    }

    /**
     * 获取列表
     * @param array $where
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function getStatusList($where = [])
    {
        $list = Db::view('vim_contact c', 'id,name,birthday age,mobile,driver,driver_age driverAge,license,indexes')
            ->view('vim_userinfo u', 'lon,lat,status', 'c.mobile=u.mobile', 'LEFT')
            ->where($where)
            ->order('c.indexes', 'ASC')
            ->select();
        foreach ($list as $k => $v) {
            if (empty($v['age'])) {
                $v['age'] = '';
            } else {
                $v['age'] = ceil((time() - strtotime($v['age'])) / (365 * 24 * 60 * 60));
            }
            $v['lon'] = $v['lon'] ?? '';
            $v['lat'] = $v['lat'] ?? '';
            $v['status'] = $v['status'] ?? 1;
            $list[$k] = $v;
        }
        return $list;
    }

}
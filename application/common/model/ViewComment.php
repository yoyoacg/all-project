<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/29
 * Time: 13:39
 */

namespace app\common\model;


use think\Model;

class ViewComment extends Model
{
    protected $table='view_comment';

    public function user(){
        return $this->belongsTo('User','user_id','id')->bind('nickname,avatar');
    }

    public function spot(){
        return $this->belongsTo('Spot','spot_id','id')->bind('name,cover');
    }

}
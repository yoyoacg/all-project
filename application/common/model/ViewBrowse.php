<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/3
 * Time: 17:23
 */

namespace app\common\model;


use think\Model;

class ViewBrowse extends Model
{
    protected $table='view_browse';

    public function spot(){
        return $this->belongsTo('Spot','spot_id','id')->bind('name,cover,content');
    }
}
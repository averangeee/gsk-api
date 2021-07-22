<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/15
 * Time: 14:17
 */

namespace App\Models\Shop;


use App\Models\BaseModel;

class Shop extends BaseModel
{
    protected $table='shop';

    protected $guarded=[];

    public function children()
    {
        return $this->hasMany(self::Class,'parent_id','id');
    }
    public function manager()
    {
        return $this->hasMany(ShopManager::class,'shop_id','shop_id');
    }

    //反向
    public  function getLevel($id,$select=['id','parent_id','code','name'])
    {
        $where=function ($query) use($id){
            if(!empty($id) && count($id)>0){
                $query->whereIn('id',$id);
            }
        };
        $sourceItems = $this->where($where)->orderBy('name')->get($select);
        return $this->getLevelInfo($sourceItems->toArray(),  0);
    }
    public function getLevelInfo($data,$pid = 0)
    {
        $arr=[];
        foreach ($data as $k=>$v) {
            if ($v['parent_id'] == $pid) {
                $child=self::getLevelInfo($data,  $v['id']);
                if($child){
                    $v['children']= $child;
                }
                $arr[] = $v;
            }
        }
        return $arr;
    }

}
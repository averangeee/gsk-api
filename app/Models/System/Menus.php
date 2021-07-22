<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/4/28
 * Time: 14:02
 */

namespace App\Models\System;


use App\Models\BaseModel;

class Menus extends BaseModel
{
    protected $table='menus';

    protected $guarded=[];

    protected $casts=[
        'is_show'=>'bool',
        'is_sys'=>'bool',
    ];

    public function childMaster()
    {
        return $this->hasMany(Menus::CLASS, 'parent_id', 'id');
    }

    public function children()
    {
        return $this->childMaster()->with('children');
    }

    public function child()
    {
        return $this->childMaster()->with(['child'=>function($q){
            $q->where('is_show',1)->select(['id','parent_id','menu_code','menu_name']);
        }]);
    }

    public static function getLevel($id,$pid,$clientType,$select=['id','menu_code','menu_name','parent_id','menu_level','client_type','menu_type','menu_icon','menu_url'])
    {
        $where=function ($query) use($id,$clientType,$pid){
            $query->where('parent_id',$pid);
            $query->where('is_show',1);
            if(count($id)>0){
                $query->whereIn('id',$id);
            }
            if(!empty($clientType)){
                $query->where('client_type',$clientType);
            }
        };
        $sourceItems =self::where($where)
            ->orderBy('menu_sort')
            ->with(['children'=>function($q)use($select){
                $q->where('is_show',1)->select($select);
            }])
            ->get($select);

        return $sourceItems->toArray();
    }

    public function getLevelInfo($data,$pid = 1)
    {
        $arr=[];
        foreach ($data as $k=>$v) {
            if ($v['parent_id'] == $pid) {
                $children=self::getLevelInfo($data,  $v['id']);
                if($children){
                    $v['children']= $children;
                }
                $arr[] = $v;
            }
        }
        return $arr;
    }

}
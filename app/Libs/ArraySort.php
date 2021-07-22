<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/4/23
 * Time: 19:12
 */

namespace App\Libs;


class ArraySort
{
    public static function sort($data)
    {
        $sort = array();
        if(!is_array($data)){
            $data=$data->toArray();
        }
        foreach ($data as $item){
            $sort[]=[
                $item['project_region']?$item['project_region']['region_id']:null
            ];
        }
        array_multisort($sort,SORT_ASC ,$data);
        return $data;
    }
}
<?php

use Illuminate\Http\Request;

//GSK 测试
Route::group(['middleware' => ['token', 'api.log'], 'namespace' => 'WebApi', 'prefix' => 'cloud'], function () {
    Route::get('/system/employee/list', 'EmployeeController@index');  //用户列表
    Route::post('/system/employee/create', 'EmployeeController@create'); //创建用户
    Route::post('/system/employee/edit', 'EmployeeController@edit'); //修改用户
    Route::post('/system/employee/delete', 'EmployeeController@delete'); //删除用户
    Route::post('/system/employee/changePwd', 'EmployeeController@changePwd'); //修改密码

    Route::post('/system/employee/roleList', 'RoleController@index'); //权限列表
    Route::post('/system/employee/role/add', 'RoleController@add'); //增加权限
    Route::post('/system/employee/role/edit', 'RoleController@edit'); //编辑权限
    Route::post('/system/role/delete', 'RoleController@delete'); //删除权限

    Route::post('/system/menus/List', 'MenusController@index'); //菜单列表
    Route::post('/system/menus/add', 'MenusController@add'); //增加菜单
    Route::post('/system/menus/edit', 'MenusController@edit'); //编辑菜单
    Route::post('/system/menus/delete', 'MenusController@delete'); //删除菜单

    Route::post('/ck/List', 'DeviceController@ck_list'); //仓库列表
    Route::post('/ck/add', 'DeviceController@ck_add'); //增加仓库
    Route::post('/ck/edit', 'DeviceController@ck_edit'); //编辑仓库
    Route::post('/ck/delete', 'DeviceController@ck_delete'); //删除仓库

    Route::post('/device/List', 'DeviceController@device_list'); //设备列表
    Route::post('/device/add', 'DeviceController@device_add'); //增加设备
    Route::post('/device/edit', 'DeviceController@device_edit'); //编辑设备
    Route::post('/device/delete', 'DeviceController@device_delete'); //删除仓库

    Route::post('/allocation/list', 'DeviceController@allocation_list'); //调拨列表
    Route::post('/allocation/listInfo', 'DeviceController@allocation_Infolist'); //调拨记录详情
    Route::post('/weixiulog/list', 'DeviceController@weixiu_log_list'); //维修记录
    Route::post('/weixiulog/listInfo', 'DeviceController@weixiu_Infolist'); //维修记录详情
    Route::post('/getCity/list', 'DeviceController@city_list'); //获取城市列表
    Route::post('/getmanager/list', 'EmployeeController@manager_list'); //获取负责人列表
    Route::post('/brand/list', 'DeviceController@brand_list'); //获取品牌列表
    Route::post('/brand/add', 'DeviceController@brand_add'); //新增品牌
    Route::post('/brand/edit', 'DeviceController@brand_edit'); //编辑品牌
    Route::post('/brand/delete', 'DeviceController@brand_delete'); //删除品牌
    Route::post('/get/maps', 'DeviceController@get_map_list'); //获取地图数据
    Route::post('/province/list', 'DeviceController@province_list');  //获取省份列表
    Route::post('/city/province/list', 'DeviceController@city_by_province');  //根据省份获取城市

    Route::post('/tj/byarea', 'DeviceController@tjbyarea');  //区域分布
    Route::post('/tj/bychannel', 'DeviceController@tjbychannel');  //渠道分布
    Route::post('/tj/bystatus', 'DeviceController@tjbystatus');  //状态统计
    Route::post('/tj/bycase', 'DeviceController@tjbycase');  //故障原因占比
    Route::post('/tj/bycase/count', 'DeviceController@tjbycase_count');  //故障次数分布
    Route::post('/resetbf', 'DeviceController@resetbf');  //手动置为报废
    Route::post('/get/wx/Result', 'DeviceController@result_percent');  //维修成功失败占比
});
//后台登录接口
Route::post('/login', 'WebApi\LoginController@login');
Route::post('/import/device/info', 'WebApi\DeviceController@export_device');  //导入设备
Route::post('/cloud/import/employee/info', 'WebApi\EmployeeController@export_employee');  //导入人员

//小程序登录接口
Route::group(['middleware' => [], 'namespace' => 'AndroidApi', 'prefix' => 'mini'], function () {
    Route::post('/sys/login', 'SystemController@login');//系统登录
    Route::post('/upload/img', 'ProductController@file_exists_S3');  //上传图片
});
//小程序接口
Route::group(['middleware' => ['token', 'api.log'], 'namespace' => 'AndroidApi', 'prefix' => 'mini'], function () {
    Route::post('/device/list', 'ProductController@product');  //设备列表
    Route::post('/device/gsk_code_list', 'ProductController@gsk_code_list');  //设备编码
    Route::post('/update/password', 'SystemController@changePwd');  //个人中心修改密码
    Route::post('/reset/password', 'SystemController@resetPwd');  //个人中心重置密码
    Route::post('/system/operater_list', 'SystemController@operater_list');  //个人中心执行方列表
    Route::post('/system/operater/add', 'SystemController@create');  //个人中心新增执行方
    Route::post('/system/operater/edit', 'SystemController@edit');  //个人中心编辑执行方
    Route::post('/system/operater/stop', 'SystemController@stop_user');  //个人中心冻结执行方
    Route::post('/system/operater/restart', 'SystemController@restart_user');  //个人中心解封执行方
    Route::post('/system/reset/manager', 'SystemController@manager_reset');  //个人中心仪器负责人变更
    Route::post('/system/ruku/deviceInfo', 'SystemController@device_list');  //个人中心仪器入库列表详情
    Route::post('/system/ruku/save', 'SystemController@save');  //个人中心仪器入库保存
    Route::post('/product/ck/list', 'ProductController@ck_list');  //仓库列表
    Route::post('/product/city/list', 'ProductController@city_list');  //城市列表
    Route::post('/quyu/allocation/apply', 'ProductController@apply_allocation');  //区域调拨申请
    Route::post('/zhixing/allocation/info', 'ProductController@allocation_info');  //执行公司收到调拨申请信息渲染
    Route::post('/zhixing/allocation/accept', 'ProductController@operater_company_check');  //执行公司接收
    Route::post('/quyu/allocation/allocation_list', 'ProductController@allocation_list');  //区域调拨记录
    Route::post('/zhixing/allocation/apply', 'ProductController@operater_company_allocation');  //执行公司调拨
    Route::post('/zhixing/replay/device', 'ProductController@operater_company_replay');  //执行公司归还
    Route::post('/zhixing/weixiu/apply', 'ProductController@operater_company_report');  //执行公司保修申请
 //   Route::post('/weixiu/device/list', 'ProductController@weixiu_company_report');  //维修公司仪器列表
    Route::post('/alluser/list', 'SystemController@alluser_list');  //区域/负责人列表
    Route::post('/province/list', 'ProductController@province_list');  //获取省份列表
    Route::post('/quyu/baoxiu/submit', 'ProductController@quyu_apply_report');  //区域提交报修
    Route::post('/quyu/baoxiu/add', 'ProductController@quyu_report');  //区域新增报修
    Route::post('/quyu/weixiu/history', 'ProductController@weixiu_history_list');  //区域维修记录历史记录
    Route::post('/quyu/weixiu/history/Info', 'ProductController@nowdate_history_info');  //区域最新维修记录及详情
    Route::post('/quyu/device/back', 'ProductController@device_back_ck_bak');  //区域仪器到仓
    Route::post('/quyu/index', 'ProductController@index');  //区域首页
    Route::post('/zhixing/index', 'ProductController@zhixing_index');  //执行公司首页
    Route::post('/quyu/applyweixiu', 'ProductController@quyu_report');  //区域保修申请
    Route::post('/weixiu/index', 'ProductController@weixiu_index');  //维修中心首页
    Route::post('/weixiu/check', 'ProductController@weixiu_company_check');  //维修公司维修
    Route::post('/quyu/biangeng/msg', 'SystemController@biangeng_msg');  //区域一起负责人变更设备进去消息列表
    Route::post('/quyu/bg/check', 'SystemController@accept_check');  //区域变更接收人确认
    Route::post('/weixiu/device/list', 'SystemController@weixiu_device_list');  //维修公司仪器列表
    Route::post('/quyu/weixiu/getInfo', 'SystemController@quyu_weixiu_Infolist');  //区域维修获取详情
    Route::post('/quyu/deviceweixiu/getInfo', 'SystemController@get_weixiu_Info');  //区域管理维修详情
    Route::post('/quyu/weixiu/get/detail', 'SystemController@get_weixiu_detail');  //区域管理维修详情(另写接口)

    Route::post('/quyu/devic/list/getInfo', 'SystemController@get_list_Info');  //区域仪器列表详情
    Route::post('/zhixing/alldevice', 'SystemController@zx_device_list');  //执行公司设备列表
    Route::post('/weixiu/replay/device', 'SystemController@weixiu_company_replay');  //维修公司归还
    Route::post('/weixiu/index/device', 'ProductController@weixiu_index');  //维修公司首页
    Route::post('/search/gsk_code', 'ProductController@search_gsk_code');  //通过骨仪器查gsk
    Route::post('/getall/gsk_code', 'SystemController@getall_status_code');  //不同页面获取gsk_code
    Route::post('/get/manger/list', 'SystemController@alluser_list_yqmanger');  //获取仪器负责人
    Route::post('/get/message/tz', 'SystemController@message_list');  //一起变更消息通知
    Route::post('/get/resetmessage/info', 'SystemController@message_list_info');  //变更消息详情
    Route::post('/get/ck/info', 'SystemController@get_ck_info');  //送回所属仓库
    Route::post('/get/wxsuccess/info', 'SystemController@get_wxsuccess_info');  //维修人员查看维修记录
    Route::post('/get/db/quyu/escape', 'SystemController@quyu_escape');  //区域调拨状态下取消按钮
    Route::post('/get/zxf/ListInfo', 'SystemController@zxfList');  //执行方查看更多消息
    Route::post('/get/wx/ListWx', 'SystemController@wxList');  //维修方查看更多信息



});


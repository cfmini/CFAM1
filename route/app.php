<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

Route::get('loginCaptcha','index/loginCaptcha');
Route::get('registerCaptcha','index/registerCaptcha');
Route::get('Adminlogin', 'index/Adminlogin');

Route::get('shop', 'index/shop');
Route::get('login', 'index/login');
Route::get('BanChinaName', 'index/BanChinaName');
Route::get('register', 'index/register');
Route::get('feedback', 'index/feedback');
Route::get('retrieve', 'index/retrieve');
Route::get('send', 'index/send');
Route::get('activity', 'index/activity');
Route::get('news', 'index/news');
Route::get('article/:id', 'index/article');
Route::get('/manage/editNews/:id', 'manage/editNews');
Route::get('/manage/editAct/:id', 'manage/editAct');
Route::post("/Admin/AjaxEditTask/:id", 'admin/AjaxEditTask');
Route::post("/Admin/AjaxMissTask/:id", 'admin/AjaxMissTask');
Route::post("/Admin/AjaxEditDarw/:id", 'admin/AjaxEditDarw');
Route::post("/Admin/AjaxGROUPTask/:id", 'admin/AjaxGROUPTask');
Route::delete("Admin/delLog", 'Admin/delLog');

Route::get("manage/showNewCdkey/:key", 'manage/showNewCdkey');


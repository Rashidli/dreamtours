<?php
namespace App\Http\Controllers\Admin;use App\Http\Controllers\Controller;use App\Models\Setting;use Illuminate\Http\{RedirectResponse,Request};use Illuminate\View\View;
class SettingController extends Controller{
 public function __construct(){$this->middleware('permission:list-settings')->only('index');$this->middleware('permission:edit-settings')->only('update');}
 public function index(Request $r):View{$settings=Setting::query()->when($r->filled('q'),fn($q)=>$q->where('key','like','%'.$r->q.'%')->orWhere('value','like','%'.$r->q.'%'))->orderBy('key')->paginate(50)->withQueryString();return view('admin.settings.index',compact('settings'));}
 public function update(Request $r):RedirectResponse{$d=$r->validate(['settings'=>['required','array'],'settings.*'=>['nullable','string','max:10000']]);foreach($d['settings'] as $key=>$value){abort_unless(preg_match('/^[a-z0-9_]+$/',$key),422);Setting::set($key,$value);}return back()->with('message','Settings updated.');}
}

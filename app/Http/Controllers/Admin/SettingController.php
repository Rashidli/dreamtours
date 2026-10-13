<?php
namespace App\Http\Controllers\Admin;use App\Http\Controllers\Controller;use App\Http\Controllers\Admin\Concerns\HandlesMediaUploads;use App\Models\Setting;use Illuminate\Http\{RedirectResponse,Request};use Illuminate\View\View;
class SettingController extends Controller{
 use HandlesMediaUploads;
 public function __construct(){$this->middleware('permission:list-settings')->only('index');$this->middleware('permission:edit-settings')->only('update');}
 public function index():View{return view('admin.settings.index',['settings'=>Setting::orderBy('key')->get()]);}
 public function update(Request $r):RedirectResponse{
  $d=$r->validate([
   'settings'=>['required','array'],
   'settings.*'=>['nullable','string','max:10000'],
   'setting_file'=>['nullable','array'],
   'setting_file.*'=>['nullable','file','mimes:png,jpg,jpeg,webp,svg,ico','max:4096'],
  ]);
  foreach($d['settings'] as $key=>$value){
   abort_unless(preg_match('/^[a-z0-9_]+$/',$key),422);
   if($r->hasFile("setting_file.$key")){
    $file=$r->file("setting_file.$key");
    // SVG logos are stored as-is; raster logos go through the WebP pipeline.
    $raw=in_array($key,\App\Support\SettingLabels::MEDIA_RAW,true)||strtolower($file->getClientOriginalExtension())==='svg';
    $value=$raw?$this->media()->uploadFile($file):$this->media()->upload($file);
   }
   Setting::set($key,$value);
  }
  return back()->with('message','Settings updated.');
 }
}

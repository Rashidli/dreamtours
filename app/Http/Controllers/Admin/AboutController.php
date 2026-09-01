<?php
namespace App\Http\Controllers\Admin;use App\Http\Controllers\Controller;use App\Models\About;use Illuminate\Http\{RedirectResponse,Request};use Illuminate\Support\Facades\Storage;use Illuminate\View\View;
class AboutController extends Controller{
 public function __construct(){$this->middleware('permission:list-abouts')->only(['index','show']);$this->middleware('permission:create-abouts')->only(['create','store']);$this->middleware('permission:edit-abouts')->only(['edit','update']);$this->middleware('permission:delete-abouts')->only('destroy');}
 public function index(Request $r):View{$abouts=About::with('translations')->when($r->filled('q'),fn($q)=>$q->where('key','like','%'.$r->q.'%')->orWhereTranslationLike('title','%'.$r->q.'%'))->latest()->paginate(20)->withQueryString();return view('admin.abouts.index',compact('abouts'));}
 public function create():View{return view('admin.abouts.form',['about'=>new About]);}
 public function store(Request $r):RedirectResponse{About::create($this->data($r));return redirect()->route('abouts.index')->with('message','About content created.');}
 public function show(About $about):RedirectResponse{return redirect()->route('abouts.edit',$about);}
 public function edit(About $about):View{return view('admin.abouts.form',compact('about'));}
 public function update(Request $r,About $about):RedirectResponse{$about->update($this->data($r,$about));return back()->with('message','About content updated.');}
 public function destroy(About $about):RedirectResponse{if($about->image&&!filter_var($about->image,FILTER_VALIDATE_URL))Storage::disk('public')->delete($about->image);$about->delete();return redirect()->route('abouts.index')->with('message','About content deleted.');}
 private function data(Request $r,?About $about=null):array{$d=$r->validate(['key'=>['required','alpha_dash','max:100','unique:abouts,key'.($about?','.$about->id:'')],'image'=>['nullable','image','max:4096'],'image_path'=>['nullable','string','max:500'],'en.title'=>['required','string','max:255'],'en.description'=>['nullable','string']]);if($r->hasFile('image')){if($about?->image&&!filter_var($about->image,FILTER_VALIDATE_URL))Storage::disk('public')->delete($about->image);$d['image']=$r->file('image')->store('abouts','public');}elseif($r->filled('image_path'))$d['image']=$r->image_path;elseif($about)$d['image']=$about->image;unset($d['image_path']);return $d;}
}

<?php
namespace App\Http\Controllers\Admin;use App\Http\Controllers\Controller;use App\Models\Social;use Illuminate\Http\{RedirectResponse,Request};use Illuminate\View\View;
class SocialController extends Controller{
 public function __construct(){$this->middleware('permission:list-socials')->only(['index','show']);$this->middleware('permission:create-socials')->only(['create','store']);$this->middleware('permission:edit-socials')->only(['edit','update']);$this->middleware('permission:delete-socials')->only('destroy');}
 public function index(Request $r):View{$socials=Social::query()->when($r->filled('q'),fn($q)=>$q->where('title','like','%'.$r->q.'%'))->when($r->filled('status'),fn($q)=>$q->where('is_active',$r->boolean('status')))->orderBy('order')->paginate(20)->withQueryString();return view('admin.socials.index',compact('socials'));}
 public function create():View{return view('admin.socials.form',['social'=>new Social]);}
 public function store(Request $r):RedirectResponse{Social::create($this->data($r));return redirect()->route('socials.index')->with('message','Social link created.');}
 public function show(Social $social):RedirectResponse{return redirect()->route('socials.edit',$social);}
 public function edit(Social $social):View{return view('admin.socials.form',compact('social'));}
 public function update(Request $r,Social $social):RedirectResponse{$social->update($this->data($r));return back()->with('message','Social link updated.');}
 public function destroy(Social $social):RedirectResponse{$social->delete();return redirect()->route('socials.index')->with('message','Social link deleted.');}
 private function data(Request $r):array{$d=$r->validate(['title'=>['required','string','max:255'],'icon'=>['nullable','string','max:100'],'link'=>['required','string','max:500'],'is_active'=>['nullable','boolean'],'order'=>['nullable','integer','min:0']]);$d['is_active']=$r->boolean('is_active');$d['order']??=0;return $d;}
}

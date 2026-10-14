<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;use App\Models\Faq;use Illuminate\Http\{RedirectResponse,Request};use Illuminate\View\View;
class FaqController extends Controller{
 public function __construct(){$this->middleware('permission:list-faqs')->only(['index','show']);$this->middleware('permission:create-faqs')->only(['create','store']);$this->middleware('permission:edit-faqs')->only(['edit','update']);$this->middleware('permission:delete-faqs')->only('destroy');}
 public function index(Request $r):View{$faqs=Faq::with('translations')->when($r->filled('q'),fn($q)=>$q->whereTranslationLike('question','%'.$r->q.'%'))->when($r->filled('status'),fn($q)=>$q->where('is_active',$r->boolean('status')))->when($r->filled('group'),fn($q)=>$q->where('group',$r->group))->orderBy('group')->orderBy('order')->paginate(30)->withQueryString();return view('admin.faqs.index',compact('faqs'));}
 public function create(Request $r):View{return view('admin.faqs.form',['faq'=>new Faq(['group'=>array_key_exists((string)$r->group,Faq::GROUPS)?$r->group:'trip'])]);}
 public function store(Request $r):RedirectResponse{$faq=Faq::create($this->data($r));return redirect()->route('faqs.index',['group'=>$faq->group])->with('message','FAQ created.');}
 public function show(Faq $faq):RedirectResponse{return redirect()->route('faqs.edit',$faq);}
 public function edit(Faq $faq):View{return view('admin.faqs.form',compact('faq'));}
 public function update(Request $r,Faq $faq):RedirectResponse{$faq->update($this->data($r));return back()->with('message','FAQ updated.');}
 public function destroy(Faq $faq):RedirectResponse{$faq->delete();return redirect()->route('faqs.index')->with('message','FAQ deleted.');}
 private function data(Request $r):array{$d=$r->validate(['group'=>['required',\Illuminate\Validation\Rule::in(array_keys(Faq::GROUPS))],'order'=>['nullable','integer','min:0','max:9999'],'en.question'=>['required','string','max:1000'],'en.answer'=>['required','string'],'is_active'=>['nullable','boolean']]);$d['is_active']=$r->boolean('is_active');$d['order']=$r->filled('order')?(int)$d['order']:((int)Faq::where('group',$d['group'])->max('order')+1);return $d;}
}

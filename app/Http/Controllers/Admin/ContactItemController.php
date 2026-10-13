<?php
namespace App\Http\Controllers\Admin;use App\Http\Controllers\Controller;use App\Models\ContactItem;use Illuminate\Http\{RedirectResponse,Request};use Illuminate\View\View;
class ContactItemController extends Controller{
 public function __construct(){$this->middleware('permission:list-contact_items')->only(['index','show']);$this->middleware('permission:create-contact_items')->only(['create','store']);$this->middleware('permission:edit-contact_items')->only(['edit','update']);$this->middleware('permission:delete-contact_items')->only('destroy');}
 public function index(Request $r):View{$contact_items=ContactItem::with('translations')->when($r->filled('q'),fn($q)=>$q->where(fn($x)=>$x->whereTranslationLike('title','%'.$r->q.'%')->orWhereTranslationLike('value','%'.$r->q.'%')))->when($r->filled('status'),fn($q)=>$q->where('is_active',$r->boolean('status')))->orderBy('order')->paginate(20)->withQueryString();return view('admin.contact_items.index',compact('contact_items'));}
 public function create():View{return view('admin.contact_items.form',['contact_item'=>new ContactItem]);}
 public function store(Request $r):RedirectResponse{ContactItem::create($this->data($r));return redirect()->route('contact_items.index')->with('message','Item created.');}
 public function show(ContactItem $contact_item):RedirectResponse{return redirect()->route('contact_items.edit',$contact_item);}
 public function edit(ContactItem $contact_item):View{return view('admin.contact_items.form',compact('contact_item'));}
 public function update(Request $r,ContactItem $contact_item):RedirectResponse{$contact_item->update($this->data($r));return back()->with('message','Item updated.');}
 public function destroy(ContactItem $contact_item):RedirectResponse{$contact_item->delete();return redirect()->route('contact_items.index')->with('message','Item deleted.');}
 private function data(Request $r):array{$d=$r->validate(['icon'=>['nullable','string','max:100'],'link'=>['nullable','string','max:500'],'is_active'=>['nullable','boolean'],'en.title'=>['required','string','max:255'],'en.value'=>['required','string']]);$d['is_active']=$r->boolean('is_active');return $d;}
}

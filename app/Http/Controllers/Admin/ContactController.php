<?php
namespace App\Http\Controllers\Admin;use App\Http\Controllers\Controller;use App\Models\Contact;use Illuminate\Http\{RedirectResponse,Request};use Illuminate\View\View;
class ContactController extends Controller{
 public function __construct(){$this->middleware('permission:list-contacts')->only(['index','show']);$this->middleware('permission:delete-contacts')->only('destroy');}
 public function index(Request $r):View{$contacts=Contact::with('service.translations')->when($r->filled('q'),fn($q)=>$q->where(fn($x)=>$x->where('name','like','%'.$r->q.'%')->orWhere('phone','like','%'.$r->q.'%')->orWhere('message','like','%'.$r->q.'%')))->when($r->filled('read'),fn($q)=>$q->where('is_read',$r->boolean('read')))->latest()->paginate(20)->withQueryString();return view('admin.contacts.index',compact('contacts'));}
 public function show(Contact $contact):View{$contact->markAsRead();return view('admin.contacts.show',compact('contact'));}
 public function destroy(Contact $contact):RedirectResponse{$contact->delete();return redirect()->route('contacts.index')->with('message','Contact request deleted.');}
}

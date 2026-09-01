<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;use App\Models\Review;use App\Services\ImageUploadService;use Illuminate\Http\{RedirectResponse,Request};use Illuminate\View\View;
class ReviewController extends Controller{
 public function __construct(private readonly ImageUploadService $images){$this->middleware('permission:list-reviews')->only(['index','show']);$this->middleware('permission:create-reviews')->only(['create','store']);$this->middleware('permission:edit-reviews')->only(['edit','update']);$this->middleware('permission:delete-reviews')->only('destroy');}
 public function index(Request $r):View{$reviews=Review::with('translations')->when($r->filled('q'),fn($q)=>$q->whereTranslationLike('name','%'.$r->q.'%'))->when($r->filled('status'),fn($q)=>$q->where('is_active',$r->boolean('status')))->when($r->filled('rating'),fn($q)=>$q->where('rating',$r->integer('rating')))->orderBy('order')->paginate(20)->withQueryString();return view('admin.reviews.index',compact('reviews'));}
 public function create():View{return view('admin.reviews.form',['review'=>new Review]);}
 public function store(Request $r):RedirectResponse{Review::create($this->data($r));return redirect()->route('reviews.index')->with('message','Review created.');}
 public function show(Review $review):RedirectResponse{return redirect()->route('reviews.edit',$review);}
 public function edit(Review $review):View{return view('admin.reviews.form',compact('review'));}
 public function update(Request $r,Review $review):RedirectResponse{$review->update($this->data($r,$review));return back()->with('message','Review updated.');}
 public function destroy(Review $review):RedirectResponse{$review->delete();return redirect()->route('reviews.index')->with('message','Review deleted.');}
 private function data(Request $r,?Review $review=null):array{$d=$r->validate(['image'=>['nullable','image','max:4096'],'image_path'=>['nullable','string','max:500'],'rating'=>['required','integer','between:1,5'],'is_active'=>['nullable','boolean'],'order'=>['nullable','integer','min:0'],'en.name'=>['required','string','max:255'],'en.position'=>['nullable','string','max:255'],'en.content'=>['required','string']]);$d['is_active']=$r->boolean('is_active');$d['order']??=0;if($r->hasFile('image'))$d['image']=$this->images->upload($r->file('image'));elseif($r->filled('image_path'))$d['image']=$r->image_path;elseif($review)$d['image']=$review->image;unset($d['image_path']);return $d;}
}

<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Destination,PickupPoint,Tour,TourCategory,Transportation};
use Illuminate\Http\{Request,RedirectResponse};
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TourController extends Controller
{
 public function __construct(){ $this->middleware('permission:list-tours')->only('index'); $this->middleware('permission:create-tours')->only(['create','store']); $this->middleware('permission:edit-tours')->only(['edit','update']); $this->middleware('permission:delete-tours')->only('destroy'); }
 public function index(Request $r):View{$tours=Tour::with(['translations','destination.translations','categories.translations'])->withCount('departures')->when($r->filled('q'),fn($q)=>$q->whereTranslationLike('name','%'.$r->string('q').'%'))->when($r->filled('status'),fn($q)=>$q->where('status',$r->status))->when($r->filled('destination_id'),fn($q)=>$q->where('destination_id',$r->integer('destination_id')))->when($r->filled('category_id'),fn($q)=>$q->whereHas('categories',fn($x)=>$x->whereKey($r->integer('category_id'))))->when($r->boolean('home'),fn($q)=>$q->where('show_on_home',true))->orderBy('sort_order')->paginate(20)->withQueryString(); return view('admin.tours.index',['tours'=>$tours,'destinations'=>Destination::withTranslation()->get(),'categories'=>TourCategory::withTranslation()->get()]);}
 public function create():View{return $this->form(new Tour);}
 public function store(Request $r):RedirectResponse{$tour=Tour::create($this->validated($r));$this->sync($tour,$r);return redirect()->route('tours.edit',$tour)->with('message','Tour created.');}
 public function edit(Tour $tour):View{$tour->load(['categories','transportations','pickupPoints','departures','itineraryDays.items','contentItems.translations','cruiseDetail']);return $this->form($tour);}
 public function update(Request $r,Tour $tour):RedirectResponse{$tour->update($this->validated($r));$this->sync($tour,$r);return back()->with('message','Tour updated.');}
 public function destroy(Tour $tour):RedirectResponse{$tour->delete();return back()->with('message','Tour deleted.');}
 private function form(Tour $tour):View{return view('admin.tours.form',['tour'=>$tour,'destinations'=>Destination::where('is_active',true)->withTranslation()->get(),'categories'=>TourCategory::where('is_active',true)->withTranslation()->get(),'transportations'=>Transportation::where('is_active',true)->withTranslation()->get(),'pickupPoints'=>PickupPoint::where('is_active',true)->withTranslation()->orderBy('sort_order')->get()]);}
 private function sync(Tour $tour,Request $r):void
 {
  $tour->categories()->sync($r->input('category_ids',[]));
  $tour->transportations()->sync($r->input('transportation_ids',[]));
  $tour->pickupPoints()->sync($r->input('pickup_point_ids',[]));
  if($tour->is_cruise){
   $tour->cruiseDetail()->updateOrCreate([], [
    'company'=>$r->input('cruise.company'),
    'departure_port'=>$r->input('cruise.departure_port'),
    'cabin_type'=>$r->input('cruise.cabin_type'),
    'documents'=>array_values(array_filter(array_map('trim',preg_split('/\r\n|\r|\n/',(string)$r->input('cruise.documents',''))))),
   ]);
  }else{$tour->cruiseDetail()->delete();}
 }
 private function validated(Request $r):array{$d=$r->validate(['destination_id'=>['nullable','exists:destinations,id'],'main_image'=>['nullable','string','max:500'],'duration_days'=>['required','integer','min:1'],'duration_nights'=>['required','integer','min:0'],'age_restriction'=>['nullable','string','max:100'],'activity_level'=>['nullable','string','max:100'],'badge'=>['nullable',Rule::in(['new','popular','limited_seats','last_seats','sold_out','coming_soon','special_price'])],'status'=>['required',Rule::in(['draft','published','archived'])],'sort_order'=>['nullable','integer','min:0'],'is_private'=>['nullable','boolean'],'is_cruise'=>['nullable','boolean'],'show_on_home'=>['nullable','boolean'],'category_ids'=>['nullable','array'],'category_ids.*'=>['exists:tour_categories,id'],'transportation_ids'=>['nullable','array'],'transportation_ids.*'=>['exists:transportations,id'],'pickup_point_ids'=>['nullable','array'],'pickup_point_ids.*'=>['exists:pickup_points,id'],'cruise.company'=>['nullable','required_if:is_cruise,1','string','max:255'],'cruise.departure_port'=>['nullable','required_if:is_cruise,1','string','max:255'],'cruise.cabin_type'=>['nullable','string','max:500'],'cruise.documents'=>['nullable','string'],'en.name'=>['required','string','max:255'],'en.slug'=>['required','alpha_dash','max:255'],'en.short_description'=>['nullable','string'],'en.description'=>['nullable','string'],'en.cancellation_policy'=>['nullable','string'],'en.refund_policy'=>['nullable','string'],'en.boarding_rules'=>['nullable','string'],'en.baggage_rules'=>['nullable','string'],'en.important_notes'=>['nullable','string']]);foreach(['is_private','is_cruise','show_on_home'] as $f)$d[$f]=$r->boolean($f);$d['sort_order']??=0;unset($d['category_ids'],$d['transportation_ids'],$d['pickup_point_ids'],$d['cruise']);return $d;}
}

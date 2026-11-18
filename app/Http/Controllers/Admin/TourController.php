<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Admin\Concerns\HandlesMediaUploads;
use App\Http\Controllers\Controller;
use App\Models\{Destination,PickupPoint,Tour,TourCategory,TourDeparture,Transportation};
use Illuminate\Http\{Request,RedirectResponse};
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TourController extends Controller
{
 use HandlesMediaUploads;
 public function __construct(){ $this->middleware('permission:list-tours')->only('index'); $this->middleware('permission:create-tours')->only(['create','store']); $this->middleware('permission:edit-tours')->only(['edit','update']); $this->middleware('permission:delete-tours')->only('destroy'); }
 /** Admin tour list sort options (older ?sort=name_asc style links keep working). */
 public const SORTS=['name_asc'=>'Tour name (A–Z)','name_desc'=>'Tour name (Z–A)','created_desc'=>'Date added (newest)','created_asc'=>'Date added (oldest)','departure_asc'=>'Departure date (earliest)','departure_desc'=>'Departure date (latest)'];
 /** Sortable columns of the list: ?sort=<column>&dir=asc|desc. */
 public const SORT_COLUMNS=['name','destination','category','dates','status','created'];
 public const STATUSES=['draft'=>'Draft','published'=>'Published','archived'=>'Archived'];

 public function index(Request $r):View{
  // Excel-style column filters: several statuses / destinations / categories at once, a date range,
  // month / year, an exact date and the All / Upcoming / Past / No dates view. Past dates are never hidden from admin.
  $r->validate(['q'=>['nullable','string','max:200'],'status'=>['nullable'],'status.*'=>[Rule::in(array_keys(self::STATUSES))],'destination_id'=>['nullable'],'destination_id.*'=>['integer'],'category_id'=>['nullable'],'category_id.*'=>['integer'],
   'month'=>['nullable','integer','between:1,12'],'year'=>['nullable','integer','between:2000,2100'],'date'=>['nullable','date'],'date_from'=>['nullable','date'],'date_to'=>['nullable','date','after_or_equal:date_from'],
   'when'=>['nullable',Rule::in(['all','upcoming','past','none'])],'sort'=>['nullable',Rule::in(array_merge(array_keys(self::SORTS),self::SORT_COLUMNS))],'dir'=>['nullable',Rule::in(['asc','desc'])],'per_page'=>['nullable',Rule::in([20,50,100])]]);
  $many=fn(string $key)=>collect((array)$r->input($key,[]))->filter(fn($v)=>$v!==null&&$v!=='')->values();
  $statuses=$many('status');$destinationIds=$many('destination_id')->map(fn($v)=>(int)$v);$categoryIds=$many('category_id')->map(fn($v)=>(int)$v);
  [$sort,$dir]=match($r->input('sort')){'name_asc'=>['name','asc'],'name_desc'=>['name','desc'],'created_desc'=>['created','desc'],'created_asc'=>['created','asc'],'departure_asc'=>['dates','asc'],'departure_desc'=>['dates','desc'],default=>[$r->input('sort'),$r->input('dir','asc')]};
  $when=$r->input('when');
  $departures=fn($q)=>$q->when($r->filled('year'),fn($x)=>$x->whereYear('departs_at',$r->integer('year')))->when($r->filled('month'),fn($x)=>$x->whereMonth('departs_at',$r->integer('month')))->when($r->filled('date'),fn($x)=>$x->whereDate('departs_at',$r->date('date')))
   ->when($r->filled('date_from'),fn($x)=>$x->whereDate('departs_at','>=',$r->date('date_from')))->when($r->filled('date_to'),fn($x)=>$x->whereDate('departs_at','<=',$r->date('date_to')))
   ->when($when==='upcoming',fn($x)=>$x->where('departs_at','>=',now()))->when($when==='past',fn($x)=>$x->where('departs_at','<',now()));
  $filtersDepartures=collect($r->only(['year','month','date','date_from','date_to']))->filter()->isNotEmpty()||in_array($when,['upcoming','past'],true);
  $tours=Tour::with(['translations','destination.translations','categories.translations','departures'=>fn($q)=>$q->select('id','tour_id','departs_at')->orderBy('departs_at')])->withCount(['departures','itineraryDays','contentItems'])
   ->when($r->filled('q'),fn($q)=>$q->whereTranslationLike('name','%'.$r->string('q').'%'))
   ->when($statuses->isNotEmpty(),fn($q)=>$q->whereIn('tours.status',$statuses))
   ->when($destinationIds->isNotEmpty(),fn($q)=>$q->where(fn($x)=>$x->whereIn('tours.destination_id',$destinationIds->filter())->when($destinationIds->contains(0),fn($y)=>$y->orWhereNull('tours.destination_id'))))
   ->when($categoryIds->isNotEmpty(),fn($q)=>$q->where(fn($x)=>$x->whereHas('categories',fn($c)=>$c->whereKey($categoryIds->filter()))->when($categoryIds->contains(0),fn($y)=>$y->orWhereDoesntHave('categories'))))
   ->when($r->boolean('home'),fn($q)=>$q->where('tours.show_on_home',true))->when($r->boolean('carousel'),fn($q)=>$q->where('tours.show_in_carousel',true))
   ->when($when==='none',fn($q)=>$q->whereDoesntHave('departures'))
   ->when($filtersDepartures,fn($q)=>$q->whereHas('departures',$departures));
  $dir=$dir==='desc'?'desc':'asc';
  // Departure sorting uses the dates that match the filters: earliest first, or latest first. Empty values go last.
  match($sort){
   'name'=>$tours->orderByTranslation('name',$dir)->orderBy('tours.sort_order'),
   'destination'=>$tours->addSelect(['sort_destination'=>\App\Models\DestinationTranslation::select('name')->whereColumn('destination_id','tours.destination_id')->where('locale','en')->limit(1)])->orderByRaw('sort_destination is null')->orderBy('sort_destination',$dir)->orderBy('tours.sort_order'),
   'category'=>$tours->addSelect(['sort_category'=>\Illuminate\Support\Facades\DB::table('tour_category')->join('tour_category_translations','tour_category_translations.tour_category_id','=','tour_category.tour_category_id')->where('tour_category_translations.locale','en')->whereColumn('tour_category.tour_id','tours.id')->selectRaw('min(tour_category_translations.name)')])->orderByRaw('sort_category is null')->orderBy('sort_category',$dir)->orderBy('tours.sort_order'),
   'dates'=>$tours->addSelect(['sort_departure'=>TourDeparture::selectRaw($dir==='asc'?'min(departs_at)':'max(departs_at)')->whereColumn('tour_id','tours.id')->where($departures)])->orderByRaw('sort_departure is null')->orderBy('sort_departure',$dir)->orderBy('tours.sort_order'),
   'status'=>$tours->orderBy('tours.status',$dir)->orderBy('tours.sort_order'),
   'created'=>$tours->orderBy('tours.created_at',$dir)->orderBy('tours.id',$dir),
   default=>$tours->orderBy('tours.sort_order'),
  };
  $years=TourDeparture::selectRaw('min(departs_at) first, max(departs_at) last')->first();
  return view('admin.tours.index',['tours'=>$tours->paginate((int)$r->input('per_page',20))->withQueryString(),
   'destinations'=>Destination::withTranslation()->withCount('tours')->get()->sortBy('name')->values(),'categories'=>TourCategory::withTranslation()->withCount('tours')->get()->sortBy('name')->values(),
   'statusLabels'=>self::STATUSES,'sort'=>in_array($sort,self::SORT_COLUMNS,true)?$sort:null,'dir'=>$dir,
   'picked'=>['status'=>$statuses->all(),'destination_id'=>$destinationIds->all(),'category_id'=>$categoryIds->all()],
   'years'=>$years?->first?range(now()->parse($years->last)->year,now()->parse($years->first)->year):[now()->year],'filtersDepartures'=>$filtersDepartures,
   'matchesDeparture'=>fn($at)=>$filtersDepartures&&(!$r->filled('year')||$at->year===$r->integer('year'))&&(!$r->filled('month')||$at->month===$r->integer('month'))&&(!$r->filled('date')||$at->isSameDay($r->date('date')))&&(!$r->filled('date_from')||$at->gte($r->date('date_from')->startOfDay()))&&(!$r->filled('date_to')||$at->lte($r->date('date_to')->endOfDay()))&&($when!=='upcoming'||$at->gte(now()))&&($when!=='past'||$at->lt(now()))]);}
 public function create():View{return $this->form(new Tour);}
 public function store(Request $r):RedirectResponse{
  // First dates can be entered with the tour; empty rows are ignored.
  $dates=collect($r->input('dates',[]))->filter(fn($d)=>!empty($d['departs_at']))->values()->all();
  $tour=\Illuminate\Support\Facades\DB::transaction(function()use($r,$dates){$tour=Tour::create($this->validated($r));$this->sync($tour,$r);if($dates)TourDepartureController::createMany($tour,['dates'=>$dates]+(array)$r->input('departure',[]));return $tour;});
  return redirect()->route('tours.edit',$tour)->with('message','Tour created'.($dates?' with '.count($dates).' '.str('date')->plural(count($dates)):'').'.');}
 public function edit(Tour $tour):View{$tour->load(['categories','transportations','pickupPoints','departures'=>fn($q)=>$q->orderBy('departs_at'),'departures.prices','departures.pickupPoints.translations','departures.staff.user','itineraryDays.items','contentItems.translations','inclusions','cruiseDetail']);return $this->form($tour);}
 public function update(Request $r,Tour $tour):RedirectResponse{$tour->update($this->validated($r,$tour));$this->sync($tour,$r);return back()->with('message','Tour updated.');}
 public function destroy(Tour $tour):RedirectResponse{$tour->delete();return back()->with('message','Tour deleted.');}
 private function form(Tour $tour):View{return view('admin.tours.form',['tour'=>$tour,'destinations'=>Destination::where('is_active',true)->withTranslation()->get(),'categories'=>TourCategory::where('is_active',true)->withTranslation()->get(),'transportations'=>Transportation::where('is_active',true)->withTranslation()->get(),'pickupPoints'=>PickupPoint::where('is_active',true)->withTranslation()->orderBy('sort_order')->get(),'tourFilters'=>\App\Models\TourFilter::with('options')->orderBy('sort_order')->get(),'inclusionLibrary'=>\App\Models\Inclusion::with('translations')->ordered()->get()->groupBy('type')]);}
 private function sync(Tour $tour,Request $r):void
 {
  $tour->categories()->sync($r->input('category_ids',[]));
  $tour->transportations()->sync($r->input('transportation_ids',[]));
  $tour->pickupPoints()->sync($r->input('pickup_point_ids',[]));
  if($r->has('filter_options_present'))$tour->filterOptions()->sync(array_map('intval',$r->input('filter_option_ids',[])));
  if($r->has('inclusions_present'))$tour->inclusions()->sync(\App\Models\Inclusion::whereIn('id',(array)$r->input('inclusion_ids',[]))->pluck('id'));
  if($tour->is_cruise){
   $tour->cruiseDetail()->updateOrCreate([], [
    'company'=>$r->input('cruise.company'),
    'departure_port'=>$r->input('cruise.departure_port'),
    'cabin_type'=>$r->input('cruise.cabin_type'),
    'documents'=>array_values(array_filter(array_map('trim',preg_split('/\r\n|\r|\n/',(string)$r->input('cruise.documents',''))))),
   ]);
  }else{$tour->cruiseDetail()->delete();}
 }
 private function validated(Request $r,?Tour $tour=null):array{
  // The URL slug is cleaned the same way the site builds it (lower case, dashes) before the unique check.
  if($r->has('en.slug'))$r->merge(['en'=>array_merge((array)$r->input('en'),['slug'=>\Illuminate\Support\Str::slug((string)$r->input('en.slug'))])]);
  $d=$r->validate($this->mediaRules('main_image')+['en.slug'=>['nullable','string','max:200',Rule::unique('tour_translations','slug')->where('locale','en')->ignore($tour?->translate('en')?->id)],'en.meta_title'=>['nullable','string','max:255'],'en.meta_description'=>['nullable','string','max:1000'],'destination_id'=>['nullable','exists:destinations,id'],'duration_days'=>['required','integer','min:1'],'duration_nights'=>['required','integer','min:0'],'age_restriction'=>['nullable','string','max:100'],'activity_level'=>['nullable','string','max:100'],'deposit_per_person'=>['nullable','numeric','min:0'],'balance_due_days'=>['nullable','integer','min:0','max:365'],'sort_order'=>['nullable','integer','min:0'],'badge'=>['nullable',Rule::in(['new','popular','limited_seats','last_seats','sold_out','coming_soon','special_price'])],'status'=>['required',Rule::in(['draft','published','archived'])],'is_private'=>['nullable','boolean'],'is_cruise'=>['nullable','boolean'],'tax_included'=>['nullable','boolean'],'infant_max_age'=>['nullable','integer','min:0','max:5'],'child_max_age'=>['nullable','integer','min:1','max:20','gt:infant_max_age'],'infant_needs_seat'=>['nullable','boolean'],'show_on_home'=>['nullable','boolean'],'show_in_carousel'=>['nullable','boolean'],'category_ids'=>['nullable','array'],'category_ids.*'=>['exists:tour_categories,id'],'transportation_ids'=>['nullable','array'],'transportation_ids.*'=>['exists:transportations,id'],'pickup_point_ids'=>['nullable','array'],'pickup_point_ids.*'=>['exists:pickup_points,id'],'cruise.company'=>['nullable','required_if:is_cruise,1','string','max:255'],'cruise.departure_port'=>['nullable','required_if:is_cruise,1','string','max:255'],'cruise.cabin_type'=>['nullable','string','max:500'],'cruise.documents'=>['nullable','string'],'en.name'=>['required','string','max:255'],'filter_option_ids'=>['nullable','array'],'filter_option_ids.*'=>['integer','exists:tour_filter_options,id'],'en.short_description'=>['nullable','string'],'en.description'=>['nullable','string'],'en.cancellation_policy'=>['nullable','string'],'en.refund_policy'=>['nullable','string'],'en.deposit_policy'=>['nullable','string'],'en.boarding_rules'=>['nullable','string'],'en.baggage_rules'=>['nullable','string'],'en.important_notes'=>['nullable','string'],'en.meal_options'=>['nullable','string','max:2000'],'en.waiver_title'=>['nullable','string','max:255'],'en.waiver'=>['nullable','string']]);$this->resolveMedia($r,$d,'main_image',$tour?->getRawOriginal('main_image'));foreach(['is_private','is_cruise','show_on_home','show_in_carousel','tax_included','infant_needs_seat'] as $f)$d[$f]=$r->boolean($f);$d['infant_max_age']=(int)($d['infant_max_age']??1);$d['child_max_age']=(int)($d['child_max_age']??11);$d['sort_order']=$d['sort_order']??0;unset($d['category_ids'],$d['transportation_ids'],$d['pickup_point_ids'],$d['cruise'],$d['filter_option_ids']);return $d;}
}

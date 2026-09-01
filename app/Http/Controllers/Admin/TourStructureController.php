<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Tour,TourContentItem,TourDeparture,TourItineraryDay,TourItineraryItem};
use Illuminate\Http\{Request,RedirectResponse};
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TourStructureController extends Controller
{
 public function index(Request $r,Tour $tour,string $section):View
 {
  abort_unless(in_array($section,['departures','itinerary','content'],true),404);
  $permission=$section==='departures'?'list-tour-departures':($section==='itinerary'?'list-tour-itineraries':'list-tour-content');
  abort_unless($r->user()->can($permission),403);
  if($section==='departures')$items=$tour->departures()->when($r->filled('q'),fn($q)=>$q->where(fn($x)=>$x->where('status',$r->q)->orWhere('currency','like','%'.$r->q.'%')))->orderByDesc('departs_at')->paginate(20)->withQueryString();
  elseif($section==='itinerary')$items=$tour->itineraryDays()->with(['translations','items.translations'])->when($r->filled('q'),fn($q)=>$q->whereTranslationLike('title','%'.$r->q.'%'))->paginate(20)->withQueryString();
  else $items=$tour->contentItems()->with('translations')->when($r->filled('q'),fn($q)=>$q->where(fn($x)=>$x->where('type',$r->q)->orWhereTranslationLike('title','%'.$r->q.'%')))->orderBy('sort_order')->paginate(20)->withQueryString();
  return view('admin.tours.structure',compact('tour','section','items'));
 }
 public function storeDeparture(Request $r,Tour $tour):RedirectResponse{abort_unless($r->user()->can('create-tour-departures'),403);$tour->departures()->create($this->departureData($r));return back()->with('message','Departure added.');}
 public function updateDeparture(Request $r,Tour $tour,TourDeparture $departure):RedirectResponse{abort_unless($r->user()->can('edit-tour-departures'),403);abort_unless($departure->tour_id===$tour->id,404);$departure->update($this->departureData($r));return back()->with('message','Departure updated.');}
 public function destroyDeparture(Request $r,Tour $tour,TourDeparture $departure):RedirectResponse{abort_unless($r->user()->can('delete-tour-departures'),403);abort_unless($departure->tour_id===$tour->id,404);$departure->delete();return back()->with('message','Departure deleted.');}
 public function storeDay(Request $r,Tour $tour):RedirectResponse{abort_unless($r->user()->can('create-tour-itineraries'),403);$tour->itineraryDays()->create($this->dayData($r));return back()->with('message','Itinerary day added.');}
 public function updateDay(Request $r,Tour $tour,TourItineraryDay $day):RedirectResponse{abort_unless($r->user()->can('edit-tour-itineraries'),403);abort_unless($day->tour_id===$tour->id,404);$day->update($this->dayData($r));return back()->with('message','Itinerary day updated.');}
 public function destroyDay(Request $r,Tour $tour,TourItineraryDay $day):RedirectResponse{abort_unless($r->user()->can('delete-tour-itineraries'),403);abort_unless($day->tour_id===$tour->id,404);$day->delete();return back()->with('message','Itinerary day deleted.');}
 public function storeItem(Request $r,TourItineraryDay $day):RedirectResponse{abort_unless($r->user()->can('create-tour-itineraries'),403);$day->items()->create($this->itemData($r));return back()->with('message','Itinerary stop added.');}
 public function updateItem(Request $r,TourItineraryDay $day,TourItineraryItem $item):RedirectResponse{abort_unless($r->user()->can('edit-tour-itineraries'),403);abort_unless($item->tour_itinerary_day_id===$day->id,404);$item->update($this->itemData($r));return back()->with('message','Itinerary stop updated.');}
 public function destroyItem(Request $r,TourItineraryDay $day,TourItineraryItem $item):RedirectResponse{abort_unless($r->user()->can('delete-tour-itineraries'),403);abort_unless($item->tour_itinerary_day_id===$day->id,404);$item->delete();return back()->with('message','Itinerary stop deleted.');}
 public function storeContent(Request $r,Tour $tour):RedirectResponse{abort_unless($r->user()->can('create-tour-content'),403);$tour->contentItems()->create($this->contentData($r));return back()->with('message','Tour content added.');}
 public function updateContent(Request $r,Tour $tour,TourContentItem $content):RedirectResponse{abort_unless($r->user()->can('edit-tour-content'),403);abort_unless($content->tour_id===$tour->id,404);$content->update($this->contentData($r));return back()->with('message','Tour content updated.');}
 public function destroyContent(Request $r,Tour $tour,TourContentItem $content):RedirectResponse{abort_unless($r->user()->can('delete-tour-content'),403);abort_unless($content->tour_id===$tour->id,404);$content->delete();return back()->with('message','Tour content deleted.');}
 private function departureData(Request $r):array{return $r->validate(['departs_at'=>['required','date'],'returns_at'=>['nullable','date','after_or_equal:departs_at'],'price'=>['required','numeric','min:0'],'old_price'=>['nullable','numeric','gte:price'],'currency'=>['required','string','size:3'],'capacity'=>['required','integer','min:1'],'reserved_seats'=>['nullable','integer','min:0','lte:capacity'],'status'=>['required',Rule::in(['available','limited','sold_out','cancelled'])]]);}
 private function dayData(Request $r):array{return $r->validate(['day_number'=>['required','integer','min:1'],'sort_order'=>['nullable','integer','min:0'],'en.title'=>['nullable','string','max:255'],'en.description'=>['nullable','string']]);}
 private function itemData(Request $r):array{$d=$r->validate(['arrival_time'=>['nullable','date_format:H:i'],'departure_time'=>['nullable','date_format:H:i'],'duration_minutes'=>['nullable','integer','min:1'],'address'=>['nullable','string','max:255'],'map_url'=>['nullable','url','max:500'],'video_url'=>['nullable','url','max:500'],'images_text'=>['nullable','string'],'entrance_included'=>['nullable','boolean'],'sort_order'=>['nullable','integer','min:0'],'en.location_name'=>['required','string','max:255'],'en.description'=>['nullable','string'],'en.sights'=>['nullable','string'],'en.activities'=>['nullable','string'],'en.notes'=>['nullable','string']]);$d['entrance_included']=$r->boolean('entrance_included');$d['images']=array_values(array_filter(array_map('trim',preg_split('/\r\n|\r|\n/',(string)($d['images_text']??'')))));unset($d['images_text']);return $d;}
 private function contentData(Request $r):array{return $r->validate(['type'=>['required',Rule::in(['included','excluded','what_to_bring','document_requirement','children','seniors','faq'])],'sort_order'=>['nullable','integer','min:0'],'en.title'=>['required','string','max:255'],'en.description'=>['nullable','string']]);}
}

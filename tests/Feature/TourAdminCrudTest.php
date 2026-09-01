<?php
namespace Tests\Feature;
use App\Models\{Destination,PickupPoint,Tour,TourCategory,Transportation,User};
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TourAdminCrudTest extends TestCase
{
 use RefreshDatabase;
 private function admin():User{Role::firstOrCreate(['name'=>'superadmin','guard_name'=>'web']);$u=User::factory()->create();$u->assignRole('superadmin');return $u;}
 public function test_catalogue_resources_support_create_filter_update_and_delete():void{
  $this->seed(CmsPermissionSeeder::class);$this->actingAs($this->admin());
  foreach(['categories'=>['type'=>'tour','en'=>['name'=>'Bus Tours','slug'=>'bus-tours','description'=>'Bus trips']],'destinations'=>['type'=>'country','en'=>['name'=>'Canada','slug'=>'canada','description'=>'Canada tours']],'transportations'=>['type'=>'bus','seat_count'=>56,'en'=>['name'=>'Coach Bus','description'=>'Comfortable bus']]] as $resource=>$payload){
   $this->post(route('tour-catalogue.store',$resource),$payload+['is_active'=>1,'sort_order'=>1])->assertRedirect();
   $this->get(route('tour-catalogue.index',[$resource,'q'=>$payload['en']['name']]))->assertOk()->assertSee($payload['en']['name']);
  }
  $category=TourCategory::first();$this->put(route('tour-catalogue.update',['categories',$category]),['type'=>'season','en'=>['name'=>'Updated Category','slug'=>'updated-category'],'is_active'=>1])->assertRedirect();$this->assertDatabaseHas('tour_category_translations',['name'=>'Updated Category']);
  $this->delete(route('tour-catalogue.destroy',['categories',$category]))->assertRedirect();$this->assertSoftDeleted($category);
 }
 public function test_tour_crud_syncs_relations_and_filters():void{
  $this->seed(CmsPermissionSeeder::class);$this->actingAs($this->admin());
  $destination=Destination::create(['type'=>'country','is_active'=>1,'en'=>['name'=>'Canada','slug'=>'canada']]);$category=TourCategory::create(['type'=>'tour','is_active'=>1,'en'=>['name'=>'One Day','slug'=>'one-day']]);$transport=Transportation::create(['type'=>'bus','is_active'=>1,'en'=>['name'=>'Bus']]);$pickup=PickupPoint::create(['address'=>'Station','is_active'=>1,'en'=>['name'=>'Central pickup']]);
  $payload=['destination_id'=>$destination->id,'duration_days'=>1,'duration_nights'=>0,'status'=>'published','show_on_home'=>1,'category_ids'=>[$category->id],'transportation_ids'=>[$transport->id],'pickup_point_ids'=>[$pickup->id],'en'=>['name'=>'Niagara Tour','slug'=>'niagara-tour','short_description'=>'A trip']];
  $this->post(route('tours.store'),$payload)->assertRedirect();$tour=Tour::first();$this->assertTrue($tour->categories->contains($category));$this->assertTrue($tour->transportations->contains($transport));$this->assertTrue($tour->pickupPoints->contains($pickup));
  $this->get(route('tours.index',['q'=>'Niagara','category_id'=>$category->id,'home'=>1]))->assertOk()->assertSee('Niagara Tour');
  $updated=array_replace($payload,['en'=>array_merge($payload['en'],['name'=>'Updated Niagara'])]);
  $this->put(route('tours.update',$tour),$updated)->assertRedirect();$this->assertDatabaseHas('tour_translations',['name'=>'Updated Niagara']);
  $this->delete(route('tours.destroy',$tour))->assertRedirect();$this->assertSoftDeleted($tour);
 }
 public function test_catalogue_is_forbidden_without_permission():void{$user=User::factory()->create();$this->actingAs($user)->get(route('tour-catalogue.index','categories'))->assertForbidden();}
 public function test_departure_and_itinerary_nested_cruds_validate_relations():void{
  $this->seed(CmsPermissionSeeder::class);$this->actingAs($this->admin());$tour=Tour::create(['duration_days'=>2,'duration_nights'=>1,'status'=>'published','en'=>['name'=>'Test Tour','slug'=>'test-tour']]);
  $this->post(route('tour-departures.store',$tour),['departs_at'=>'2026-10-01 08:00','returns_at'=>'2026-10-02 20:00','price'=>199,'currency'=>'CAD','capacity'=>20,'reserved_seats'=>3,'status'=>'available'])->assertRedirect();
  $departure=$tour->departures()->first();$this->assertSame(17,$departure->available_seats);
  $this->put(route('tour-departures.update',[$tour,$departure]),['departs_at'=>'2026-10-01 08:00','price'=>179,'currency'=>'CAD','capacity'=>20,'reserved_seats'=>5,'status'=>'limited'])->assertRedirect();$this->assertDatabaseHas('tour_departures',['id'=>$departure->id,'status'=>'limited','reserved_seats'=>5]);
  $this->post(route('tour-itinerary-days.store',$tour),['day_number'=>1,'en'=>['title'=>'Arrival day']])->assertRedirect();$day=$tour->itineraryDays()->first();
  $this->post(route('tour-itinerary-items.store',$day),['arrival_time'=>'09:30','departure_time'=>'11:00','duration_minutes'=>90,'address'=>'Falls Ave','map_url'=>'https://maps.example.com','video_url'=>'https://video.example.com','images_text'=>"https://img.example.com/one.jpg\nhttps://img.example.com/two.jpg",'en'=>['location_name'=>'Niagara Falls','description'=>'Sightseeing','sights'=>'Waterfall','activities'=>'Boat trip','notes'=>'Bring a jacket'],'entrance_included'=>1])->assertRedirect();$item=$day->items()->first();$this->assertTrue($item->entrance_included);$this->assertSame(['https://img.example.com/one.jpg','https://img.example.com/two.jpg'],$item->images);$this->assertSame('Boat trip',$item->activities);
  $this->delete(route('tour-itinerary-items.destroy',[$day,$item]))->assertRedirect();$this->assertDatabaseMissing('tour_itinerary_items',['id'=>$item->id]);
  $this->delete(route('tour-itinerary-days.destroy',[$tour,$day]))->assertRedirect();$this->delete(route('tour-departures.destroy',[$tour,$departure]))->assertRedirect();
 }
 public function test_pickup_points_and_tour_content_have_complete_crud_paths():void{
  $this->seed(CmsPermissionSeeder::class);$this->actingAs($this->admin());
  $this->post(route('tour-catalogue.store','pickup-points'),['address'=>'100 Main St','map_url'=>'https://maps.example.com','is_active'=>1,'en'=>['name'=>'Union Station','instructions'=>'Front entrance']])->assertRedirect();$point=\App\Models\PickupPoint::first();$this->get(route('tour-catalogue.index',['pickup-points','q'=>'Union']))->assertOk()->assertSee('Union Station');$this->put(route('tour-catalogue.update',['pickup-points',$point]),['address'=>'200 Main St','is_active'=>1,'en'=>['name'=>'Updated Stop']])->assertRedirect();$this->assertDatabaseHas('pickup_point_translations',['name'=>'Updated Stop']);
  $tour=Tour::create(['duration_days'=>1,'duration_nights'=>0,'status'=>'published','en'=>['name'=>'Tour','slug'=>'tour']]);$this->post(route('tour-content-items.store',$tour),['type'=>'included','en'=>['title'=>'Breakfast','description'=>'Included meal']])->assertRedirect();$content=$tour->contentItems()->first();$this->put(route('tour-content-items.update',[$tour,$content]),['type'=>'excluded','en'=>['title'=>'Dinner']])->assertRedirect();$this->assertDatabaseHas('tour_content_items',['id'=>$content->id,'type'=>'excluded']);$this->delete(route('tour-content-items.destroy',[$tour,$content]))->assertRedirect();$this->assertDatabaseMissing('tour_content_items',['id'=>$content->id]);$this->delete(route('tour-catalogue.destroy',['pickup-points',$point]))->assertRedirect();$this->assertSoftDeleted($point);
 }
}

<?php
namespace Tests\Feature;use App\Models\{Booking,PickupPoint,Tour,TourDeparture,User};use Database\Seeders\CmsPermissionSeeder;use Illuminate\Foundation\Testing\RefreshDatabase;use Spatie\Permission\Models\Role;use Tests\TestCase;
class BookingCrudTest extends TestCase{use RefreshDatabase;private function admin():User{Role::firstOrCreate(['name'=>'superadmin','guard_name'=>'web']);$u=User::factory()->create();$u->assignRole('superadmin');return $u;}
 public function test_public_booking_creates_passengers_and_reserves_seats():void{$tour=Tour::create(['duration_days'=>1,'duration_nights'=>0,'status'=>'published','en'=>['name'=>'Tour','slug'=>'tour']]);$departure=TourDeparture::create(['tour_id'=>$tour->id,'departs_at'=>now()->addMonth(),'price'=>100,'currency'=>'CAD','capacity'=>10,'reserved_seats'=>0,'status'=>'available']);$payload=['tour_id'=>$tour->id,'tour_departure_id'=>$departure->id,'type'=>'tour','first_name'=>'John','last_name'=>'Doe','phone'=>'123','email'=>'john@example.com','participant_count'=>2,'passengers'=>[['first_name'=>'Jane','last_name'=>'Doe','category'=>'adult']]];$this->post(route('front.bookings.store'),$payload)->assertRedirect()->assertSessionHas('message');$booking=Booking::first();$this->assertNotNull($booking->reference);$this->assertCount(1,$booking->passengers);$this->assertSame(2,$departure->fresh()->reserved_seats);}
 public function test_booking_rejects_more_seats_than_available():void{$tour=Tour::create(['duration_days'=>1,'duration_nights'=>0,'status'=>'published','en'=>['name'=>'Tour','slug'=>'tour']]);$departure=TourDeparture::create(['tour_id'=>$tour->id,'departs_at'=>now()->addMonth(),'price'=>100,'currency'=>'CAD','capacity'=>1,'reserved_seats'=>0,'status'=>'available']);$this->post(route('front.bookings.store'),['tour_id'=>$tour->id,'tour_departure_id'=>$departure->id,'type'=>'tour','first_name'=>'John','last_name'=>'Doe','phone'=>'123','email'=>'john@example.com','participant_count'=>2])->assertStatus(422);$this->assertDatabaseCount('bookings',0);}
 public function test_admin_can_filter_update_and_delete_booking():void{$this->seed(CmsPermissionSeeder::class);$this->actingAs($this->admin());$b=Booking::create(['type'=>'visa','first_name'=>'A','last_name'=>'B','phone'=>'1','email'=>'a@b.com','participant_count'=>1]);$this->get(route('bookings.index',['type'=>'visa','q'=>$b->reference]))->assertOk()->assertSee($b->reference);$this->put(route('bookings.update',$b),['status'=>'contacted'])->assertRedirect();$this->assertDatabaseHas('bookings',['id'=>$b->id,'status'=>'contacted']);$this->delete(route('bookings.destroy',$b))->assertRedirect();$this->assertDatabaseMissing('bookings',['id'=>$b->id]);}

 public function test_every_public_enquiry_type_is_stored_and_contact_is_filterable():void
 {
  foreach(['contact','tour','private_tour','visa','cruise'] as $type){
   $this->post(route('front.bookings.store'),[
    'type'=>$type,'first_name'=>'Public','last_name'=>'Guest','phone'=>'123456',
    'email'=>$type.'@example.com','participant_count'=>1,'comments'=>$type.' request',
   ])->assertRedirect()->assertSessionHas('message');
  }

  $this->assertSame(['contact','cruise','private_tour','tour','visa'],Booking::query()->pluck('type')->sort()->values()->all());
  $this->seed(CmsPermissionSeeder::class);
  $this->actingAs($this->admin())->get(route('bookings.index',['type'=>'contact']))
   ->assertOk()->assertSee('contact@example.com')->assertDontSee('visa@example.com');
 }

 public function test_tour_detail_exposes_booking_anchor_and_posts_its_form():void
 {
  $tour=Tour::create(['duration_days'=>1,'duration_nights'=>0,'status'=>'published','en'=>['name'=>'Anchor Tour','slug'=>'anchor-tour']]);
  $departure=TourDeparture::create(['tour_id'=>$tour->id,'departs_at'=>now()->addMonth(),'price'=>100,'currency'=>'CAD','capacity'=>10,'reserved_seats'=>0,'status'=>'available']);
  $pickup=PickupPoint::create(['address'=>'Station','is_active'=>1,'en'=>['name'=>'Central']]);

  $this->get('/tours/anchor-tour')->assertOk()->assertSee('id="booking"',false)->assertSee('Anchor Tour')->assertSee('name="age"',false)->assertSee('name="source"',false)->assertSee('passengers[${index}][category]',false);
  $this->post(route('front.bookings.store'),[
   'tour_id'=>$tour->id,'tour_departure_id'=>$departure->id,'pickup_point_id'=>$pickup->id,
   'type'=>'tour','first_name'=>'Form','last_name'=>'Guest','phone'=>'123456',
   'email'=>'form@example.com','participant_count'=>2,'age'=>35,'whatsapp'=>'123456',
   'address'=>'100 Main St','comments'=>'Main comment','special_requests'=>'Window seat',
   'mobility_information'=>'None','food_allergies'=>'Peanuts','meal_selection'=>'Vegetarian','promo_code'=>'SAVE10','source'=>'Google',
   'passengers'=>[['first_name'=>'Extra','last_name'=>'Guest','category'=>'senior','age'=>67,'pickup_point_id'=>$pickup->id,'meal_selection'=>'Standard','notes'=>'Front seat']],
  ])->assertRedirect()->assertSessionHas('message');

  $this->assertDatabaseHas('bookings',['tour_id'=>$tour->id,'pickup_point_id'=>$pickup->id,'age'=>35,'special_requests'=>'Window seat','promo_code'=>'SAVE10']);
  $this->assertDatabaseHas('booking_passengers',['first_name'=>'Extra','category'=>'senior','age'=>67,'pickup_point_id'=>$pickup->id]);
 }
}

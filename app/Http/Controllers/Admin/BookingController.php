<?php
namespace App\Http\Controllers\Admin;use App\Http\Controllers\Controller;use App\Models\Booking;use Illuminate\Http\{Request,RedirectResponse};use Illuminate\Validation\Rule;use Illuminate\View\View;
class BookingController extends Controller{
 public function __construct(){$this->middleware('permission:list-bookings')->only(['index','show']);$this->middleware('permission:edit-bookings')->only('update');$this->middleware('permission:delete-bookings')->only('destroy');}
 public function index(Request $r):View{$bookings=Booking::with('tour.translations')->when($r->filled('q'),fn($q)=>$q->where(fn($x)=>$x->where('reference','like','%'.$r->q.'%')->orWhere('first_name','like','%'.$r->q.'%')->orWhere('last_name','like','%'.$r->q.'%')->orWhere('email','like','%'.$r->q.'%')->orWhere('phone','like','%'.$r->q.'%')))->when($r->filled('type'),fn($q)=>$q->where('type',$r->type))->when($r->filled('status'),fn($q)=>$q->where('status',$r->status))->latest()->paginate(20)->withQueryString();return view('admin.bookings.index',compact('bookings'));}
 public function show(Booking $booking):View{$booking->load(['tour.translations','departure','pickupPoint.translations','passengers.pickupPoint.translations']);return view('admin.bookings.show',compact('booking'));}
 public function update(Request $r,Booking $booking):RedirectResponse{$booking->update($r->validate(['status'=>['required',Rule::in(['new','contacted','confirmed','cancelled','completed'])]]));return back()->with('message','Booking status updated.');}
 public function destroy(Booking $booking):RedirectResponse{$booking->delete();return redirect()->route('bookings.index')->with('message','Booking deleted.');}
}

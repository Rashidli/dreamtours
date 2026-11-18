<?php
namespace App\Models;
use App\Models\Concerns\GeneratesSlug;use Illuminate\Database\Eloquent\Model;
class TourTranslation extends Model {use \App\Models\Concerns\RecordsActivity; use GeneratesSlug; protected $slugSource='name';
 // Renaming a tour keeps its URL; the address only changes when an editor edits the slug.
 protected bool $slugLocked=true;
 public $timestamps=false; protected $fillable=['name','slug','meta_title','meta_description','short_description','description','cancellation_policy','refund_policy','deposit_policy','boarding_rules','baggage_rules','important_notes', 'meal_options', 'waiver_title', 'waiver'];
 protected static function booted(): void
 {
  // Keep the old address working: it 301s to the new one. A slug that is live again is no longer a redirect.
  static::saved(function (self $t): void {
   TourSlugRedirect::where('locale', $t->locale)->where('slug', $t->slug)->delete();
   $old = (string) $t->getOriginal('slug');
   if ($t->wasChanged('slug') && $old !== '' && $old !== $t->slug) {
    TourSlugRedirect::updateOrCreate(['locale' => $t->locale, 'slug' => $old], ['tour_id' => $t->tour_id]);
   }
  });
 }
}

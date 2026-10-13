<?php
namespace App\Models;
use App\Models\Concerns\GeneratesSlug;use Illuminate\Database\Eloquent\Model;
class TourTranslation extends Model { use GeneratesSlug; protected $slugSource='name'; public $timestamps=false; protected $fillable=['name','slug','short_description','description','cancellation_policy','refund_policy','deposit_policy','boarding_rules','baggage_rules','important_notes']; }

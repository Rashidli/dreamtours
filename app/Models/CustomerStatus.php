<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** A customer's lifecycle status (New, Loyal / VIP, Do Not Contact…), managed by Super Admin. */
class CustomerStatus extends Model
{
    use \App\Models\Concerns\RecordsActivity;

    public const COLORS = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'];

    protected $fillable = ['name', 'color', 'blocks_marketing', 'sort_order', 'archived_at'];

    protected $casts = ['blocks_marketing' => 'boolean', 'archived_at' => 'datetime'];

    public function customers() { return $this->hasMany(Customer::class); }

    /** Archived statuses stay on the customers that have them but can't be chosen again. */
    public function scopeActive(Builder $query): Builder { return $query->whereNull('archived_at')->orderBy('sort_order')->orderBy('name'); }
}

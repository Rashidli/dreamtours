<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** A free label on customers (several per customer), managed by Super Admin. */
class CustomerTag extends Model
{
    use \App\Models\Concerns\RecordsActivity;

    protected $fillable = ['name', 'color', 'archived_at'];

    protected $casts = ['archived_at' => 'datetime'];

    public function customers() { return $this->belongsToMany(Customer::class); }

    public function scopeActive(Builder $query): Builder { return $query->whereNull('archived_at')->orderBy('name'); }
}

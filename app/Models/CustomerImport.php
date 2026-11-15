<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One uploaded customer file and how it was mapped and imported. */
class CustomerImport extends Model
{
    protected $fillable = ['user_id', 'filename', 'path', 'columns', 'mapping', 'options', 'summary', 'status', 'imported_at'];

    protected $casts = ['columns' => 'array', 'mapping' => 'array', 'options' => 'array', 'summary' => 'array', 'imported_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
}

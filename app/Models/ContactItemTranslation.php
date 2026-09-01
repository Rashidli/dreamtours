<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactItemTranslation extends Model
{
    public $timestamps = true;
    protected $fillable = ['title', 'value'];
}

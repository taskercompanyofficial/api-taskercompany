<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Services extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'keywords', 'status', 'icon', 'color'];
}

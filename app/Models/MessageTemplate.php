<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    protected $fillable = ['name','channel','subject','body','active'];
    protected $casts = ['active' => 'boolean'];
}

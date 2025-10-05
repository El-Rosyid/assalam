<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class academic_year extends Model
{
    use HasFactory;
    protected $table = 'academic_year';
    protected $guarded = [];
    public $timestamps = false;
}

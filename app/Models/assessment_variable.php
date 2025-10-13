<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class assessment_variable extends Model
{
    use HasFactory;
    protected $table = 'assessment_variable';
    protected $guarded = [];
    protected $fillable = [
        'name',
        'dekripsi'
    ];
}

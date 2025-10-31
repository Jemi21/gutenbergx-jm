<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $casts = [
        'authors' => 'array',
        'subjects' => 'array',
        'bookshelves' => 'array',
        'formats' => 'array',
        'languages' => 'array',
    ];
    
    public $timestamps = false;
}

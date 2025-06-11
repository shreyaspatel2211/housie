<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WinnerHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'early_five',
        'first_row',
        'second_row',
        'third_row',
        'full_housie',
    ];

    // Cast the JSON fields to arrays
    protected $casts = [
        'early_five' => 'array',
        'first_row' => 'array',
        'second_row' => 'array',
        'third_row' => 'array',
        'full_housie' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

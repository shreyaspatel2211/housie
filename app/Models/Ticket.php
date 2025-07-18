<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'game_id',
        'pos_1',
        'pos_2',
        'pos_3',
        'pos_4',
        'pos_5',
        'pos_6',
        'pos_7',
        'pos_8',
        'pos_9',
        'pos_10',
        'pos_11',
        'pos_12',
        'pos_13',
        'pos_14',
        'pos_15',
        'pos_16',
        'pos_17',
        'pos_18',
        'pos_19',
        'pos_20',
        'pos_21',
        'pos_22',
        'pos_23',
        'pos_24',
        'pos_25',
        'pos_26',
        'pos_27'
    ];
}

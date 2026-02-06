<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'start',
        'end',
        'description',
        'color',
        'is_public'
    ];

    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
        'is_public' => 'boolean',
    ];

    /**
     * Relasi many-to-many dengan User
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'event_user');
    }
}
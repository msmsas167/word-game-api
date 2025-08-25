<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Word extends Model
{
    use HasFactory;
    protected $fillable = ['theme_id', 'text'];

    public function theme()
    {
        return $this->belongsTo(Theme::class);
    }
}

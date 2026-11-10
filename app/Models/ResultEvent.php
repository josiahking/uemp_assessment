<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['result_id', 'actor_id', 'event', 'from_status', 'to_status', 'metadata', 'created_at'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
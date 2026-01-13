<?php

namespace Platform\Lists\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

class ListsBoard extends Model implements HasDisplayName
{
    protected $table = 'lists_boards';

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'order',
        'user_id',
        'team_id',
        'done',
        'done_at',
    ];

    protected $casts = [
        'uuid' => 'string',
        'done' => 'boolean',
        'done_at' => 'datetime',
        'order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;
            
            if (!$model->order) {
                $maxOrder = self::where('team_id', $model->team_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    public function lists()
    {
        return $this->hasMany(ListsList::class, 'board_id')->orderBy('order');
    }

    public function getDisplayName(): ?string
    {
        return $this->name;
    }
}

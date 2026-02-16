<?php

namespace Platform\Lists\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

class ListsListItem extends Model implements HasDisplayName
{
    protected $table = 'lists_list_items';

    protected $fillable = [
        'uuid',
        'list_id',
        'title',
        'description',
        'order',
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
                $maxOrder = self::where('list_id', $model->list_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo(ListsList::class, 'list_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->title;
    }
}

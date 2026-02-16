<?php

namespace Platform\Lists\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Lists\Models\ListsListItem;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class ToggleItemTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'lists.items.toggle';
    }

    public function getDescription(): string
    {
        return 'POST /lists/items/{id}/toggle - Setzt einen Listeneintrag auf erledigt oder offen (Toggle). REST-Parameter: item_id (required, integer) - Eintrags-ID. done (optional, boolean) - Explizit setzen statt umschalten.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'item_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Eintrags (ERFORDERLICH).'
                ],
                'done' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Explizit auf erledigt (true) oder offen (false) setzen. Wenn nicht angegeben, wird der aktuelle Status umgeschaltet.'
                ],
            ],
            'required' => ['item_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'item_id',
                ListsListItem::class,
                'ITEM_NOT_FOUND',
                'Der angegebene Eintrag wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $item = $validation['model'];
            $item->load('list');

            if (!$item->list) {
                return ToolResult::error('LIST_NOT_FOUND', 'Die zugehörige Liste wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $item->list);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen Eintrag nicht ändern.');
            }

            // Toggle oder explizit setzen
            $newDone = isset($arguments['done']) ? (bool) $arguments['done'] : !$item->done;

            $item->update([
                'done' => $newDone,
                'done_at' => $newDone ? now() : null,
            ]);

            $item->refresh();

            $statusText = $item->done ? 'erledigt' : 'offen';

            return ToolResult::success([
                'id' => $item->id,
                'uuid' => $item->uuid,
                'title' => $item->title,
                'done' => $item->done,
                'done_at' => $item->done_at?->toIso8601String(),
                'list_id' => $item->list_id,
                'list_name' => $item->list->name,
                'message' => "Eintrag '{$item->title}' ist jetzt {$statusText}."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Umschalten des Eintrags: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['lists', 'item', 'toggle', 'done'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => true,
            'side_effects' => ['updates'],
        ];
    }
}

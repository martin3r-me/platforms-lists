<?php

namespace Platform\Lists\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Lists\Models\ListsListItem;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateItemTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'lists.items.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /lists/items/{id} - Aktualisiert einen Listeneintrag. REST-Parameter: item_id (required, integer) - Eintrags-ID. title, description, done (optional) - Update-Felder.';
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
                'title' => ['type' => 'string', 'description' => 'Optional: Neuer Titel des Eintrags.'],
                'description' => ['type' => 'string', 'description' => 'Optional: Neue Beschreibung des Eintrags.'],
                'done' => ['type' => 'boolean', 'description' => 'Optional: Eintrag als erledigt/offen markieren.'],
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
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen Eintrag nicht bearbeiten.');
            }

            $updateData = [];
            if (isset($arguments['title'])) $updateData['title'] = $arguments['title'];
            if (isset($arguments['description'])) $updateData['description'] = $arguments['description'];
            if (isset($arguments['done'])) {
                $updateData['done'] = $arguments['done'];
                $updateData['done_at'] = $arguments['done'] ? now() : null;
            }

            if (!empty($updateData)) {
                $item->update($updateData);
            }

            $item->refresh();
            $item->load('list.board');

            return ToolResult::success([
                'id' => $item->id,
                'uuid' => $item->uuid,
                'title' => $item->title,
                'description' => $item->description,
                'order' => $item->order,
                'done' => $item->done,
                'done_at' => $item->done_at?->toIso8601String(),
                'list_id' => $item->list_id,
                'list_name' => $item->list->name,
                'updated_at' => $item->updated_at->toIso8601String(),
                'message' => "Eintrag '{$item->title}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Eintrags: ' . $e->getMessage());
        }
    }
}

<?php

namespace Platform\Lists\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Lists\Models\ListsListItem;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteItemTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'lists.items.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /lists/items/{id} - Löscht einen Listeneintrag. REST-Parameter: item_id (required, integer) - Eintrags-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'item_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Eintrags (ERFORDERLICH).'
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
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen Eintrag nicht löschen.');
            }

            $itemTitle = $item->title;
            $itemId = $item->id;
            $listId = $item->list_id;
            $listName = $item->list->name;

            $item->delete();

            return ToolResult::success([
                'item_id' => $itemId,
                'item_title' => $itemTitle,
                'list_id' => $listId,
                'list_name' => $listName,
                'message' => "Eintrag '{$itemTitle}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Eintrags: ' . $e->getMessage());
        }
    }
}

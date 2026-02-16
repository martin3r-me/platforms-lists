<?php

namespace Platform\Lists\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Lists\Models\ListsList;
use Platform\Lists\Models\ListsListItem;
use Illuminate\Support\Facades\Gate;

class ListItemsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'lists.items.GET';
    }

    public function getDescription(): string
    {
        return 'GET /lists/lists/{list_id}/items - Listet Einträge einer Liste auf. REST-Parameter: list_id (required, integer) - Listen-ID. filters, search, sort, limit/offset (optional) - Standard-Parameter.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'list_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID der Liste.'
                    ],
                ]
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $listId = $arguments['list_id'] ?? null;
            if (!$listId) {
                return ToolResult::error('VALIDATION_ERROR', 'list_id ist erforderlich.');
            }

            $list = ListsList::with('board')->find($listId);
            if (!$list) {
                return ToolResult::error('LIST_NOT_FOUND', 'Die angegebene Liste wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $list)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Liste.');
            }

            $query = ListsListItem::query()
                ->where('list_id', $listId)
                ->with('list');

            $this->applyStandardFilters($query, $arguments, ['title', 'description', 'done', 'created_at', 'updated_at']);
            $this->applyStandardSearch($query, $arguments, ['title', 'description']);
            $this->applyStandardSort($query, $arguments, ['title', 'order', 'done', 'created_at', 'updated_at'], 'order', 'asc');
            $this->applyStandardPagination($query, $arguments);

            $items = $query->get();

            $itemsList = $items->map(function($item) {
                return [
                    'id' => $item->id,
                    'uuid' => $item->uuid,
                    'title' => $item->title,
                    'description' => $item->description,
                    'order' => $item->order,
                    'done' => $item->done,
                    'done_at' => $item->done_at?->toIso8601String(),
                    'list_id' => $item->list_id,
                    'created_at' => $item->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'items' => $itemsList,
                'count' => count($itemsList),
                'list_id' => $listId,
                'list_name' => $list->name,
                'board_id' => $list->board_id,
                'board_name' => $list->board->name,
                'message' => count($itemsList) > 0
                    ? count($itemsList) . ' Eintrag/Einträge gefunden in Liste "' . $list->name . '".'
                    : 'Keine Einträge gefunden in Liste "' . $list->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Einträge: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['lists', 'item', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}

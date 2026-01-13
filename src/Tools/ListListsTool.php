<?php

namespace Platform\Lists\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Lists\Models\ListsBoard;
use Platform\Lists\Models\ListsList;
use Illuminate\Support\Facades\Gate;

class ListListsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'lists.lists.GET';
    }

    public function getDescription(): string
    {
        return 'GET /lists/boards/{board_id}/lists - Listet Listen eines Boards auf. REST-Parameter: board_id (required, integer) - Board-ID. filters, search, sort, limit/offset (optional) - Standard-Parameter.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID des Boards.'
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

            $boardId = $arguments['board_id'] ?? null;
            if (!$boardId) {
                return ToolResult::error('VALIDATION_ERROR', 'board_id ist erforderlich.');
            }

            $board = ListsBoard::find($boardId);
            if (!$board) {
                return ToolResult::error('BOARD_NOT_FOUND', 'Das angegebene Board wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $board)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Board.');
            }
            
            $query = ListsList::query()
                ->where('board_id', $boardId)
                ->with(['board', 'user', 'team']);

            $this->applyStandardFilters($query, $arguments, ['name', 'description', 'done', 'created_at', 'updated_at']);
            $this->applyStandardSearch($query, $arguments, ['name', 'description']);
            $this->applyStandardSort($query, $arguments, ['name', 'order', 'created_at', 'updated_at'], 'order', 'asc');
            $this->applyStandardPagination($query, $arguments);

            $lists = $query->get();

            $listsList = $lists->map(function($list) {
                return [
                    'id' => $list->id,
                    'uuid' => $list->uuid,
                    'name' => $list->name,
                    'description' => $list->description,
                    'board_id' => $list->board_id,
                    'board_name' => $list->board->name,
                    'team_id' => $list->team_id,
                    'user_id' => $list->user_id,
                    'done' => $list->done,
                    'done_at' => $list->done_at?->toIso8601String(),
                    'created_at' => $list->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'lists' => $listsList,
                'count' => count($listsList),
                'board_id' => $boardId,
                'board_name' => $board->name,
                'message' => count($listsList) > 0 
                    ? count($listsList) . ' Liste(n) gefunden für Board "' . $board->name . '".'
                    : 'Keine Listen gefunden für Board "' . $board->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Listen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['lists', 'list', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}

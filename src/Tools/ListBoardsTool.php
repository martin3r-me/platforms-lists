<?php

namespace Platform\Lists\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Lists\Models\ListsBoard;
use Illuminate\Support\Facades\Gate;

class ListBoardsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'lists.boards.GET';
    }

    public function getDescription(): string
    {
        return 'GET /lists/boards - Listet Listen-Boards auf. REST-Parameter: team_id (optional, integer) - Filter nach Team-ID. filters, search, sort, limit/offset (optional) - Standard-Parameter.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (optional): Filter nach Team-ID. Wenn nicht angegeben, wird automatisch das aktuelle Team verwendet.'
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

            $teamId = $arguments['team_id'] ?? $context->team?->id ?? $context->user->currentTeam?->id;
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team angegeben und kein Team im Kontext gefunden.');
            }
            
            $query = ListsBoard::query()
                ->where('team_id', $teamId)
                ->with(['user', 'team']);

            $this->applyStandardFilters($query, $arguments, ['name', 'description', 'done', 'created_at', 'updated_at']);
            $this->applyStandardSearch($query, $arguments, ['name', 'description']);
            $this->applyStandardSort($query, $arguments, ['name', 'created_at', 'updated_at', 'order'], 'name', 'asc');
            $this->applyStandardPagination($query, $arguments);

            $boards = $query->get()->filter(function ($board) use ($context) {
                try {
                    return Gate::forUser($context->user)->allows('view', $board);
                } catch (\Throwable $e) {
                    return false;
                }
            })->values();

            $boardsList = $boards->map(function($board) {
                return [
                    'id' => $board->id,
                    'uuid' => $board->uuid,
                    'name' => $board->name,
                    'description' => $board->description,
                    'team_id' => $board->team_id,
                    'user_id' => $board->user_id,
                    'done' => $board->done,
                    'done_at' => $board->done_at?->toIso8601String(),
                    'created_at' => $board->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'boards' => $boardsList,
                'count' => count($boardsList),
                'message' => count($boardsList) > 0 
                    ? count($boardsList) . ' Board(s) gefunden.'
                    : 'Keine Boards gefunden.'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['lists', 'board', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}

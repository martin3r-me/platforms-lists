<?php

namespace Platform\Lists\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Lists\Models\ListsBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class GetBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'lists.board.GET';
    }

    public function getDescription(): string
    {
        return 'GET /lists/boards/{id} - Ruft ein einzelnes Board ab. REST-Parameter: id (required, integer) - Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des Boards.'
                ]
            ],
            'required' => ['id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            if (empty($arguments['id'])) {
                return ToolResult::error('VALIDATION_ERROR', 'Board-ID ist erforderlich.');
            }

            $board = ListsBoard::with(['user', 'team', 'lists'])->find($arguments['id']);

            if (!$board) {
                return ToolResult::error('BOARD_NOT_FOUND', 'Das angegebene Board wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('view', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Board.');
            }

            return ToolResult::success([
                'id' => $board->id,
                'uuid' => $board->uuid,
                'name' => $board->name,
                'description' => $board->description,
                'team_id' => $board->team_id,
                'user_id' => $board->user_id,
                'done' => $board->done,
                'done_at' => $board->done_at?->toIso8601String(),
                'created_at' => $board->created_at->toIso8601String(),
                'lists_count' => $board->lists->count(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['lists', 'board', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}

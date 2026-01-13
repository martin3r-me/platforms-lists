<?php

namespace Platform\Lists\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Lists\Models\ListsBoard;
use Platform\Lists\Models\ListsList;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateListTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'lists.lists.POST';
    }

    public function getDescription(): string
    {
        return 'POST /lists/boards/{board_id}/lists - Erstellt eine neue Liste. REST-Parameter: board_id (required, integer) - Board-ID. name (optional, string) - Listen-Name. description (optional, string) - Beschreibung.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Boards, zu dem die Liste gehört (ERFORDERLICH).'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name der Liste. Wenn nicht angegeben, wird automatisch "Neue Liste" verwendet.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Beschreibung der Liste.'
                ],
            ],
            'required' => ['board_id']
        ];
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

            try {
                Gate::forUser($context->user)->authorize('update', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Listen für dieses Board erstellen.');
            }

            $name = $arguments['name'] ?? 'Neue Liste';

            $list = ListsList::create([
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'user_id' => $context->user->id,
                'team_id' => $board->team_id,
                'board_id' => $board->id,
            ]);

            $list->load(['board', 'user', 'team']);

            return ToolResult::success([
                'id' => $list->id,
                'uuid' => $list->uuid,
                'name' => $list->name,
                'description' => $list->description,
                'board_id' => $list->board_id,
                'board_name' => $list->board->name,
                'team_id' => $list->team_id,
                'created_at' => $list->created_at->toIso8601String(),
                'message' => "Liste '{$list->name}' erfolgreich für Board '{$list->board->name}' erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Liste: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['lists', 'list', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}

<?php

namespace Platform\Lists\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Lists\Models\ListsBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'lists.boards.POST';
    }

    public function getDescription(): string
    {
        return 'POST /lists/boards - Erstellt ein neues Listen-Board. REST-Parameter: name (optional, string) - Board-Name. description (optional, string) - Beschreibung.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Name des Boards. Wenn nicht angegeben, wird automatisch "Neues Board" verwendet.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Beschreibung des Boards.'
                ],
            ],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('create', ListsBoard::class);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Boards erstellen.');
            }

            $name = $arguments['name'] ?? 'Neues Board';

            $board = ListsBoard::create([
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'user_id' => $context->user->id,
                'team_id' => $context->user->currentTeam->id,
            ]);

            $board->load(['user', 'team']);

            return ToolResult::success([
                'id' => $board->id,
                'uuid' => $board->uuid,
                'name' => $board->name,
                'description' => $board->description,
                'team_id' => $board->team_id,
                'created_at' => $board->created_at->toIso8601String(),
                'message' => "Board '{$board->name}' erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['lists', 'board', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}

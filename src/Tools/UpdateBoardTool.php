<?php

namespace Platform\Lists\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Lists\Models\ListsBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'lists.boards.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /lists/boards/{id} - Aktualisiert ein Board. REST-Parameter: board_id (required, integer) - Board-ID. name, description, done (optional) - Update-Felder.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Boards (ERFORDERLICH).'
                ],
                'name' => ['type' => 'string', 'description' => 'Optional: Name des Boards.'],
                'description' => ['type' => 'string', 'description' => 'Optional: Beschreibung des Boards.'],
                'done' => ['type' => 'boolean', 'description' => 'Optional: Board als erledigt markieren.'],
            ],
            'required' => ['board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'board_id',
                ListsBoard::class,
                'BOARD_NOT_FOUND',
                'Das angegebene Board wurde nicht gefunden.'
            );
            
            if ($validation['error']) {
                return $validation['error'];
            }
            
            $board = $validation['model'];
            
            try {
                Gate::forUser($context->user)->authorize('update', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Board nicht bearbeiten.');
            }

            $updateData = [];
            if (isset($arguments['name'])) $updateData['name'] = $arguments['name'];
            if (isset($arguments['description'])) $updateData['description'] = $arguments['description'];
            if (isset($arguments['done'])) {
                $updateData['done'] = $arguments['done'];
                $updateData['done_at'] = $arguments['done'] ? now() : null;
            }

            if (!empty($updateData)) {
                $board->update($updateData);
            }

            $board->refresh();

            return ToolResult::success([
                'id' => $board->id,
                'name' => $board->name,
                'description' => $board->description,
                'done' => $board->done,
                'done_at' => $board->done_at?->toIso8601String(),
                'updated_at' => $board->updated_at->toIso8601String(),
                'message' => "Board '{$board->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Boards: ' . $e->getMessage());
        }
    }
}

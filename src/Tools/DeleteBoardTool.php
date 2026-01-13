<?php

namespace Platform\Lists\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Lists\Models\ListsBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'lists.boards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /lists/boards/{id} - Löscht ein Board. REST-Parameter: board_id (required, integer) - Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Boards (ERFORDERLICH).'
                ],
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
                Gate::forUser($context->user)->authorize('delete', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Board nicht löschen.');
            }

            $boardName = $board->name;
            $boardId = $board->id;

            $board->delete();

            return ToolResult::success([
                'board_id' => $boardId,
                'board_name' => $boardName,
                'message' => "Board '{$boardName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Boards: ' . $e->getMessage());
        }
    }
}

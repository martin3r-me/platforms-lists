<?php

namespace Platform\Lists\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Lists\Models\ListsList;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteListTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'lists.lists.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /lists/lists/{id} - Löscht eine Liste. REST-Parameter: list_id (required, integer) - Listen-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'list_id' => [
                    'type' => 'integer',
                    'description' => 'ID der zu löschenden Liste (ERFORDERLICH).'
                ],
            ],
            'required' => ['list_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'list_id',
                ListsList::class,
                'LIST_NOT_FOUND',
                'Die angegebene Liste wurde nicht gefunden.'
            );
            
            if ($validation['error']) {
                return $validation['error'];
            }
            
            $list = $validation['model'];
            
            try {
                Gate::forUser($context->user)->authorize('delete', $list);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Liste nicht löschen.');
            }

            $listName = $list->name;
            $listId = $list->id;
            $boardId = $list->board_id;

            $list->delete();

            return ToolResult::success([
                'list_id' => $listId,
                'list_name' => $listName,
                'board_id' => $boardId,
                'message' => "Liste '{$listName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen der Liste: ' . $e->getMessage());
        }
    }
}

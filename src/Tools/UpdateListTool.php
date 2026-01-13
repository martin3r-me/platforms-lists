<?php

namespace Platform\Lists\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Lists\Models\ListsList;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateListTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'lists.lists.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /lists/lists/{id} - Aktualisiert eine Liste. REST-Parameter: list_id (required, integer) - Listen-ID. name, description, done (optional) - Update-Felder.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'list_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Liste (ERFORDERLICH).'
                ],
                'name' => ['type' => 'string', 'description' => 'Optional: Name der Liste.'],
                'description' => ['type' => 'string', 'description' => 'Optional: Beschreibung der Liste.'],
                'done' => ['type' => 'boolean', 'description' => 'Optional: Liste als erledigt markieren.'],
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
                Gate::forUser($context->user)->authorize('update', $list);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Liste nicht bearbeiten.');
            }

            $updateData = [];
            if (isset($arguments['name'])) $updateData['name'] = $arguments['name'];
            if (isset($arguments['description'])) $updateData['description'] = $arguments['description'];
            if (isset($arguments['done'])) {
                $updateData['done'] = $arguments['done'];
                $updateData['done_at'] = $arguments['done'] ? now() : null;
            }

            if (!empty($updateData)) {
                $list->update($updateData);
            }

            $list->refresh();
            $list->load('board');

            return ToolResult::success([
                'id' => $list->id,
                'name' => $list->name,
                'description' => $list->description,
                'board_id' => $list->board_id,
                'board_name' => $list->board->name,
                'done' => $list->done,
                'done_at' => $list->done_at?->toIso8601String(),
                'updated_at' => $list->updated_at->toIso8601String(),
                'message' => "Liste '{$list->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Liste: ' . $e->getMessage());
        }
    }
}

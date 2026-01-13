<?php

namespace Platform\Lists\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Lists\Models\ListsList;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class GetListTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'lists.list.GET';
    }

    public function getDescription(): string
    {
        return 'GET /lists/lists/{id} - Ruft eine einzelne Liste ab. REST-Parameter: id (required, integer) - Listen-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID der Liste.'
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
                return ToolResult::error('VALIDATION_ERROR', 'Listen-ID ist erforderlich.');
            }

            $list = ListsList::with(['board', 'user', 'team'])->find($arguments['id']);

            if (!$list) {
                return ToolResult::error('LIST_NOT_FOUND', 'Die angegebene Liste wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('view', $list);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Liste.');
            }

            return ToolResult::success([
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
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Liste: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['lists', 'list', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}

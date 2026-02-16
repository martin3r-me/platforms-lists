<?php

namespace Platform\Lists\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Lists\Models\ListsList;
use Platform\Lists\Models\ListsListItem;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateItemTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'lists.items.POST';
    }

    public function getDescription(): string
    {
        return 'POST /lists/lists/{list_id}/items - Erstellt einen oder mehrere Einträge in einer Liste. REST-Parameter: list_id (required, integer) - Listen-ID. title (optional, string) - Titel des Eintrags. description (optional, string) - Beschreibung. items (optional, array) - Mehrere Einträge auf einmal erstellen (Bulk). Entweder title ODER items angeben.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'list_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Liste, zu der der Eintrag gehört (ERFORDERLICH).'
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Titel des Eintrags. Wenn nicht angegeben, wird "Neuer Eintrag" verwendet. Wird ignoriert, wenn items angegeben ist.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Beschreibung des Eintrags. Wird ignoriert, wenn items angegeben ist.'
                ],
                'items' => [
                    'type' => 'array',
                    'description' => 'Mehrere Einträge auf einmal erstellen (Bulk). Jedes Element: {title: string, description?: string}. Wenn angegeben, werden title/description auf Top-Level ignoriert.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string', 'description' => 'Titel des Eintrags.'],
                            'description' => ['type' => 'string', 'description' => 'Beschreibung des Eintrags.'],
                        ],
                        'required' => ['title']
                    ]
                ],
            ],
            'required' => ['list_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $listId = $arguments['list_id'] ?? null;
            if (!$listId) {
                return ToolResult::error('VALIDATION_ERROR', 'list_id ist erforderlich.');
            }

            $list = ListsList::with('board')->find($listId);
            if (!$list) {
                return ToolResult::error('LIST_NOT_FOUND', 'Die angegebene Liste wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $list);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Einträge für diese Liste erstellen.');
            }

            // Bulk-Modus: mehrere Einträge auf einmal
            if (!empty($arguments['items']) && is_array($arguments['items'])) {
                $createdItems = [];
                foreach ($arguments['items'] as $itemData) {
                    $title = $itemData['title'] ?? 'Neuer Eintrag';
                    $item = ListsListItem::create([
                        'title' => $title,
                        'description' => $itemData['description'] ?? null,
                        'list_id' => $list->id,
                        'done' => false,
                    ]);
                    $createdItems[] = [
                        'id' => $item->id,
                        'uuid' => $item->uuid,
                        'title' => $item->title,
                        'description' => $item->description,
                        'order' => $item->order,
                        'done' => $item->done,
                    ];
                }

                return ToolResult::success([
                    'items' => $createdItems,
                    'count' => count($createdItems),
                    'list_id' => $list->id,
                    'list_name' => $list->name,
                    'board_id' => $list->board_id,
                    'board_name' => $list->board->name,
                    'message' => count($createdItems) . " Eintrag/Einträge erfolgreich in Liste '{$list->name}' erstellt."
                ]);
            }

            // Einzelner Eintrag
            $title = $arguments['title'] ?? 'Neuer Eintrag';

            $item = ListsListItem::create([
                'title' => $title,
                'description' => $arguments['description'] ?? null,
                'list_id' => $list->id,
                'done' => false,
            ]);

            return ToolResult::success([
                'id' => $item->id,
                'uuid' => $item->uuid,
                'title' => $item->title,
                'description' => $item->description,
                'order' => $item->order,
                'done' => $item->done,
                'list_id' => $list->id,
                'list_name' => $list->name,
                'board_id' => $list->board_id,
                'board_name' => $list->board->name,
                'message' => "Eintrag '{$item->title}' erfolgreich in Liste '{$list->name}' erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Eintrags: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['lists', 'item', 'create', 'bulk'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}

<?php

namespace Platform\Lists\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Lists\Models\ListsList;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;

class ListItem extends Component
{
    public ListsList $list;

    public function mount(ListsList $listsList)
    {
        $this->list = $listsList->fresh()->load('items');
        
        // Berechtigung prüfen
        $this->authorize('view', $this->list);
    }

    #[On('updateList')]
    public function updateList()
    {
        $this->list->refresh();
        $this->list->load('items');
    }

    public function updateListName($name)
    {
        $this->authorize('update', $this->list);

        $name = trim($name);
        if ($name === '' || $name === $this->list->name) {
            return;
        }

        $this->list->update(['name' => $name]);
        $this->list->refresh();
        $this->list->load('items');

        $this->dispatch('updateSidebar');

        $this->dispatch('notifications:store', [
            'title' => 'Liste umbenannt',
            'message' => "Liste wurde in '{$name}' umbenannt.",
            'notice_type' => 'success',
            'noticable_type' => get_class($this->list),
            'noticable_id'   => $this->list->getKey(),
        ]);
    }

    public function rules(): array
    {
        return [
            'list.name' => 'required|string|max:255',
            'list.description' => 'nullable|string',
        ];
    }

    #[Computed]
    public function isDirty(): bool
    {
        if (!$this->list) {
            return false;
        }
        
        return count($this->list->getDirty()) > 0;
    }

    public function save()
    {
        $this->validate();
        
        // Policy prüfen
        $this->authorize('update', $this->list);
        
        // Speichern
        $this->list->save();
        $this->list->refresh();
        
        $this->dispatch('updateSidebar');
        $this->dispatch('updateList');
        
        $this->dispatch('notifications:store', [
            'title' => 'Liste gespeichert',
            'message' => 'Die Änderungen wurden erfolgreich gespeichert.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->list),
            'noticable_id'   => $this->list->getKey(),
        ]);
    }

    public function updated($propertyName)
    {
        if (str_starts_with($propertyName, 'list.')) {
            $field = str_replace('list.', '', $propertyName);
            $this->validateOnly("list.$field");
        }
    }

    public function updateItemOrder($items)
    {
        $this->authorize('update', $this->list);
        
        foreach ($items as $item) {
            $listItem = $this->list->items()->find($item['value']);
            if ($listItem) {
                $listItem->update(['order' => $item['order']]);
            }
        }
        
        $this->list->refresh();
        $this->list->load('items');
    }

    public function createItem()
    {
        $this->authorize('update', $this->list);
        
        $item = \Platform\Lists\Models\ListsListItem::create([
            'list_id' => $this->list->id,
            'title' => 'Neues Element',
            'description' => null,
        ]);
        
        $this->list->refresh();
        $this->list->load('items');
    }

    public function toggleItemDone($itemId)
    {
        $this->authorize('update', $this->list);
        
        $item = $this->list->items()->find($itemId);
        if ($item) {
            $item->done = !$item->done;
            $item->done_at = $item->done ? now() : null;
            $item->save();
        }
        
        $this->list->refresh();
        $this->list->load('items');
    }

    public function deleteItem($itemId)
    {
        $this->authorize('update', $this->list);
        
        $item = $this->list->items()->find($itemId);
        if ($item) {
            $item->delete();
        }
        
        $this->list->refresh();
        $this->list->load('items');
    }

    public function updateItemTitle($itemId, $title)
    {
        $this->authorize('update', $this->list);
        
        $item = $this->list->items()->find($itemId);
        if ($item) {
            $item->title = $title;
            $item->save();
        }
        
        $this->list->refresh();
        $this->list->load('items');
    }

    public function render()
    {
        $user = Auth::user();

        return view('lists::livewire.list', [
            'user' => $user,
        ])->layout('platform::layouts.app');
    }
}

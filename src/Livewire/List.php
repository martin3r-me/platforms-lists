<?php

namespace Platform\Lists\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Lists\Models\ListsList;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;

class List extends Component
{
    public ListsList $list;

    public function mount(ListsList $listsList)
    {
        $this->list = $listsList->fresh();
        
        // Berechtigung prüfen
        $this->authorize('view', $this->list);
    }

    #[On('updateList')] 
    public function updateList()
    {
        $this->list->refresh();
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

    public function render()
    {
        $user = Auth::user();

        return view('lists::livewire.list', [
            'user' => $user,
        ])->layout('platform::layouts.app');
    }
}

<?php

namespace Modules\Heya\Livewire;

use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Heya\Models\Room;

class RoomManagement extends Component
{
    public bool $showModal = false;

    public ?int $roomId = null;

    /** @var array{name: string} */
    public array $form = ['name' => ''];

    public function create(): void
    {
        Gate::authorize('create', Room::class);
        $this->resetValidation();
        $this->roomId = null;
        $this->form = ['name' => ''];
        $this->showModal = true;
    }

    public function edit(int $roomId): void
    {
        $room = Room::query()->whereKey($roomId)->firstOrFail();
        Gate::authorize('update', $room);
        $this->resetValidation();
        $this->roomId = $room->id;
        $this->form = ['name' => $room->name];
        $this->showModal = true;
    }

    public function save(): void
    {
        $room = $this->roomId === null ? new Room : Room::query()->whereKey($this->roomId)->firstOrFail();
        Gate::authorize($room->exists ? 'update' : 'create', $room->exists ? $room : Room::class);

        $validated = $this->validate([
            'form.name' => ['required', 'string', 'max:255', Rule::unique((new Room)->getTable(), 'name')->ignore($room)],
        ]);

        $isNew = ! $room->exists;
        $room->fill($validated['form'])->save();
        $this->showModal = false;
        Flux::toast(variant: 'success', text: $isNew ? '会議室を登録しました。' : '会議室を更新しました。');
    }

    public function toggleActive(int $roomId): void
    {
        $room = Room::query()->whereKey($roomId)->firstOrFail();
        Gate::authorize('update', $room);
        $room->update(['is_active' => ! $room->is_active]);
        Flux::toast(variant: 'success', text: $room->is_active ? '会議室を有効にしました。' : '会議室を無効にしました。');
    }

    public function render(): View
    {
        return view('heya::livewire.room-management', [
            'rooms' => Room::query()->withCount('bookings')->orderBy('name')->get(),
        ]);
    }
}

<?php

namespace Modules\Syako\Livewire;

use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Syako\Models\Vehicle;

class VehicleManagement extends Component
{
    public bool $showModal = false;

    public ?int $vehicleId = null;

    /** @var array{name: string, color_code: string, vehicle_number: string, manufacturer: string, model: string, model_code: string} */
    public array $form = [
        'name' => '',
        'color_code' => '#D1D5DB',
        'vehicle_number' => '',
        'manufacturer' => '',
        'model' => '',
        'model_code' => '',
    ];

    public function create(): void
    {
        Gate::authorize('create', Vehicle::class);
        $this->resetValidation();
        $this->vehicleId = null;
        $this->form = [
            'name' => '',
            'color_code' => '#D1D5DB',
            'vehicle_number' => '',
            'manufacturer' => '',
            'model' => '',
            'model_code' => '',
        ];
        $this->showModal = true;
    }

    public function edit(int $vehicleId): void
    {
        $vehicle = Vehicle::query()->whereKey($vehicleId)->firstOrFail();
        Gate::authorize('update', $vehicle);
        $this->resetValidation();
        $this->vehicleId = $vehicle->id;
        $this->form = [
            'name' => $vehicle->name,
            'color_code' => $vehicle->color_code,
            'vehicle_number' => $vehicle->vehicle_number,
            'manufacturer' => $vehicle->manufacturer ?? '',
            'model' => $vehicle->model ?? '',
            'model_code' => $vehicle->model_code ?? '',
        ];
        $this->showModal = true;
    }

    public function save(): void
    {
        $vehicle = $this->vehicleId === null ? new Vehicle : Vehicle::query()->whereKey($this->vehicleId)->firstOrFail();
        Gate::authorize($vehicle->exists ? 'update' : 'create', $vehicle->exists ? $vehicle : Vehicle::class);

        $validated = $this->validate([
            'form.name' => ['required', 'string', 'max:255', Rule::unique((new Vehicle)->getTable(), 'name')->ignore($vehicle)],
            'form.color_code' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'form.vehicle_number' => ['required', 'string', 'max:255', Rule::unique((new Vehicle)->getTable(), 'vehicle_number')->ignore($vehicle)],
            'form.manufacturer' => ['nullable', 'string', 'max:255'],
            'form.model' => ['nullable', 'string', 'max:255'],
            'form.model_code' => ['nullable', 'string', 'max:255'],
        ]);

        $isNew = ! $vehicle->exists;
        $vehicle->fill($validated['form'])->save();
        $this->showModal = false;
        Flux::toast(variant: 'success', text: $isNew ? '車両を登録しました。' : '車両を更新しました。');
    }

    public function toggleActive(int $vehicleId): void
    {
        $vehicle = Vehicle::query()->whereKey($vehicleId)->firstOrFail();
        Gate::authorize('update', $vehicle);
        $vehicle->update(['is_active' => ! $vehicle->is_active]);
        Flux::toast(variant: 'success', text: $vehicle->is_active ? '車両を有効にしました。' : '車両を無効にしました。');
    }

    public function delete(int $vehicleId): void
    {
        $vehicle = Vehicle::query()->whereKey($vehicleId)->firstOrFail();
        Gate::authorize('delete', $vehicle);

        if ($vehicle->bookings()->exists()) {
            Flux::toast(variant: 'danger', text: '予約がある車両は削除できません。');

            return;
        }

        $vehicle->delete();
        Flux::toast(variant: 'success', text: '車両を削除しました。');
    }

    public function render(): View
    {
        return view('syako::livewire.vehicle-management', [
            'vehicles' => Vehicle::query()->withCount('bookings')->orderBy('name')->get(),
        ]);
    }
}

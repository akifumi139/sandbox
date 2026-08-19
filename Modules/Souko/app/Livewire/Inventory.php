<?php

namespace Modules\Souko\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Souko\Models\Tool;

class Inventory extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public bool $showQrModal = false;

    public string $qrManagementNumber = '';

    public array $form = [
        'management_number' => '',
        'name' => '',
        'model' => '',
        'manufacturer' => '',
        'status' => 'available',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function tools()
    {
        $query = Tool::query()
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($toolQuery) use ($search): void {
                    $toolQuery->where('management_number', 'like', $search)
                        ->orWhere('name', 'like', $search)
                        ->orWhere('model', 'like', $search);
                });
            })
            ->when($this->status !== '', function (Builder $query): void {
                $query->where('status', $this->status);
            })
            ->orderBy('management_number');

        return $query->paginate(10);
    }

    public function saveTool(): void
    {
        $this->validate([
            'form.management_number' => ['required', 'string', 'max:255', 'unique:souko__tools,management_number'],
            'form.name' => ['required', 'string', 'max:255'],
            'form.model' => ['nullable', 'string', 'max:255'],
            'form.manufacturer' => ['nullable', 'string', 'max:255'],
            'form.status' => ['required', 'in:available,rented,maintenance,disposed'],
        ]);

        Tool::query()->create($this->form);

        $this->reset('form');
        $this->form['status'] = 'available';
        unset($this->tools);

        session()->flash('message', '工具を登録しました。');
    }

    public function deleteTool(int $toolId): void
    {
        $tool = Tool::query()->findOrFail($toolId);
        $tool->delete();

        unset($this->tools);
        session()->flash('message', $tool->name.' を削除しました。');
    }

    public function openQrModal(string $managementNumber): void
    {
        $this->qrManagementNumber = $managementNumber;
        $this->showQrModal = true;
    }

    public function render()
    {
        $query = Tool::query()
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($toolQuery) use ($search): void {
                    $toolQuery->where('management_number', 'like', $search)
                        ->orWhere('name', 'like', $search)
                        ->orWhere('model', 'like', $search);
                });
            })
            ->when($this->status !== '', function (Builder $query): void {
                $query->where('status', $this->status);
            });

        $totalTools = $query->count();

        return view('souko::livewire.inventory', [
            'availableTools' => (clone $query)->where('status', 'available')->count(),
            'rentedTools' => (clone $query)->where('status', 'rented')->count(),
            'maintenanceTools' => (clone $query)->where('status', 'maintenance')->count(),
        ]);
    }
}

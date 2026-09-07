<?php

namespace Modules\Souko\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Souko\Actions\GenerateToolQrPdf;
use Modules\Souko\Models\Tool;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Inventory extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $type = '';

    /** @var array<int, int|string> */
    public array $selectedToolIds = [];

    public bool $showQrModal = false;

    public string $qrManagementNumber = '';

    public array $form = [
        'management_number' => '',
        'name' => '',
        'type' => '',
        'model' => '',
        'manufacturer' => '',
        'status' => 'available',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->selectedToolIds = [];
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
        $this->selectedToolIds = [];
    }

    public function updatingType(): void
    {
        $this->resetPage();
        $this->selectedToolIds = [];
    }

    public function updatingPage(): void
    {
        $this->selectedToolIds = [];
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
                        ->orWhere('type', 'like', $search)
                        ->orWhere('model', 'like', $search);
                });
            })
            ->when($this->status !== '', function (Builder $query): void {
                $query->where('status', $this->status);
            })
            ->when($this->type !== '', function (Builder $query): void {
                $query->where('type', $this->type);
            })
            ->orderBy('type')
            ->orderBy('name')
            ->orderBy('management_number');

        return $query->paginate(200);
    }

    #[Computed]
    public function toolTypes()
    {
        return Tool::query()
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');
    }

    public function saveTool(): void
    {
        $this->validate([
            'form.management_number' => ['required', 'string', 'max:255', 'unique:souko__tools,management_number'],
            'form.name' => ['required', 'string', 'max:255'],
            'form.type' => ['nullable', 'string', 'max:255'],
            'form.model' => ['nullable', 'string', 'max:255'],
            'form.manufacturer' => ['nullable', 'string', 'max:255'],
            'form.status' => ['required', 'in:available,rented,maintenance,disposed'],
        ]);

        $this->form['type'] = trim($this->form['type']);

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

        $this->selectedToolIds = array_values(array_diff(
            array_map('intval', $this->selectedToolIds),
            [$toolId],
        ));
        unset($this->tools);
        session()->flash('message', $tool->name.' を削除しました。');
    }

    public function toggleSelectAllVisibleTools(): void
    {
        $visibleToolIds = $this->tools->getCollection()
            ->pluck('id')
            ->map(fn (int|string $toolId): int => (int) $toolId)
            ->all();

        $selectedToolIds = array_map('intval', $this->selectedToolIds);

        if ($visibleToolIds !== [] && count(array_diff($visibleToolIds, $selectedToolIds)) === 0) {
            $this->selectedToolIds = [];

            return;
        }

        $this->selectedToolIds = $visibleToolIds;
    }

    public function exportSelectedToolQrCodes(GenerateToolQrPdf $generateToolQrPdf): ?StreamedResponse
    {
        $selectedToolIds = array_values(array_unique(array_map('intval', $this->selectedToolIds)));

        if ($selectedToolIds === []) {
            $this->addError('selectedToolIds', 'PDFに出力する工具を選択してください。');

            return null;
        }

        $visibleToolIds = $this->tools->getCollection()
            ->pluck('id')
            ->map(fn (int|string $toolId): int => (int) $toolId)
            ->all();

        if (array_diff($selectedToolIds, $visibleToolIds) !== []) {
            $this->addError('selectedToolIds', '現在表示中の工具だけを選択してください。');

            return null;
        }

        $this->resetValidation('selectedToolIds');
        $this->selectedToolIds = [];

        return $generateToolQrPdf->handle($selectedToolIds);
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
                        ->orWhere('type', 'like', $search)
                        ->orWhere('model', 'like', $search);
                });
            })
            ->when($this->status !== '', function (Builder $query): void {
                $query->where('status', $this->status);
            })
            ->when($this->type !== '', function (Builder $query): void {
                $query->where('type', $this->type);
            });

        $totalTools = $query->count();

        return view('souko::livewire.inventory', [
            'availableTools' => (clone $query)->where('status', 'available')->count(),
            'rentedTools' => (clone $query)->where('status', 'rented')->count(),
            'maintenanceTools' => (clone $query)->where('status', 'maintenance')->count(),
        ]);
    }
}

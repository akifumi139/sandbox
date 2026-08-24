<?php

namespace Modules\Souko\Livewire;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Souko\Models\Tool;
use Modules\Souko\Models\ToolLog;

class ReturnCounter extends Component
{
    use WithPagination;

    public string $search = '';

    public array $selectedToolIds = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleSelectAll(): void
    {
        $ids = $this->borrowedTools->getCollection()->pluck('tool_id')->filter()->all();

        if (count($this->selectedToolIds) === count($ids)) {
            $this->selectedToolIds = [];

            return;
        }

        $this->selectedToolIds = $ids;
    }

    #[Computed]
    public function borrowedTools(): LengthAwarePaginator
    {
        $latestToolLogIds = ToolLog::query()
            ->selectRaw('MAX(id)')
            ->whereNull('return_at')
            ->groupBy('tool_id');

        $query = ToolLog::query()
            ->whereNull('return_at')
            ->whereIn('id', $latestToolLogIds)
            ->whereHas('tool', fn ($toolQuery) => $toolQuery->where('status', 'rented'))
            ->with('tool', 'user')
            ->latest('borrow_at');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('user_name', 'like', "%{$this->search}%")
                    ->orWhereHas('tool', function ($toolQuery) {
                        $toolQuery->where('name', 'like', "%{$this->search}%")
                            ->orWhere('management_number', 'like', "%{$this->search}%");
                    });
            });
        }

        return $query->paginate(100);
    }

    public function returnTool(int $toolId): void
    {
        $tool = DB::transaction(fn () => $this->returnBorrowedTool($toolId));
        unset($this->borrowedTools);

        if ($tool === null) {
            session()->flash('message', 'この工具は返却できません。');

            return;
        }

        session()->flash('message', "「{$tool->name}」の返却を完了しました。");
    }

    public function bulkReturn(): void
    {
        $toolIds = array_values(array_unique(array_map('intval', $this->selectedToolIds)));

        if ($toolIds === []) {
            return;
        }

        $returnedCount = DB::transaction(function () use ($toolIds): int {
            return collect($toolIds)
                ->filter(fn (int $toolId): bool => $this->returnBorrowedTool($toolId) !== null)
                ->count();
        });

        $this->selectedToolIds = [];
        unset($this->borrowedTools);

        session()->flash('message', $returnedCount.'件の返却を完了しました。');
    }

    private function returnBorrowedTool(int $toolId): ?Tool
    {
        $tool = Tool::query()->lockForUpdate()->find($toolId);

        if ($tool === null || $tool->status !== 'rented') {
            return null;
        }

        $toolLog = ToolLog::query()
            ->where('tool_id', $toolId)
            ->whereNull('return_at')
            ->latest('borrow_at')
            ->lockForUpdate()
            ->first();

        if ($toolLog === null) {
            return null;
        }

        $toolLog->update(['return_at' => now()]);

        $tool->update(['status' => 'available']);

        return $tool;
    }

    public function render()
    {
        return view('souko::livewire.return-counter');
    }
}

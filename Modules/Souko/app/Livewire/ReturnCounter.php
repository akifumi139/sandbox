<?php

namespace Modules\Souko\Livewire;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
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
    public function borrowedTools()
    {
        $query = ToolLog::query()
            ->where('action_type', 'borrow')
            ->whereHas('tool', fn ($toolQuery) => $toolQuery->where('status', 'rented'))
            ->with('tool', 'user')
            ->latest('logged_at');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('user_name', 'like', "%{$this->search}%")
                    ->orWhereHas('tool', function ($toolQuery) {
                        $toolQuery->where('name', 'like', "%{$this->search}%")
                            ->orWhere('management_number', 'like', "%{$this->search}%");
                    });
            });
        }

        $logs = $query->get();
        $latestLogsByTool = $logs
            ->groupBy('tool_id')
            ->map(fn ($toolLogs) => $toolLogs->sortByDesc('logged_at')->first())
            ->filter()
            ->sortByDesc('logged_at')
            ->values();

        $page = (int) ($this->getPage() ?? 1);
        $perPage = 100;
        $items = $latestLogsByTool
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        return new LengthAwarePaginator(
            $items,
            $latestLogsByTool->count(),
            $perPage,
            $page,
            ['path' => request()->path(), 'query' => request()->query()],
        );
    }

    public function returnTool(int $toolId): void
    {
        $tool = Tool::query()->findOrFail($toolId);

        $borrowLog = ToolLog::query()
            ->where('tool_id', $toolId)
            ->where('action_type', 'borrow')
            ->latest('logged_at')
            ->first();

        $userName = trim((string) ($borrowLog?->user_name ?: $borrowLog?->user?->name ?? ''));

        ToolLog::query()->create([
            'tool_id' => $tool->getKey(),
            'action_type' => 'return',
            'user_id' => $borrowLog?->user_id ?? Auth::id(),
            'user_name' => $userName,
            'logged_at' => now(),
            'note' => null,
        ]);

        $tool->update(['status' => 'available']);
        unset($this->borrowedTools);

        session()->flash('message', "「{$tool->name}」の返却を完了しました。");
    }

    public function bulkReturn(): void
    {
        $toolIds = array_values(array_unique(array_map('intval', $this->selectedToolIds)));

        if ($toolIds === []) {
            return;
        }

        $tools = Tool::query()->whereIn('id', $toolIds)->get()->keyBy('id');
        $returnedCount = 0;

        foreach ($toolIds as $toolId) {
            $tool = $tools->get($toolId);

            if ($tool === null || $tool->status !== 'rented') {
                continue;
            }

            $borrowLog = ToolLog::query()
                ->where('tool_id', $toolId)
                ->where('action_type', 'borrow')
                ->latest('logged_at')
                ->first();

            $userName = trim((string) ($borrowLog?->user_name ?: $borrowLog?->user?->name ?? ''));

            ToolLog::query()->create([
                'tool_id' => $tool->getKey(),
                'action_type' => 'return',
                'user_id' => $borrowLog?->user_id ?? Auth::id(),
                'user_name' => $userName,
                'logged_at' => now(),
                'note' => null,
            ]);

            $tool->update(['status' => 'available']);
            $returnedCount++;
        }

        $this->selectedToolIds = [];
        unset($this->borrowedTools);

        session()->flash('message', $returnedCount.'件の返却を完了しました。');
    }

    public function render()
    {
        return view('souko::livewire.return-counter');
    }
}

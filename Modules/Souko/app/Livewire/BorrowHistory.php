<?php

namespace Modules\Souko\Livewire;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Souko\Models\ToolLog;

class BorrowHistory extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function logs(): LengthAwarePaginator
    {
        $query = ToolLog::query()
            ->with(['tool', 'user'])
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

        if ($this->status === 'active') {
            $query->whereNull('return_at');
        }

        if ($this->status === 'returned') {
            $query->whereNotNull('return_at');
        }

        return $query->paginate(15);
    }

    public function render()
    {
        return view('souko::livewire.borrow-history');
    }
}

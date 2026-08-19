<?php

namespace Modules\Souko\Livewire;

use Livewire\Component;
use Modules\Souko\Models\Tool;

class Inventory extends Component
{
    public string $search = '';

    public string $status = '';

    public function render()
    {
        $tools = Tool::query()
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($toolQuery) use ($search): void {
                    $toolQuery->where('management_number', 'like', $search)
                        ->orWhere('name', 'like', $search)
                        ->orWhere('model', 'like', $search);
                });
            })
            ->when($this->status !== '', function ($query): void {
                $query->where('status', $this->status);
            })
            ->orderBy('management_number')
            ->get();

        return view('souko::livewire.inventory', [
            'tools' => $tools,
            'totalTools' => $tools->count(),
            'availableTools' => $tools->where('status', 'available')->count(),
            'rentedTools' => $tools->where('status', 'rented')->count(),
            'maintenanceTools' => $tools->where('status', 'maintenance')->count(),
        ]);
    }
}

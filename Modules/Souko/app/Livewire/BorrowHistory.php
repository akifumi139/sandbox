<?php

namespace Modules\Souko\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Souko\Models\ToolLog;

class BorrowHistory extends Component
{
    use WithPagination;

    // 検索・絞り込み条件
    public string $search = '';

    public string $status = 'all'; // all, borrow, return

    // 検索条件変更時にページを1ページ目に戻す
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    /**
     * ログ一覧を Computed プロパティでキャッシュ・参照する
     */
    #[Computed]
    public function logs()
    {
        $query = ToolLog::with('tool')
            ->latest('logged_at');

        // キーワード検索（使用者名・工具名・管理番号）
        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('user_name', 'like', "%{$this->search}%")
                    ->orWhereHas('tool', function ($toolQuery) {
                        $toolQuery->where('name', 'like', "%{$this->search}%")
                            ->orWhere('management_number', 'like', "%{$this->search}%");
                    });
            });
        }

        // アクション種別で絞り込み (borrow, return など)
        if ($this->status !== 'all') {
            $query->where('action_type', $this->status);
        }

        return $query->paginate(15);
    }

    /**
     * 返却完了処理（返却のイベントログを追加で挿入）
     */
    public function returnTool(int $toolId, string $userName): void
    {
        // 返却のログを新規追加 (Append-Only)
        $log = ToolLog::create([
            'tool_id' => $toolId,
            'action_type' => 'return',
            'user_name' => $userName,
            'logged_at' => now(),
        ]);

        // 工具側のステータスを更新
        $log->tool?->update(['status' => 'available']);

        // Computed プロパティのキャッシュを破棄して再計算させる
        unset($this->logs);

        session()->flash('message', "「{$log->tool->name}」の返却を完了しました。");
    }

    public function render()
    {
        return view('souko::livewire.borrow-history');
    }
}

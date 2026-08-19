<?php

namespace Modules\Souko\Livewire;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Modules\Souko\Models\Tool;
use Modules\Souko\Models\ToolLog;

class QrScanner extends Component
{
    public bool $showQrScanner = false;

    public string $scannerMessage = 'QRコードを読み取って工具を追加してください。';

    public function render()
    {
        return view('souko::livewire.qr-scanner');
    }

    public string $search = '';

    public string $borrower_name = '';

    public array $cart = [];

    public function removeItem(int $index): void
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
    }

    public function clearCart(): void
    {
        $this->cart = [];
    }

    public function addToolByQrCode(string $decodedText): void
    {
        $managementNumber = trim($decodedText);

        if ($managementNumber === '') {
            return;
        }

        $tool = Tool::query()
            ->where('management_number', $managementNumber)
            ->first();

        if ($tool === null) {
            $this->scannerMessage = '管理番号 '.$managementNumber.' の工具が見つかりませんでした。';

            return;
        }

        if ($tool->status !== 'available') {
            $this->scannerMessage = $tool->name.' は貸し出しできません。';

            return;
        }

        $cartIndex = $this->findCartIndexByCode($tool->management_number);

        if ($cartIndex !== null) {
            $this->cart[$cartIndex]['quantity'] = (int) ($this->cart[$cartIndex]['quantity'] ?? 1) + 1;
        } else {
            $this->cart[] = $this->toolToCartItem($tool);
        }

        $this->scannerMessage = $tool->name.' を追加しました。';
    }

    private function findCartIndexByCode(string $managementNumber): ?int
    {
        foreach ($this->cart as $index => $item) {
            if (($item['code'] ?? null) === $managementNumber) {
                return $index;
            }
        }

        return null;
    }

    private function toolToCartItem(Tool $tool): array
    {
        return [
            'id' => $tool->getKey(),
            'code' => $tool->management_number,
            'name' => $tool->name,
            'model_number' => $tool->model ?? '-',
            'quantity' => 1,
        ];
    }

    public function checkout(): void
    {
        $this->validate([
            'borrower_name' => ['required', 'string', 'min:1'],
            'cart' => ['required', 'array', 'min:1'],
        ]);

        $toolIds = collect($this->cart)->pluck('id')->filter()->all();
        $tools = Tool::query()->whereIn('id', $toolIds)->get()->keyBy('id');

        foreach ($this->cart as $item) {
            $tool = $tools->get($item['id'] ?? null);

            if ($tool === null) {
                throw ValidationException::withMessages([
                    'cart' => ['カート内の工具が見つかりませんでした。'],
                ]);
            }

            if ($tool->status !== 'available') {
                throw ValidationException::withMessages([
                    'cart' => [$tool->name.' は貸し出しできません。'],
                ]);
            }
        }

        DB::transaction(function () {
            foreach ($this->cart as $item) {
                $tool = Tool::query()->find($item['id'] ?? null);

                if ($tool === null || $tool->status !== 'available') {
                    throw ValidationException::withMessages([
                        'cart' => ['貸し出しできない工具が含まれています。'],
                    ]);
                }

                ToolLog::query()->create([
                    'tool_id' => $tool->getKey(),
                    'action_type' => 'borrow',
                    'user_name' => trim($this->borrower_name),
                    'logged_at' => now(),
                    'note' => 'QRスキャナーによる貸し出し',
                ]);

                $tool->update(['status' => 'rented']);
            }
        });

        session()->flash('status', '貸し出し処理が完了しました。');
        $this->reset(['cart', 'borrower_name']);
        $this->scannerMessage = '貸し出し処理が完了しました。';
    }
}

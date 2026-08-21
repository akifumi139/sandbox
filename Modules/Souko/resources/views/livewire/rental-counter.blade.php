<div class="max-w-7xl mx-auto md:space-y-6">

    <x-souko::header />

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="hidden md:block">
            <flux:heading size="xl" level="1">貸し出しカウンター</flux:heading>
            <flux:subheading>QRコードをスキャンして工具を貸し出します</flux:subheading>
        </div>

        <div class="flex items-center gap-3">
            <div class="inline-flex rounded-xl border border-zinc-200 bg-zinc-100 p-1">
                <a href="{{ route('souko.rental-counter') }}">
                    <button type="button"
                        class="rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-zinc-900 shadow-sm ring-1 ring-zinc-200">
                        貸出
                    </button>
                </a>
                <a href="{{ route('souko.return-counter') }}">
                    <button type="button"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium text-zinc-500 transition hover:text-zinc-900">
                        返却
                    </button>
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <flux:card x-data="qrScanner">
                <div class="flex items-center justify-between pb-4 border-b border-zinc-200">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-zinc-100 ">
                            <flux:icon name="qr-code" class="w-5 h-5 text-zinc-600" />
                        </div>
                        <div>
                            <flux:heading size="lg">QRスキャナー</flux:heading>
                            <flux:subheading x-text="statusText"></flux:subheading>
                        </div>
                    </div>
                </div>

                @if ($scannerMessage !== '')
                    <div
                        class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-100">
                        {{ $scannerMessage }}
                    </div>
                @endif
                <!-- Camera Viewport Area -->
                <div class="mt-4">
                    <div wire:ignore>
                        <div id="qr-reader"
                            class="overflow-hidden rounded-xl bg-zinc-950 min-h-75 flex items-center justify-center">
                        </div>
                    </div>
                </div>
            </flux:card>

            <!-- Scanned Tools List -->
            <flux:card class="p-0 overflow-hidden">
                <div
                    class="p-4 sm:px-6 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                    <div>
                        <flux:heading>スキャンした工具</flux:heading>
                        <flux:subheading>{{ count($cart) }}点</flux:subheading>
                    </div>

                    @if (count($cart) > 0)
                        <flux:button variant="ghost" size="sm" wire:click="clearCart"
                            class="text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                            すべて削除
                        </flux:button>
                    @endif
                </div>

                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($cart as $index => $item)
                        <div class="p-4 sm:p-6" wire:key="cart-item-{{ $item['id'] }}">
                            <div class="flex gap-4">
                                <div
                                    class="hidden sm:flex w-16 h-16 bg-zinc-100 dark:bg-zinc-800 rounded-lg items-center justify-center shrink-0">
                                    <flux:icon name="wrench" class="w-6 h-6 text-zinc-400" />
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-col sm:flex-row sm:justify-between gap-2">
                                        <div>
                                            <flux:heading size="base" level="3">{{ $item['name'] }}
                                            </flux:heading>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                                管理番号：{{ $item['code'] }}</p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                型番：{{ $item['model_number'] }}</p>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-3">

                                            <flux:badge color="green" size="sm" class="self-start">
                                                貸出可能
                                            </flux:badge>
                                            <flux:button variant="subtle" size="sm"
                                                wire:click="removeItem({{ $index }})"
                                                class="text-red-600 hover:text-red-800 dark:text-red-400">
                                                削除
                                            </flux:button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-zinc-500 dark:text-zinc-400">
                            <flux:icon name="qr-code" class="w-10 h-10 mx-auto text-zinc-300 dark:text-zinc-600 mb-2" />
                            <p class="text-sm">スキャンされた工具がありません</p>
                        </div>
                    @endforelse
                </div>
            </flux:card>
        </div>

        <!-- Right Column: Checkout Panel -->
        <aside>
            <flux:card class="lg:sticky lg:top-6 space-y-5">
                <div class="space-y-4">
                    <flux:select label="貸し出し利用者" wire:model="userId">
                        @foreach ($this->users as $user)
                            <flux:select.option value="{{ $user->id }}">{{ $user->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="borrowerName" label="使用者" placeholder="未入力の場合は、貸し出し利用者の名前が使用されます" />

                    <flux:button variant="primary" class="w-full mt-2" wire:click="checkout" :disabled="count($cart) === 0">
                        持ち出す
                    </flux:button>

                    <p class="text-xs text-zinc-400 dark:text-zinc-500 text-center">
                        貸し出し確定後、持ち出しリストで更新されます
                    </p>
                </div>
            </flux:card>
        </aside>

    </div>
</div>

<!-- html5-qrcode -->
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('qrScanner', () => ({
            scanner: null,
            lastResult: null,
            lastResultTime: 0,
            statusText: 'カメラ起動中...',

            init() {
                this.startScanning();
            },

            async startScanning() {
                if (this.scanner) return;

                const element = document.getElementById('qr-reader');
                if (!element) {
                    this.statusText = '要素が見つかりません。';
                    return;
                }

                if (typeof Html5Qrcode === 'undefined') {
                    this.statusText = 'QRライブラリの読み込みに失敗しました。';
                    return;
                }

                this.scanner = new Html5Qrcode('qr-reader');
                this.statusText = 'カメラを初期化中...';

                try {
                    await this.scanner.start({
                            facingMode: 'environment'
                        }, {
                            fps: 10,
                            qrbox: {
                                width: 220,
                                height: 220
                            }
                        },
                        (decodedText) => {
                            const now = Date.now();
                            if (decodedText === this.lastResult && now - this
                                .lastResultTime < 1500) {
                                return;
                            }

                            this.lastResult = decodedText;
                            this.lastResultTime = now;
                            this.statusText = `読み取り成功: ${decodedText}`;

                            this.$wire.call('addToolByQrCode', decodedText);
                        },
                        () => {}
                    );

                    this.statusText = 'QRコードにかざしてください';
                } catch (error) {
                    console.error(error);
                    this.statusText = 'カメラの起動に失敗しました（権限を確認してください）';
                    this.scanner = null;
                }
            },
        }));

        const scannerRoot = document.querySelector('[x-data="qrScanner"]');
        if (scannerRoot) {
            const alpineInstance = Alpine.$data(scannerRoot);
            if (alpineInstance && typeof alpineInstance.startScanning === 'function') {
                alpineInstance.startScanning();
            }
        }
    });
</script>

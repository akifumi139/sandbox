<header class="block bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="min-h-16 py-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            {{-- <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gray-900 text-white rounded-lg flex items-center justify-center font-bold">
                    <flux:icon name="wrench" class="w-5 h-5" />
                </div>
                <div>
                    <h1 class="font-semibold text-sm sm:text-base">
                        工具管理システム
                    </h1>
                </div>
            </div> --}}

            <nav class="flex flex-wrap items-center justify-center gap-1 sm:justify-end">
                <a href="{{ route('souko.rental-counter') }}" wire:navigate
                    class="whitespace-nowrap px-2.5 py-2 rounded-md text-xs sm:text-sm {{ request()->routeIs('souko.rental-counter') ? 'bg-gray-100 font-medium' : 'text-gray-600 hover:bg-gray-100' }}">
                    窓口
                </a>
                <a href="{{ route('souko.borrow-history') }}" wire:navigate
                    class="whitespace-nowrap px-2.5 py-2 rounded-md text-xs sm:text-sm {{ request()->routeIs('souko.borrow-history') ? 'bg-gray-100 font-medium' : 'text-gray-600 hover:bg-gray-100' }}">
                    持ち出し履歴
                </a>
                <a href="{{ route('souko.inventory') }}" wire:navigate
                    class="whitespace-nowrap px-2.5 py-2 rounded-md text-xs sm:text-sm {{ request()->routeIs('souko.inventory') ? 'bg-gray-100 font-medium' : 'text-gray-600 hover:bg-gray-100' }}">
                    在庫管理
                </a>
            </nav>
        </div>
    </div>
</header>

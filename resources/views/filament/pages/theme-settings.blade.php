<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="save">保存主题</x-filament::button>
        <x-filament::button tag="a" href="{{ route('home') }}" target="_blank" rel="noopener noreferrer" color="gray">查看前台</x-filament::button>
    </form>
</x-filament-panels::page>

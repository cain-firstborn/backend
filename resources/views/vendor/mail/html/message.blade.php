<x-mail::layout>
    {{-- Header --}}
    <x-slot:header>
        <x-mail::header :url="config('app.url')">
            {{ config('app.name') }}
        </x-mail::header>
    </x-slot:header>

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @if(isset($subcopy) && $subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {{ $subcopy }}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    {{-- Footer --}}
    @if($footer)
        <x-slot:footer>
            <x-mail::footer>
                © {{ date('Y') }} {{ config('app.name') }}. {{ __('notification.rights') }}.
            </x-mail::footer>
        </x-slot:footer>
    @endif
</x-mail::layout>

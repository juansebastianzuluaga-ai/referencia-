<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
<img src="{{ config('app.url') }}/images/logo-w.png" alt="{{ config('app.name') }}" class="logo">
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
<strong>Clínica Santa Bárbara</strong><br>
Este es un mensaje generado automáticamente, por favor no responda a este correo.<br>
© {{ date('Y') }} Clínica Santa Bárbara. {{ __('Todos los derechos reservados.') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>

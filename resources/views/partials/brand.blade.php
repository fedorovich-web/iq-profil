<span class="brand-content">
@if(file_exists(public_path('assets/brand-original.png')))
<img src="{{ asset('assets/brand-original.png') }}" width="205" height="44" class="original-brand" alt="IQ.Профиль">
@else
<span class="brand-symbol" aria-hidden="true">✧</span><span class="brand-word">IQ.Профиль</span>
@endif
</span>
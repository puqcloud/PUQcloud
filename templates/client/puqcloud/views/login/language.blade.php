<div class="dropdown">
    @php
        $currentLocale = session('locale', config('app.locale'));
        $currentFlag = config('locale.client.locales')[$currentLocale]['flag'];
    @endphp

    <button type="button" data-bs-toggle="dropdown" class="p-0 me-2 btn btn-link">
        <span class="icon-wrapper icon-wrapper-alt rounded-circle">
            <span class="icon-wrapper-bg bg-focus"></span>
            <span class="language-icon opacity-8 fi fi-{{ $currentFlag }} large"></span>
        </span>
    </button>

    <div tabindex="-1" role="menu" aria-hidden="true" class="rm-pointers dropdown-menu dropdown-menu-right">
        @foreach(config('locale.client.locales') as $code => $locale)
            <a href="{{ route('client.web.locale.switch', $code) }}"
               class="dropdown-item @if($code === $currentLocale) active @endif">
                <span class="me-3 opacity-8 large fi fi-{{ $locale['flag'] }}"></span>
                {{ $locale['native'] }}
            </a>
        @endforeach
    </div>
</div>

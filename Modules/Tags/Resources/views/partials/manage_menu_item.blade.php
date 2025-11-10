@if ($canView)
    <li class="{{ \App\Misc\Helper::menuSelectedHtml('tags') }}">
        <a href="{{ route('tags') }}">{{ __('Tags') }}</a>
    </li>
@endif

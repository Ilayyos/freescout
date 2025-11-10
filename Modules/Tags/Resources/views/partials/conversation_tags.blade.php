@if ($canView)
    <li class="conv-tags">
        <span class="conv-tags__label">{{ __('Tags') }}</span>
        @if ($canEdit)
            <select class="conv-tags__select" multiple
                    data-conversation-id="{{ $conversation->id }}"
                    data-sync-url="{{ route('tags.conversations.sync', $conversation->id) }}"
                    data-suggest-url="{{ route('tags.suggest') }}"
                    data-allow-create="{{ $canCreate ? '1' : '0' }}"
                    data-placeholder="{{ __('Add tags') }}">
                @foreach ($tags as $tag)
                    <option value="{{ $tag->name }}" selected data-color="{{ $tag->color }}">{{ $tag->name }}</option>
                @endforeach
            </select>
        @else
            <div class="conv-tags__display">
                @forelse ($tags as $tag)
                    <span class="tag-pill" style="background-color: {{ $tag->color }}">{{ $tag->name }}</span>
                @empty
                    <span class="text-muted">{{ __('No tags') }}</span>
                @endforelse
            </div>
        @endif
    </li>
@endif

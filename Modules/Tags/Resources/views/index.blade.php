@extends('layouts.app')

@section('title', __('Tags'))

@section('content')
    @include('partials/flash_messages')

    <div class="page-title">
        <h1>{{ __('Tags') }}</h1>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">{{ __('Create Tag') }}</div>
                <div class="panel-body">
                    <form method="POST" action="{{ route('tags.store') }}" class="form-horizontal">
                        @csrf
                        <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
                            <label for="tag-name" class="col-sm-3 control-label">{{ __('Name') }}</label>
                            <div class="col-sm-8">
                                <input id="tag-name" type="text" class="form-control" name="name" value="{{ old('name') }}" maxlength="50" required>
                                @include('partials/field_error', ['field' => 'name'])
                            </div>
                        </div>
                        <div class="form-group{{ $errors->has('color') ? ' has-error' : '' }}">
                            <label for="tag-color" class="col-sm-3 control-label">{{ __('Color') }}</label>
                            <div class="col-sm-8">
                                <input id="tag-color" type="color" class="form-control input-sm" name="color" value="{{ old('color', '#4e7efc') }}">
                                @include('partials/field_error', ['field' => 'color'])
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-8 col-sm-offset-3">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Add Tag') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">{{ __('Existing Tags') }}</div>
        <div class="panel-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-middle tags-table">
                    <thead>
                        <tr>
                            <th>{{ __('Tag') }}</th>
                            <th>{{ __('Color & Name') }}</th>
                            <th class="text-center">{{ __('Conversations') }}</th>
                            <th class="text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tags as $tag)
                            <tr>
                                <td>
                                    <span class="tag-pill" style="background-color: {{ $tag->color }}">{{ $tag->name }}</span>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('tags.update', $tag->id) }}" class="form-inline tag-color-form">
                                        @csrf
                                        @method('PUT')
                                        <div class="form-group">
                                            <label class="sr-only" for="tag-color-{{ $tag->id }}">{{ __('Color') }}</label>
                                            <input id="tag-color-{{ $tag->id }}" type="color" name="color" value="{{ $tag->color }}" class="form-control input-sm">
                                        </div>
                                        <div class="form-group">
                                            <label class="sr-only" for="tag-name-{{ $tag->id }}">{{ __('Name') }}</label>
                                            <input id="tag-name-{{ $tag->id }}" type="text" name="name" value="{{ $tag->name }}" maxlength="50" class="form-control input-sm" required>
                                        </div>
                                        <button type="submit" class="btn btn-link btn-xs">{{ __('Save') }}</button>
                                    </form>
                                </td>
                                <td class="text-center">
                                    {{ $tag->conversations_count }}
                                </td>
                                <td class="text-right">
                                    <form method="POST" action="{{ route('tags.destroy', $tag->id) }}" class="inline-block" onsubmit="return confirm('{{ __('Delete this tag?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-link btn-xs text-danger">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">{{ __('No tags yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if (method_exists($tags, 'links'))
            <div class="panel-footer text-center">
                {{ $tags->links() }}
            </div>
        @endif
    </div>
@endsection

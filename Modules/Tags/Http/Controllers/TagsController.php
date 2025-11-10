<?php

namespace Modules\Tags\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\Tags\Entities\Tag;
use Modules\Tags\Support\TagsPermissions;

class TagsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless(TagsPermissions::canManage($user), 403);

        $tags = Tag::query()
            ->withConversationCountForUser($user)
            ->ordered()
            ->paginate(25);

        return view('tags::index', [
            'tags' => $tags,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless(TagsPermissions::canManage($user), 403);

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:50', 'unique:tags,name'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        Tag::create([
            'name'  => $validated['name'],
            'color' => $validated['color'] ?? null,
        ]);

        return redirect()->route('tags')->with('flash_success', __('Tag created successfully.'));
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $user = $request->user();
        abort_unless(TagsPermissions::canManage($user), 403);

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:50', Rule::unique('tags', 'name')->ignore($tag->id)],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $tag->fill([
            'name'  => $validated['name'],
            'color' => $validated['color'] ?? null,
        ]);
        $tag->save();

        return redirect()->route('tags')->with('flash_success', __('Tag updated successfully.'));
    }

    public function destroy(Request $request, Tag $tag): RedirectResponse
    {
        $user = $request->user();
        abort_unless(TagsPermissions::canManage($user), 403);

        $tag->delete();

        return redirect()->route('tags')->with('flash_success', __('Tag deleted.'));
    }
}

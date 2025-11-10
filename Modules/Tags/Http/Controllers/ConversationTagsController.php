<?php

namespace Modules\Tags\Http\Controllers;

use App\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tags\Entities\Tag;
use Modules\Tags\Support\TagsPermissions;

class ConversationTagsController extends Controller
{
    public function suggest(Request $request): JsonResponse
    {
        $user = $request->user();

        $term = $request->input('q');
        $tags = Tag::suggestionsForUser($user, $term);

        return response()->json([
            'items' => $tags->map(function (Tag $tag) {
                return [
                    'name'  => $tag->name,
                    'color' => $tag->color,
                ];
            })->values(),
        ]);
    }

    public function sync(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        $this->authorize('update', $conversation);

        $tags = $request->input('tags', []);
        if (!is_array($tags)) {
            return response()->json(['message' => __('Tags data must be an array.')], 422);
        }

        $allowCreate = TagsPermissions::canEditConversationTags($user);

        try {
            $synced = Tag::syncConversationTags($conversation, $tags, $user, $allowCreate);
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        }

        return response()->json([
            'tags' => $synced->map(function (Tag $tag) {
                return [
                    'name'  => $tag->name,
                    'color' => $tag->color,
                ];
            })->values(),
        ]);
    }

}

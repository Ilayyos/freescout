<?php

namespace Modules\Tags\Entities;

use App\Conversation;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Collection as BaseCollection;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'color',
    ];

    protected $casts = [
        'name'  => 'string',
        'slug'  => 'string',
        'color' => 'string',
    ];

    public const DEFAULT_COLORS = [
        '#4e7efc', '#5bb974', '#8e67ff', '#f45d48', '#f7b32b', '#49c1b6', '#c86bfa', '#ff6f91', '#00b8d9', '#6c757d',
    ];

    protected static function booted()
    {
        static::creating(function (self $tag) {
            $tag->name = static::sanitizeName($tag->name);
            $tag->slug = static::generateUniqueSlug($tag->name);
            $tag->color = static::normalizeColor($tag->color, $tag->name);
        });

        static::updating(function (self $tag) {
            $tag->name = static::sanitizeName($tag->name);
            $tag->slug = static::generateUniqueSlug($tag->name, $tag->id);
            $tag->color = static::normalizeColor($tag->color, $tag->name);
        });
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_tag')->withTimestamps();
    }

    public function scopeOrdered(Builder $builder): Builder
    {
        return $builder->orderBy('name');
    }

    public function scopeAccessibleTo(Builder $builder, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $builder;
        }

        $mailboxIds = $user->mailboxesCanView(true)->pluck('id')->filter()->all();
        if (empty($mailboxIds)) {
            return $builder->whereRaw('1 = 0');
        }

        return $builder->whereHas('conversations', function (Builder $query) use ($mailboxIds, $user) {
            $query->whereIn('conversations.mailbox_id', $mailboxIds);
            if ($user->canSeeOnlyAssignedConversations()) {
                $query->where(function (Builder $sub) use ($user) {
                    $sub->where('conversations.user_id', $user->id)
                        ->orWhere('conversations.created_by_user_id', $user->id);
                });
            }
        });
    }

    public function scopeWithConversationCountForUser(Builder $builder, User $user): Builder
    {
        return $builder->withCount(['conversations as conversations_count' => function (Builder $query) use ($user) {
            static::applyConversationScope($query, $user);
        }]);
    }

    public static function applyConversationScope(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        $mailboxIds = $user->mailboxesCanView(true)->pluck('id')->filter()->all();
        if (empty($mailboxIds)) {
            return $query->whereRaw('1 = 0');
        }

        $query->whereIn('conversations.mailbox_id', $mailboxIds);

        if ($user->canSeeOnlyAssignedConversations()) {
            $query->where(function (Builder $sub) use ($user) {
                $sub->where('conversations.user_id', $user->id)
                    ->orWhere('conversations.created_by_user_id', $user->id);
            });
        }

        return $query;
    }

    public static function forConversation(Conversation $conversation): Collection
    {
        return static::query()
            ->whereHas('conversations', function (Builder $query) use ($conversation) {
                $query->where('conversations.id', $conversation->id);
            })
            ->ordered()
            ->get();
    }

    public static function suggestionsForUser(User $user, ?string $term = null): Collection
    {
        return static::query()
            ->accessibleTo($user)
            ->when($term, function (Builder $query) use ($term) {
                $query->where('name', 'like', '%' . $term . '%');
            })
            ->ordered()
            ->limit(25)
            ->get(['id', 'name', 'color']);
    }

    public static function syncConversationTags(Conversation $conversation, array $names, User $user, bool $allowCreate): BaseCollection
    {
        $names = collect($names)
            ->map(function ($name) {
                return static::sanitizeName($name);
            })
            ->filter()
            ->unique(function ($name) {
                return Str::lower($name);
            })
            ->values();

        if ($names->isEmpty()) {
            DB::table('conversation_tag')->where('conversation_id', $conversation->id)->delete();
            return collect();
        }

        $existingTags = static::query()->whereIn('name', $names)->get();

        $missing = $names->diff($existingTags->pluck('name'));
        if ($missing->isNotEmpty() && !$allowCreate) {
            throw new \RuntimeException(__('You are not allowed to create new tags.'));
        }

        $created = collect();
        if ($missing->isNotEmpty()) {
            foreach ($missing as $name) {
                $created->push(static::create([
                    'name' => $name,
                    'color' => static::pickColorForName($name),
                ]));
            }
        }

        $tags = $existingTags->merge($created);

        DB::transaction(function () use ($conversation, $tags) {
            $currentIds = DB::table('conversation_tag')
                ->where('conversation_id', $conversation->id)
                ->pluck('tag_id');

            $newIds = $tags->pluck('id');

            $detachIds = $currentIds->diff($newIds);
            if ($detachIds->isNotEmpty()) {
                DB::table('conversation_tag')
                    ->where('conversation_id', $conversation->id)
                    ->whereIn('tag_id', $detachIds)
                    ->delete();
            }

            $attachIds = $newIds->diff($currentIds);
            $now = now();
            if ($attachIds->isNotEmpty()) {
                $records = $attachIds->map(function ($tagId) use ($conversation, $now) {
                    return [
                        'conversation_id' => $conversation->id,
                        'tag_id'          => $tagId,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];
                })->all();
                DB::table('conversation_tag')->insert($records);
            }

            if ($newIds->isNotEmpty()) {
                DB::table('conversation_tag')
                    ->where('conversation_id', $conversation->id)
                    ->whereIn('tag_id', $newIds)
                    ->update(['updated_at' => $now]);
            }
        });

        return $tags->sortBy('name')->values();
    }

    public static function sanitizeName(?string $name): string
    {
        $name = trim((string) $name);
        $name = preg_replace('/\s+/u', ' ', $name);
        return mb_substr($name, 0, 50);
    }

    protected static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name, '-');
        if ($base === '') {
            $base = Str::slug(Str::random(8));
        }

        $slug = $base;
        $suffix = 1;
        while (static::query()
            ->when($ignoreId, function (Builder $query, $ignoreId) {
                $query->where('id', '!=', $ignoreId);
            })
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return mb_substr($slug, 0, 60);
    }

    protected static function normalizeColor(?string $color, string $name): string
    {
        if (!$color || !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            return static::pickColorForName($name);
        }

        return strtoupper($color);
    }

    protected static function pickColorForName(string $name): string
    {
        $palette = static::DEFAULT_COLORS;
        $index = abs(crc32(Str::lower($name))) % count($palette);
        return $palette[$index];
    }
}

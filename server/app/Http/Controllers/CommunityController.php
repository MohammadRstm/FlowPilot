<?php

namespace App\Http\Controllers;

use App\Models\UserPost;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    public function fetchPosts(Request $request)
    {
        $userId = auth()->id() ?? 0;

        $perPage = 30;
        $page = (int) $request->query('page', 1);

        $weightLikes = 1;
        $weightComments = 2;
        $weightImports = 4;
        $followBoost = 100000;

        $query = UserPost::query()
            ->with('user')
            ->select('user_posts.*')
            ->selectRaw(
                '(
                    COALESCE(user_posts.likes, 0) * ?
                    + COALESCE(user_posts.imports, 0) * ?
                    + (
                        SELECT COUNT(*)
                        FROM post_comments
                        WHERE post_comments.post_id = user_posts.id
                    ) * ?
                    + CASE
                        WHEN EXISTS (
                            SELECT 1
                            FROM followers
                            WHERE followers.followed_id = user_posts.user_id
                              AND followers.follower_id = ?
                        )
                        THEN ?
                        ELSE 0
                    END
                ) AS score',
                [
                    $weightLikes,
                    $weightImports,
                    $weightComments,
                    $userId,
                    $followBoost,
                ]
            )
            ->selectRaw(
                '(
                    SELECT COUNT(*)
                    FROM post_comments
                    WHERE post_comments.post_id = user_posts.id
                ) AS comments_count'
            )
            ->orderByDesc('score')
            ->orderByDesc('user_posts.created_at');

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        $data = $paginated->getCollection()->map(function ($post) {
            $user = $post->user;

            return [
                'id' => $post->id,
                'author' => $user->name ?? 'Unknown',
                'username' => $user?->username ? '@' . ltrim($user->username, '@') : null,
                'avatar' => $user->avatar_url ?? $user->avatar ?? null,
                'content' => $post->description ?? $post->title ?? '',
                'likes' => (int) $post->likes,
                'comments' => (int) $post->comments_count,
                'exports' => (int) $post->imports,
                'score' => (float) $post->score,
                'created_at' => optional($post->created_at)->toDateTimeString(),
            ];
        });

        return $this->successResponse([
            'data' => $data->values(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
        ]);
    }
}

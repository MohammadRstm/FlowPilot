<?php

namespace App\Service;

use App\Models\CommentsLike;
use App\Models\PostComment;
use App\Models\PostsLike;
use App\Models\UserPost;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CommunityService{

    public static function toggleLike(int $userId , int $postId ){
        $post = self::getPost($postId);

        DB::transaction(function () use ($post, $userId, &$liked) {
            $existing = PostsLike::where('post_id', $post->id)
                ->where('user_id', $userId)
                ->first();

            if ($existing) {
                $existing->delete();
                $post->decrement('likes');
                $liked = false;
            } else {
                PostsLike::create([
                    'post_id' => $post->id,
                    'user_id' => $userId,
                ]);
                $post->increment('likes');
                $liked = true;
            }
        });

        return [
            'liked' => $liked,
            'likes' => $post->fresh()->likes,
        ];
    }

    public static function getPosts(int $userId , int $page){
        $perPage = 30;

        $query = self::executeFetchPaginatedPostsQuery($userId);

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        $data = self::buildPostsData($paginated);

        return[
            'data' => $data->values(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
        ];
    }

    public static function export(int $postId){
        $post = self::getPost($postId);

        $jsonContent = $post->json_content ?? [];

        $filename = 'post-' . $post->id . '.json';
        $headers = [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $post->increment('imports');

        return [
            "json_content" => $jsonContent,
            "headers" => $headers,
            "imports" => $post->imports + 1// model isn't updated in real time here so we have to manually increment
        ];
    }

    public static function toggleCommentLike(int $userId , int $commentId){
        $comment = PostComment::find($commentId);

         DB::transaction(function () use ($comment, $userId, &$liked) {
            $existing = CommentsLike::where('comment_id', $comment->id)
                ->where('user_id', $userId)
                ->first();

            if ($existing) {
                $existing->delete();
                $comment->decrement('likes');
                $liked = false;
            } else {
                CommentsLike::create([
                    'comment_id' => $comment->id,
                    'user_id' => $userId,
                ]);
                $comment->increment('likes');
                $liked = true;
            }
        });

        return[
            "liked" => $liked,
            "likes" => $comment->fresh()->likes
        ];
    }

    public static function getComments(int $postId){
        return PostComment::where("post_id" , $postId)
            ->with('user')->orderBy('likes' , 'DESC')
            ->get();
    }

    public static function postComment(int $userId , string $content , int $postId){
        return PostComment::create([
            "user_id" => $userId,
            "post_id" => $postId,
            "content" => $content,
            "likes" => 0
        ]);
    }

    public static function createPost(int $userId , array $form){
        $jsonContent = null;
        $photoUrl = null;

        if (isset($form['file'])) {

            $file = $form['file'];
            if ($file->getClientOriginalExtension() === 'json') {
                $jsonContent = json_decode(file_get_contents($file->getRealPath()), true);
            }
        }

        if (isset($form['image'])) {
            $image = $form['image'];
            $storagePath = 'upload/post_images';

            if (!Storage::disk('public')->exists($storagePath)) {
                Storage::disk('public')->makeDirectory($storagePath);
            }

            $filename = Str::uuid()->toString() . '.' . $image->getClientOriginalExtension();

            $image->storeAs($storagePath, $filename, 'public');

            $photoUrl = 'storage/' . $storagePath . '/' . $filename;
        }

        $post = UserPost::create([
            'user_id' => $userId,
            'title' => $form['title'],
            'description' => $form['description'] ?? null,
            'json_content' => $jsonContent,
            'photo_url' => $photoUrl,
            'likes' => 0,
            'imports' => 0,
        ]);

        return $post->id;
    }


    private static function executeFetchPaginatedPostsQuery($userId){
        $weightLikes = 1;
        $weightComments = 2;
        $weightImports = 4;
        $followBoost = 100000;
        return UserPost::query()
            ->with('user')
            ->select('user_posts.*')
            ->selectRaw(
                self::getRawSelect(),
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
    }

    private static function buildPostsData($paginated){
        return $paginated->getCollection()->map(function ($post) {
            $user = $post->user;

            $username = null;
            if ($user?->email) {
                $username = strstr($user->email, '@', true); // returns the part before @
            }

            return [
                'id' => $post->id,

                'author' => $user
                ? trim("{$user->first_name} {$user->last_name}")
                : 'Unknown',

                'username' => '@' . $username,
                'avatar' => $user->photo_url,
                'content' => $post->description ?? null,
                'title' => $post->title,
                'photo' => $post->photo_url,
                'likes' => (int) $post->likes,
                'comments' => (int) $post->comments_count,
                'exports' => (int) $post->imports,
                'score' => (float) $post->score,
                'created_at' => optional($post->created_at)->toDateTimeString(),
            ];
        });
    }

    private static function getRawSelect(){
        return '(
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
    ) AS score';
    }

    private static function getPost(int $postId){
        $post = UserPost::where('id' , $postId)->first();

        if(!$post) throw new Exception("Post not found");

        return $post;
    }
}

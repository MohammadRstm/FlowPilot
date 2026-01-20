<?php

namespace App\Service;

use App\Models\Follower;
use App\Models\User;
use App\Models\UserPost;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class ProfileService{
    public static function getProfileDetails(int $userId, ?int $viewerId = null, int $perPage = 20){
        $user = self::getNumberOfImports($userId);

        if(!$user){
            return null;
        }

        // totals: likes & imports
        $totals = self::getTotalsCommentsLikes($userId);

        $totalLikes = (int) ($totals->total_likes ?? 0);
        $totalImports = (int) ($totals->total_imports ?? 0);

        // followers list: return id, full name and photo_url
        $followers = self::getFollowersList($user);
        $following = self::getFollowingList($user);
        
        // is viewer following this profile?
        $viewerFollows = false;
        if($viewerId) {
            $viewerFollows = DB::table('followers')
            ->where('follower_id', $viewerId)
            ->where('followed_id', $userId)
            ->exists();
        }

        // posts : ranked/paginated :: copilot histories : paginated
        $postsPayload = self::getRankedPaginatedPosts($viewerFollows , $userId , $perPage);
        $historiesPayload = self::getPaginatedHistories($user , $perPage);

        return [
            'user' => $user->toArray(),
            'totals' => [
                'likes' => $totalLikes,
                'imports' => $totalImports,
                'posts_count' => $user->posts_count ?? 0,
            ],
            'followers' => $followers,
            'following' => $following,
            'posts' => $postsPayload,
            'workflows' => $historiesPayload,
            'viewer_follows' => $viewerFollows,
        ];
    }


    public static function toggeleFollow(int $userId, int $toBeFollowed){
        if ($userId === $toBeFollowed) {
            throw new Exception("You cannot follow yourself");
        }

        // Ensure target user exists
        if (!User::where('id', $toBeFollowed)->exists()) {
            throw new Exception("User not found");
        }

        $existing = Follower::where('follower_id', $userId)
            ->where('followed_id', $toBeFollowed)
            ->first();

        if ($existing) {
           Follower::where('follower_id', $userId)
            ->where('followed_id', $toBeFollowed)
            ->delete();
            return [
                'following' => false,
            ];
        }

        Follower::create([
            'follower_id' => $userId,
            'followed_id' => $toBeFollowed,
        ]);

        return [
            'following' => true,
        ];
    }


    public static function isFollowingUser(int $userId, int $viewerId): array{
        $isViewerFollowing = Follower::where('follower_id', $viewerId)
            ->where('followed_id', $userId)
            ->exists();
 
        // for "follow back"
        $isUserFollowed = Follower::where('follower_id', $userId)
            ->where('followed_id', $viewerId)
            ->exists();

        return [
            'isFollowing' => $isViewerFollowing,
            'isBeingFollowed' => $isUserFollowed,
        ];
    }


    private static function getNumberOfImports(int $userId){
        return User::select('id','first_name','last_name','email','photo_url','created_at')
        ->withCount(['posts as posts_count'])
        ->find($userId);
    }

    private static function getTotalsCommentsLikes($userId){
        return DB::table('user_posts')
            ->where('user_id', $userId)
            ->selectRaw('COALESCE(SUM(COALESCE(likes,0)),0) as total_likes, COALESCE(SUM(COALESCE(imports,0)),0) as total_imports')
            ->first();
    }

    private static function getFollowersList($user){
        return $user->followers()
            ->select('users.id','users.first_name','users.last_name','users.photo_url' , 'users.email')
            ->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'full_name' => trim("{$f->first_name} {$f->last_name}"),
                'photo_url' => $f->photo_url,
                'email' => $f->email
            ]);
    }

    private static function getFollowingList($user){
        return $user->followings()
        ->select('users.id','users.first_name','users.last_name','users.photo_url' , 'users.email')
        ->get()
        ->map(fn($f) => [
            'id' => $f->id,
            'full_name' => trim("{$f->first_name} {$f->last_name}"),
            'photo_url' => $f->photo_url,
            'email' => $f->email
        ]);
    }

    private static function getRankedPaginatedPosts($viewerFollows , $userId , $perPage){
        $wLikes = 1.0;
        $wComments = 1.5;
        $wImports = 1.2;
        $followBoost = 10;

        $postsQuery = UserPost::query()
        ->where('user_posts.user_id', $userId)
        ->select('user_posts.*')
        ->selectSub(function ($q) {
            $q->from('post_comments')
            ->selectRaw('COUNT(*)')
            ->whereColumn('post_comments.post_id', 'user_posts.id');
        }, 'comments_count');

        // Add a computed score column using raw ordering. We use numeric weights interpolated here
        // (they're constants so interpolation is safe).
        $scoreExpression = " (COALESCE(user_posts.likes,0) * {$wLikes}) + (COALESCE(user_posts.imports,0) * {$wImports}) + (COALESCE(comments_count,0) * {$wComments}) ";

        // If viewer follows the author, add a constant boost (not per-post) — optional.
        if ($viewerFollows) {
            $scoreExpression = "({$scoreExpression}) + {$followBoost}";
        }

        $postsQuery->orderByRaw("{$scoreExpression} DESC, user_posts.created_at DESC");

        try {
            $posts = $postsQuery->cursorPaginate($perPage);
        } catch (\Throwable $e) {
            // fallback to regular paginate if cursorPaginate isn't available
            $posts = $postsQuery->paginate($perPage);
        }

        return [
            'items' => $posts->items(),
            'nextCursor' => method_exists($posts, 'nextCursor') ? $posts->nextCursor()?->encode() ?? null : null,
            'hasMore' => method_exists($posts, 'hasMorePages') ? $posts->hasMorePages() : $posts->nextPageUrl() !== null,
            'meta' => [
                'per_page' => $perPage,
            ],
        ];

    }

    private static function getPaginatedHistories($user , $perPage){
        $historiesQuery = $user->copilotHistory()->with(['messages'])->orderBy('created_at', 'desc');

        try {
            $histories = $historiesQuery->cursorPaginate($perPage);
        } catch (\Throwable $e) {
            $histories = $historiesQuery->paginate($perPage);
        }

        // Prepare histories payload: include a download_url (suggested route: route('histories.download', $id))
        $historiesItems = collect($histories->items())->map(function ($h) {
            // Build a lightweight summary (avoid returning full message arrays unless necessary)
            return [
                'id' => $h->id,
                'created_at' => $h->created_at,
                'messages_count' => $h->messages->count(),
                // download_url: front-end will call this; implement controller route to stream JSON
                'download_url' => URL::route('user.histories.download', ['history' => $h->id]),
            ];
        })->toArray();

        return [
            'items' => $historiesItems,
            'nextCursor' => method_exists($histories, 'nextCursor') ? $histories->nextCursor()?->encode() ?? null : null,
            'hasMore' => method_exists($histories, 'hasMorePages') ? $histories->hasMorePages() : $histories->nextPageUrl() !== null,
            'meta' => [
                'per_page' => $perPage
            ],
        ];
    }

}

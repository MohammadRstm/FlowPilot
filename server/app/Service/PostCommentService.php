<?php

namespace App\Service;

use App\Models\CommentsLike;
use App\Models\PostComment;
use Illuminate\Support\Facades\DB;

class PostCommentService{
    
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
}

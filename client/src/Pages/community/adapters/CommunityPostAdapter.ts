import type { PostCardData } from "../../profile/types";
import type { PostDto } from "../types";

export const adaptCommunityPost = (post: PostDto): PostCardData => ({
  id: post.id,
  title: post.title,
  content: post.content,
  photo: post.photo,

  author: post.author,
  username: post.username,
  avatar: post.avatar,

  likes: post.likes,
  comments: post.comments,
  imports: post.exports,

  liked_by_me: post.liked_by_me,
});

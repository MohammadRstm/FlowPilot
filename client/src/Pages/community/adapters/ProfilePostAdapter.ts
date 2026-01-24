import type { PostCardData } from "../../profile/types";

export const adaptListPost = (post: any): PostCardData => ({
  id: post.id,

  title: post.title,
  content: post.description,
  photo: post.photo_url,

  likes: post.likes,
  comments: post.comments_count,
  imports: post.imports,
});
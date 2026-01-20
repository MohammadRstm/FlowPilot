import type { PostDto } from "../../Pages/community/hook/useFetchPosts";
import type { PostCardData } from "../../Pages/profile/types";

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

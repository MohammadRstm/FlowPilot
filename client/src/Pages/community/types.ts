export type PostDto = {
  id: number;
  author: string;
  title:string;
  photo:string;
  username?: string | null;
  avatar?: string | null;
  content: string;
  likes: number;
  comments: number;
  exports: number;
  score?: number;
  created_at?: string | null;
  liked_by_me: boolean;
};

export type ApiResponse = {
  data: PostDto[];
  meta: {
    current_page: number;
    last_page: number;
  };
};

export type PostCommentPayload = {postId : number , content : string}


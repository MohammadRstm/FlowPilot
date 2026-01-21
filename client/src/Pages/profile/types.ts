export type UserLite = {
  id: number;
  full_name: string;
  email?: string;
  photo_url?: string;
  first_name?: string;
  last_name?: string;
};

export type PostsShape = { items: any[]; nextCursor?: string | null; hasMore?: boolean; meta?: any };
export type WorkflowsShape = PostsShape;

export type ProfileApiShape = {
  user?: UserLite & { first_name?: string; last_name?: string; email?: string; photo_url?: string };
  totals?: { likes?: number; imports?: number; posts_count?: number };
  followers?: Array<UserLite>;
  following?: Array<UserLite>;
  posts?: PostsShape;
  workflows?: WorkflowsShape;
  viewer_follows?: boolean;
  following_count?: number;
};

export type PostCardData = {
  id: number;

  title?: string | null;
  content?: string | null;
  photo?: string | null;

  author?: string | null;
  username?: string | null;
  avatar?: string | null;

  likes?: number;
  comments?: number;
  imports?: number;

  liked_by_me?: boolean;
};

export type FollowUserParam = number | undefined;

export type IsBeingFollowedParam = number | undefined;

export const TabType = {
  POSTS: "posts",
  WORKFLOWS: "workflows",
} as const;

export type TabType = typeof TabType[keyof typeof TabType];

export const SortType = {
  SCORE: "score",
  LIKES: "likes",
  COMMENTS: "comments",
  IMPORTS: "imports"
} as const;

export type SortType = typeof SortType[keyof typeof SortType];

export const ModalType = {
  FOLLOWERS: "followers",
  FOLLOWING: "following"
} as const;

export type ModalType = typeof ModalType[keyof typeof ModalType];


export type Totals = {
  likes: number;
  imports: number;
  posts_count?: number;
};







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

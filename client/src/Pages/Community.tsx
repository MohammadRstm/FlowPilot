import React from "react";
import "../styles/Community.css";
import Header from "./components/Header";

type Post = {
  id: number;
  author: string;
  username: string;
  avatar: string;
  content: string;
  likes: number;
  comments: number;
  exports: number;
};

const posts: Post[] = [
  {
    id: 1,
    author: "Mohammad Rostom",
    username: "@mhmdrstm",
    avatar: "https://i.pravatar.cc/100?img=1",
    content: "No body talks about this goated duo 😂",
    likes: 33,
    comments: 4,
    exports: 20,
  },
  {
    id: 2,
    author: "Jane Doe",
    username: "@janedoe",
    avatar: "https://i.pravatar.cc/100?img=2",
    content: "This automation setup saved me hours 🔥",
    likes: 21,
    comments: 3,
    exports: 12,
  },
];

const PostCard: React.FC<{ post: Post }> = ({ post }) => {
  return (
    <div className="post-card">
      <div className="post-header">
        <img src={post.avatar} alt={post.author} />
        <div>
          <div className="post-author">{post.author}</div>
          <div className="post-username">{post.username}</div>
        </div>
        <div className="post-imports">{post.exports} exports</div>
      </div>

      <div className="post-content">{post.content}</div>

      <div className="post-preview">
        {/* Placeholder for workflow / image */}
      </div>

      <div className="post-actions">
        <button>👍 Like</button>
        <button>💬 Comment</button>
        <button>⬇ Export</button>
      </div>

      <div className="post-stats">
        {post.likes} likes · {post.comments} comments
      </div>
    </div>
  );
};

const CommunityPage: React.FC = () => {
  return (
    <div className="community-page">
      <Header />

      <main className="feed">
        {posts.map((post) => (
          <PostCard key={post.id} post={post} />
        ))}
      </main>
    </div>
  );
};

export default CommunityPage;

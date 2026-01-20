import React, { useState } from "react";
// import { useCreatePost } from "../hook/useCreatePost"; // you'll create this hook

type Props = {
  isOpen: boolean;
  onClose: () => void;
};

const CreatePostModal: React.FC<Props> = ({ isOpen, onClose }) => {
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [file, setFile] = useState<File | null>(null);
  const [image, setImage] = useState<File | null>(null);

//   const createPost = useCreatePost();

  if (!isOpen) return null;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    const formData = new FormData();
    formData.append("title", title);
    formData.append("description", description);
    if (file) formData.append("file", file);
    if (image) formData.append("image", image);

    // createPost.mutate(formData, {
    //   onSuccess: () => {
    //     onClose();
    //     setTitle("");
    //     setDescription("");
    //     setFile(null);
    //     setImage(null);
    //   },
    // });
  };

  const handleImageDrop = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      setImage(e.dataTransfer.files[0]);
    }
  };

  return (
    <div className="comments-modal-overlay" onClick={onClose}>
      <div
        className="comments-modal"
        style={{ maxWidth: "600px" }}
        onClick={(e) => e.stopPropagation()}
      >
        <h2>Create New Post</h2>
        <form className="create-post-form" onSubmit={handleSubmit}>
          <label>
            Title*
            <input
              type="text"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              required
            />
          </label>

          <label>
            Description
            <textarea
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              style={{ maxHeight: "200px", resize: "vertical" }}
            />
          </label>

          <label>
            Upload Workflow (File)
            <input
              type="file"
              onChange={(e) => e.target.files && setFile(e.target.files[0])}
            />
          </label>

          <label>
            Upload Image (Optional)
            <div
              className="image-dropzone"
              onDragOver={(e) => e.preventDefault()}
              onDrop={handleImageDrop}
              onClick={() => document.getElementById("image-input")?.click()}
            >
              {image ? (
                <span>{image.name}</span>
              ) : (
                <span>Drag & Drop image here or click to upload</span>
              )}
            </div>
            <input
              type="file"
              id="image-input"
              style={{ display: "none" }}
              accept="image/*"
              onChange={(e) => e.target.files && setImage(e.target.files[0])}
            />
          </label>

          <button type="submit" className="start-post-btn full-width">
            Submit
          </button>
        </form>
      </div>
    </div>
  );
};

export default CreatePostModal;

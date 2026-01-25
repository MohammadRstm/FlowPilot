import React, { useState } from "react";
import { useCreatePost } from "../hook/useCreatePost";

type Props = {
  isOpen: boolean;
  onClose: () => void;
  createPost: ReturnType<typeof useCreatePost>;
};

const CreatePostModal: React.FC<Props> = ({ isOpen, onClose, createPost }) => {
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [file, setFile] = useState<File | null>(null);
  const [image, setImage] = useState<File | null>(null);

  if (!isOpen) return null;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    createPost.mutate(
      {
        title,
        description,
        file: file ?? undefined,
        image: image ?? undefined,
      },
      {
        onSuccess: () => {
          setTitle("");
          setDescription("");
          setFile(null);
          setImage(null);
          onClose();
        },
      }
    );
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
        className="create-post-modal"
        onClick={(e) => e.stopPropagation()}
      >
        <h2>Create New Post</h2>

        <div className="create-post-form-container">
          <div className="image-column">
            <div
              className="image-dropzone"
              onDragOver={(e) => e.preventDefault()}
              onDrop={handleImageDrop}
              onClick={() => document.getElementById("image-input")?.click()}
            >
              {image ? (
                <img
                  src={URL.createObjectURL(image)}
                  alt="Preview"
                  className="image-preview"
                />
              ) : (
                <span>Drag & Drop image here or click to upload</span>
              )}
              <input
                type="file"
                id="image-input"
                style={{ display: "none" }}
                accept="image/*"
                onChange={(e) =>
                  e.target.files && setImage(e.target.files[0])
                }
              />
            </div>
          </div>

          <div className="form-column">
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
                />
              </label>

              <label>
                Upload Workflow (.json)
                <input
                  type="file"
                  onChange={(e) =>
                    e.target.files && setFile(e.target.files[0])
                  }
                />
              </label>

              <button type="submit" className="start-post-btn full-width">
                Submit
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  );
};

export default CreatePostModal;

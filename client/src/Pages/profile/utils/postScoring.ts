export const getCounts = (p: any) => {
  const likes = typeof p.likes === "number" ? p.likes : p.likes_count ?? p.likes?.length ?? 0;
  const imports = typeof p.imports === "number" ? p.imports : p.imports_count ?? p.imports?.length ?? 0;
  const comments =
    typeof p.comments_count === "number" ? p.comments_count : p.comments?.length ?? p.comment_count ?? 0;
  return { likes, imports, comments };
};

export const getScore = (p: any) => {
  const w = { likes: 1.0, comments: 1.5, imports: 1.2 };
  const { likes, imports, comments } = getCounts(p);
  return likes * w.likes + comments * w.comments + imports * w.imports;
};

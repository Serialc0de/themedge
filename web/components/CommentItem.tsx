'use client';

import { useState } from 'react';
import CommentForm from './CommentForm';

interface CommentData {
  id: number;
  parent_id: number | null;
  author_name: string;
  author_website: string | null;
  gravatar_url: string;
  content: string;
  is_pinned?: boolean;
  likes_count: number;
  created_at: string;
  created_at_formatted: string;
  replies?: CommentData[];
}

interface Props {
  comment: CommentData;
  articleSlug: string;
  onReplySuccess?: () => void;
  isReply?: boolean;
}

export default function CommentItem({ comment, articleSlug, onReplySuccess, isReply = false }: Props) {
  const [showReplyForm, setShowReplyForm] = useState(false);
  const [showReplies, setShowReplies] = useState(true);

  const replies = comment.replies || [];
  const isAdminReply = comment.author_name.includes('(Redazione)');
  const isPinned = comment.is_pinned === true;

  function handleReplySuccess() {
    setShowReplyForm(false);
    if (onReplySuccess) onReplySuccess();
  }

  return (
    <article
      id={`comment-${comment.id}`}
      className={`${isReply ? 'ml-8 md:ml-12 mt-4' : 'py-5 border-b border-gray-200 dark:border-gray-800 last:border-b-0'}`}
    >
      <div className="flex gap-4">
        {/* Avatar */}
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img
          src={comment.gravatar_url}
          alt={comment.author_name}
          className={`flex-shrink-0 rounded-full ${isReply ? 'w-10 h-10' : 'w-12 h-12'}`}
          loading="lazy"
        />

        <div className="flex-1 min-w-0">
          {/* Header */}
          <div className="flex flex-wrap items-center gap-2 mb-1">
            {comment.author_website ? (
              <a
                href={comment.author_website}
                target="_blank"
                rel="noopener noreferrer nofollow"
                className={`font-bold hover:underline ${isAdminReply ? 'text-red-700 dark:text-red-500' : 'text-gray-900 dark:text-gray-100'}`}
              >
                {comment.author_name}
              </a>
            ) : (
              <span className={`font-bold ${isAdminReply ? 'text-red-700 dark:text-red-500' : 'text-gray-900 dark:text-gray-100'}`}>
                {comment.author_name}
              </span>
            )}

            {isPinned && (
              <span className="bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-200 text-[10px] px-2 py-0.5 uppercase font-bold tracking-wider">
                📌 Fissato
              </span>
            )}

            <span className="text-xs text-gray-500 dark:text-gray-400">·</span>
            <time
              className="text-xs text-gray-500 dark:text-gray-400"
              title={new Date(comment.created_at).toLocaleString('it-IT')}
            >
              {comment.created_at_formatted}
            </time>
          </div>

          {/* Content */}
          <div className="text-sm text-gray-700 dark:text-gray-300 leading-relaxed font-serif">
            {comment.content.split('\n').map((paragraph, i) => (
              <p key={i} className="mb-2 last:mb-0">{paragraph}</p>
            ))}
          </div>

          {/* Actions */}
          <div className="flex items-center gap-4 mt-2">
            {!isReply && (
              <button
                onClick={() => setShowReplyForm(!showReplyForm)}
                className="text-xs text-gray-500 hover:text-red-700 transition uppercase tracking-wider font-bold"
              >
                {showReplyForm ? '✕ Annulla' : '↩️ Rispondi'}
              </button>
            )}

            {!isReply && replies.length > 0 && (
              <button
                onClick={() => setShowReplies(!showReplies)}
                className="text-xs text-gray-500 hover:text-red-700 transition uppercase tracking-wider font-bold"
              >
                {showReplies ? `▼ Nascondi ${replies.length} risposte` : `▶ Mostra ${replies.length} risposte`}
              </button>
            )}
          </div>

          {/* Reply form */}
          {showReplyForm && (
            <div className="mt-4">
              <CommentForm
                articleSlug={articleSlug}
                parentId={comment.id}
                onSuccess={handleReplySuccess}
                onCancel={() => setShowReplyForm(false)}
                compact
              />
            </div>
          )}

          {/* Replies */}
          {!isReply && showReplies && replies.length > 0 && (
            <div className="mt-4 border-l-2 border-gray-200 dark:border-gray-800 pl-2">
              {replies.map((reply) => (
                <CommentItem
                  key={reply.id}
                  comment={reply}
                  articleSlug={articleSlug}
                  onReplySuccess={onReplySuccess}
                  isReply
                />
              ))}
            </div>
          )}
        </div>
      </div>
    </article>
  );
}

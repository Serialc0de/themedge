'use client';

import { useEffect, useState } from 'react';
import CommentForm from './CommentForm';
import CommentItem from './CommentItem';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

interface Comment {
  id: number;
  parent_id: number | null;
  author_name: string;
  author_website: string | null;
  gravatar_url: string;
  content: string;
  is_pinned: boolean;
  likes_count: number;
  created_at: string;
  created_at_formatted: string;
  replies?: Comment[];
}

interface Props {
  articleSlug: string;
  allowComments?: boolean;
}

export default function CommentsSection({ articleSlug, allowComments = true }: Props) {
  const [comments, setComments] = useState<Comment[]>([]);
  const [totalCount, setTotalCount] = useState(0);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchComments();
  }, [articleSlug]);

  async function fetchComments() {
    setIsLoading(true);
    setError(null);

    try {
      const res = await fetch(`${API_URL}/articles/${articleSlug}/comments`, {
        cache: 'no-store',
      });

      if (!res.ok) throw new Error('Errore caricamento commenti');

      const data = await res.json();
      setComments(data.data || []);
      setTotalCount(data.total_count || 0);
    } catch (e) {
      setError('Impossibile caricare i commenti.');
    } finally {
      setIsLoading(false);
    }
  }

  function handleNewComment() {
    fetchComments();
  }

  if (!allowComments) {
    return (
      <section className="mt-12 pt-8 border-t-2 border-gray-300 dark:border-gray-700">
        <div className="text-center py-8 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
          <p className="text-2xl mb-2">🔒</p>
          <p className="text-sm text-gray-600 dark:text-gray-400">
            I commenti sono disabilitati per questo articolo.
          </p>
        </div>
      </section>
    );
  }

  return (
    <section className="mt-12 pt-8 border-t-2 border-gray-300 dark:border-gray-700" id="comments">
      <header className="mb-6">
        <h2 className="font-serif text-3xl font-bold flex items-center gap-3">
          <span className="w-1.5 h-8 bg-signal"></span>
          Commenti
          {totalCount > 0 && (
            <span className="text-lg text-gray-500 dark:text-gray-400 font-normal">
              ({totalCount})
            </span>
          )}
        </h2>
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-2">
          I commenti vengono pubblicati dopo moderazione. Sii rispettoso e costruttivo.
        </p>
      </header>

      {/* Form nuovo commento */}
      <div className="mb-8">
        <CommentForm
          articleSlug={articleSlug}
          onSuccess={handleNewComment}
        />
      </div>

      {/* Lista commenti */}
      {isLoading ? (
        <div className="text-center py-8 text-gray-500">
          <p className="text-sm">⏳ Caricamento commenti...</p>
        </div>
      ) : error ? (
        <div className="text-center py-8 border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/20">
          <p className="text-sm text-red-800 dark:text-red-200">⚠️ {error}</p>
        </div>
      ) : comments.length === 0 ? (
        <div className="text-center py-12 bg-gray-50 dark:bg-gray-900 border-2 border-dashed border-gray-300 dark:border-gray-700">
          <p className="text-3xl mb-3">💬</p>
          <h3 className="font-serif text-lg font-bold mb-2">Nessun commento</h3>
          <p className="text-sm text-gray-600 dark:text-gray-400">
            Sii il primo a commentare questo articolo.
          </p>
        </div>
      ) : (
        <div className="space-y-0">
          {comments.map((comment) => (
            <CommentItem
              key={comment.id}
              comment={comment}
              articleSlug={articleSlug}
              onReplySuccess={fetchComments}
            />
          ))}
        </div>
      )}
    </section>
  );
}

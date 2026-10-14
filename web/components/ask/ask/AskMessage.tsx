'use client';

import { useState } from 'react';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import Link from 'next/link';
import type { AskMessage as AskMessageType, AskSource, FeedbackVote } from '@/lib/ask';
import { sendFeedback } from '@/lib/ask';

type Props = {
  message: AskMessageType;
  sources?: AskSource[];
  onSourceClick?: (source: AskSource) => void;
  onFeedbackSent?: (messageId: string, vote: FeedbackVote) => void;
};

export function AskMessage({ message, sources = [], onSourceClick, onFeedbackSent }: Props) {
  const [copied, setCopied] = useState(false);
  const [feedbackSent, setFeedbackSent] = useState<FeedbackVote | null>(message.feedback ?? null);
  const [feedbackLoading, setFeedbackLoading] = useState(false);
  const [showComment, setShowComment] = useState(false);
  const [comment, setComment] = useState('');

  const isUser = message.role === 'user';

  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(message.content);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch {
      // ignore
    }
  };

  const handleFeedback = async (vote: FeedbackVote, withComment?: string) => {
    if (!message.queryId || feedbackLoading || feedbackSent) return;

    setFeedbackLoading(true);
    const result = await sendFeedback(message.queryId, vote, withComment);

    if (result.ok) {
      setFeedbackSent(vote);
      onFeedbackSent?.(message.id, vote);
      setShowComment(false);
      setComment('');
    }

    setFeedbackLoading(false);
  };

  // Trasforma [1] in link cliccabile
  const renderWithCitations = (text: string) => {
    const parts = text.split(/(\[\d+\])/g);
    return parts.map((part, i) => {
      const match = part.match(/^\[(\d+)\]$/);
      if (match) {
        const num = parseInt(match[1], 10);
        const source = sources[num - 1];
        if (source) {
          return (
            <button
              key={i}
              type="button"
              onClick={() => onSourceClick?.(source)}
              className="mx-0.5 inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-red-100 px-1.5 text-[10px] font-bold text-red-700 transition hover:bg-red-200 dark:bg-red-950/50 dark:text-red-300 dark:hover:bg-red-900"
              title={source.title}
            >
              {num}
            </button>
          );
        }
      }
      return <span key={i}>{part}</span>;
    });
  };

  return (
    <div className={`flex gap-3 ${isUser ? 'flex-row-reverse' : ''}`}>
      {/* Avatar */}
      <div
        className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold ${
          isUser
            ? 'bg-red-700 text-white'
            : 'bg-gradient-to-br from-red-500 to-orange-500 text-white'
        }`}
      >
        {isUser ? '👤' : '🤖'}
      </div>

      {/* Bubble */}
      <div className={`min-w-0 max-w-[85%] flex-1 ${isUser ? 'text-right' : ''}`}>
        <div
          className={`inline-block rounded-2xl px-4 py-3 text-left ${
            isUser
              ? 'bg-red-700 text-white'
              : 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-100'
          }`}
        >
          {isUser ? (
            <p className="whitespace-pre-wrap break-words text-sm">{message.content}</p>
          ) : (
            <>
              {message.status === 'streaming' && !message.content && (
                <div className="flex gap-1">
                  <span className="h-2 w-2 animate-bounce rounded-full bg-gray-400" style={{ animationDelay: '0ms' }} />
                  <span className="h-2 w-2 animate-bounce rounded-full bg-gray-400" style={{ animationDelay: '150ms' }} />
                  <span className="h-2 w-2 animate-bounce rounded-full bg-gray-400" style={{ animationDelay: '300ms' }} />
                </div>
              )}

              {message.content && (
                <div className="prose prose-sm max-w-none dark:prose-invert prose-p:my-2 prose-p:leading-relaxed prose-ul:my-2 prose-ol:my-2 prose-li:my-0.5">
                  <ReactMarkdown
                    remarkPlugins={[remarkGfm]}
                    components={{
                      p: ({ children }) => (
                        <p>
                          {Array.isArray(children)
                            ? children.map((child, i) =>
                                typeof child === 'string' ? (
                                  <span key={i}>{renderWithCitations(child)}</span>
                                ) : (
                                  child
                                )
                              )
                            : typeof children === 'string'
                            ? renderWithCitations(children)
                            : children}
                        </p>
                      ),
                      a: ({ href, children }) => {
                        if (href?.startsWith('/')) {
                          return <Link href={href}>{children}</Link>;
                        }
                        return (
                          <a href={href} target="_blank" rel="noopener noreferrer">
                            {children}
                          </a>
                        );
                      },
                    }}
                  >
                    {message.content}
                  </ReactMarkdown>

                  {message.status === 'streaming' && (
                    <span className="ml-0.5 inline-block h-4 w-1 animate-pulse bg-gray-500" />
                  )}
                </div>
              )}

              {message.status === 'error' && (
                <p className="text-sm text-red-600 dark:text-red-400">
                  ⚠️ {message.error || 'Errore durante la generazione'}
                </p>
              )}
            </>
          )}
        </div>

        {/* Actions per AI */}
        {!isUser && message.status === 'done' && message.content && (
          <div className="mt-2 flex flex-wrap items-center gap-2 text-xs">
            <button
              type="button"
              onClick={handleCopy}
              className="inline-flex items-center gap-1 rounded px-2 py-1 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800"
            >
              {copied ? '✅ Copiato' : '📋 Copia'}
            </button>

            {/* Feedback */}
            {message.queryId && (
              <>
                <span className="text-gray-300 dark:text-gray-700">|</span>
                <span className="text-gray-500">Questa risposta è stata utile?</span>

                <button
                  type="button"
                  onClick={() => handleFeedback(1)}
                  disabled={feedbackLoading || !!feedbackSent}
                  className={`inline-flex items-center gap-1 rounded px-2 py-1 transition ${
                    feedbackSent === 1
                      ? 'bg-green-100 text-green-700 dark:bg-green-950/50 dark:text-green-400'
                      : 'text-gray-500 hover:bg-green-50 hover:text-green-700 dark:hover:bg-green-950/30 dark:hover:text-green-400'
                  } disabled:cursor-not-allowed disabled:opacity-60`}
                  title="Utile"
                >
                  👍
                </button>

                <button
                  type="button"
                  onClick={() => handleFeedback(-1)}
                  disabled={feedbackLoading || !!feedbackSent}
                  className={`inline-flex items-center gap-1 rounded px-2 py-1 transition ${
                    feedbackSent === -1
                      ? 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-400'
                      : 'text-gray-500 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950/30 dark:hover:text-red-400'
                  } disabled:cursor-not-allowed disabled:opacity-60`}
                  title="Non utile"
                >
                  👎
                </button>

                {feedbackSent && (
                  <span className="text-xs text-gray-500">
                    {feedbackSent === 1 ? 'Grazie!' : 'Ci dispiace, cercheremo di migliorare'}
                  </span>
                )}
              </>
            )}
          </div>
        )}
      </div>
    </div>
  );
}

'use client';

import { useState, useRef, useEffect } from 'react';
import Link from 'next/link';

type Source = {
  id: string;
  kind: string;
  title: string;
  url: string;
  excerpt: string;
  score: number;
  icon: string;
  type: string;
};

type Props = {
  slug: string;
  title: string;
  suggestedQuestions?: string[];
};

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://api.themedge.com/api';

const DEFAULT_QUESTIONS = [
  'Chi è coinvolto?',
  'Quali sono gli eventi recenti?',
  'Quali entità sono correlate?',
];

export function WikiAskBox({ slug, title, suggestedQuestions }: Props) {
  const [question, setQuestion] = useState('');
  const [answer, setAnswer] = useState('');
  const [sources, setSources] = useState<Source[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [askedQuestion, setAskedQuestion] = useState<string | null>(null);
  const abortRef = useRef<AbortController | null>(null);
  const answerRef = useRef<HTMLDivElement>(null);

  const questions = suggestedQuestions || DEFAULT_QUESTIONS;

  const ask = async (q: string) => {
    const trimmed = q.trim();
    if (trimmed.length < 3) return;

    // Reset
    setLoading(true);
    setError(null);
    setAnswer('');
    setSources([]);
    setAskedQuestion(trimmed);
    setQuestion('');

    // Abort precedente
    if (abortRef.current) abortRef.current.abort();
    const controller = new AbortController();
    abortRef.current = controller;

    try {
      const res = await fetch(`${API_URL}/wiki/${slug}/ask`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'text/event-stream',
        },
        body: JSON.stringify({ question: trimmed, stream: true }),
        signal: controller.signal,
      });

      if (!res.ok || !res.body) {
        throw new Error(`HTTP ${res.status}`);
      }

      const reader = res.body.getReader();
      const decoder = new TextDecoder();
      let buffer = '';
      let currentEvent = '';

      while (true) {
        const { done, value } = await reader.read();
        if (done) break;

        buffer += decoder.decode(value, { stream: true });
        const lines = buffer.split('\n');
        buffer = lines.pop() || '';

        for (const line of lines) {
          if (line.startsWith('event: ')) {
            currentEvent = line.slice(7).trim();
          } else if (line.startsWith('data: ')) {
            const dataStr = line.slice(6);
            try {
              const data = JSON.parse(dataStr);
              if (currentEvent === 'sources') {
                setSources(data.sources || []);
              } else if (currentEvent === 'token') {
                setAnswer((prev) => prev + (data.text || ''));
              } else if (currentEvent === 'error') {
                setError(data.message || 'Errore durante la generazione');
              }
            } catch {
              // ignora JSON malformato
            }
          } else if (line === '') {
            currentEvent = '';
          }
        }
      }
    } catch (e) {
      if (e instanceof Error && e.name !== 'AbortError') {
        setError(e.message);
      }
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    ask(question);
  };

  return (
    <section className="rounded-xl border border-gray-200 bg-gradient-to-br from-indigo-50/50 to-white p-6 dark:border-gray-800 dark:from-indigo-950/20 dark:to-gray-900">
      {/* Header */}
      <header className="mb-4 flex items-start justify-between gap-4">
        <div>
          <h3 className="flex items-center gap-2 font-serif text-xl font-bold">
            <span aria-hidden="true">✨</span>
            <span>Chiedi a questa wiki</span>
          </h3>
          <p className="mt-1 text-xs text-gray-600 dark:text-gray-400">
            Risposta generata localmente da AI, basata su <strong>{title}</strong> e sugli articoli collegati.
          </p>
        </div>
      </header>

      {/* Input */}
      <form onSubmit={handleSubmit} className="mb-4 flex gap-2">
        <input
          type="text"
          value={question}
          onChange={(e) => setQuestion(e.target.value)}
          placeholder="Fai una domanda su questa voce..."
          className="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-gray-700 dark:bg-gray-800 dark:focus:ring-indigo-900"
          disabled={loading}
        />
        <button
          type="submit"
          disabled={loading || question.trim().length < 3}
          className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
        >
          {loading ? '...' : 'Chiedi'}
        </button>
      </form>

      {/* Domande suggerite */}
      {!askedQuestion && !loading && (
        <div className="mb-4">
          <p className="mb-2 text-xs uppercase tracking-wider text-gray-500">Prova a chiedere:</p>
          <div className="flex flex-wrap gap-2">
            {questions.map((q, idx) => (
              <button
                key={idx}
                type="button"
                onClick={() => ask(q)}
                className="rounded-full border border-gray-300 bg-white px-3 py-1 text-xs text-gray-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-indigo-950/30"
              >
                {q}
              </button>
            ))}
          </div>
        </div>
      )}

      {/* Domanda + Risposta */}
      {askedQuestion && (
        <div className="space-y-4">
          {/* Domanda */}
          <div className="flex items-start gap-2 text-sm">
            <span className="font-bold text-indigo-700 dark:text-indigo-400">Tu:</span>
            <span className="font-medium">{askedQuestion}</span>
          </div>

          {/* Risposta */}
          <div className="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <div className="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-400">
              <span>✨ Wiki AI</span>
              {loading && <span className="ml-auto animate-pulse text-gray-400">sta scrivendo...</span>}
            </div>

            {error ? (
              <p className="text-sm text-red-600 dark:text-red-400">
                Errore: {error}
              </p>
            ) : (
              <div
                ref={answerRef}
                className="whitespace-pre-wrap text-sm leading-relaxed text-gray-800 dark:text-gray-200"
              >
                {answer || (loading ? '⏳ Sto analizzando la wiki...' : '')}
              </div>
            )}

            {/* Fonti */}
            {sources.length > 0 && (
              <div className="mt-4 border-t border-gray-100 pt-3 dark:border-gray-800">
                <p className="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">
                  Fonti ({sources.length})
                </p>
                <ul className="space-y-2">
                  {sources.slice(0, 5).map((s) => (
                    <li key={s.id}>
                      <Link
                        href={s.url}
                        className="flex items-start gap-2 text-xs text-gray-600 transition hover:text-indigo-700 dark:text-gray-400 dark:hover:text-indigo-400"
                      >
                        <span aria-hidden="true">{s.icon || '📄'}</span>
                        <span className="min-w-0 flex-1">
                          <span className="font-medium">{s.title}</span>
                          <span className="ml-2 text-gray-400">
                            ({(s.score * 100).toFixed(0)}%)
                          </span>
                        </span>
                      </Link>
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </div>

          {/* Reset */}
          <button
            type="button"
            onClick={() => {
              setAskedQuestion(null);
              setAnswer('');
              setSources([]);
              setError(null);
            }}
            className="text-xs text-gray-500 underline hover:text-gray-700 dark:hover:text-gray-300"
          >
            Chiedi un'altra cosa
          </button>
        </div>
      )}

      {/* Disclaimer */}
      <p className="mt-4 text-[10px] leading-relaxed text-gray-400 dark:text-gray-500">
        ⚠️ Le risposte sono generate da AI (qwen2.5:3b) basandosi esclusivamente sul contenuto di questa wiki e degli articoli collegati.
        Potrebbero contenere imprecisioni. Verifica sempre le fonti citate.
      </p>
    </section>
  );
}

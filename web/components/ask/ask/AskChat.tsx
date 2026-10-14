'use client';

import { useCallback, useEffect, useRef, useState } from 'react';
import { askThemedge, type AskMessage as AskMessageType, type AskSource } from '@/lib/ask';
import { AskMessage } from './AskMessage';
import { AskSources } from './AskSources';
import { AskInput } from './AskInput';
import { AskSuggestions } from './AskSuggestions';

const STORAGE_KEY = 'themedge:ask:history';
const MAX_HISTORY = 20;

function generateId() {
  return Math.random().toString(36).slice(2, 11);
}

export function AskChat() {
  const [messages, setMessages] = useState<AskMessageType[]>([]);
  const [isStreaming, setIsStreaming] = useState(false);
  const [highlightedSourceId, setHighlightedSourceId] = useState<string | null>(null);
  const abortRef = useRef<(() => void) | null>(null);
  const scrollRef = useRef<HTMLDivElement>(null);

  // Load history
  useEffect(() => {
    try {
      const saved = localStorage.getItem(STORAGE_KEY);
      if (saved) {
        const parsed = JSON.parse(saved);
        if (Array.isArray(parsed)) {
          // Solo gli ultimi messaggi completati
          setMessages(parsed.filter((m: AskMessageType) => m.status === 'done').slice(-MAX_HISTORY));
        }
      }
    } catch {
      // ignore
    }
  }, []);

  // Save history
  useEffect(() => {
    try {
      const toSave = messages.filter((m) => m.status === 'done');
      localStorage.setItem(STORAGE_KEY, JSON.stringify(toSave.slice(-MAX_HISTORY)));
    } catch {
      // ignore
    }
  }, [messages]);

  // Auto scroll
  useEffect(() => {
    const el = scrollRef.current;
    if (!el) return;
    el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
  }, [messages]);

  const handleSubmit = useCallback((question: string) => {
    const userMessage: AskMessageType = {
      id: generateId(),
      role: 'user',
      content: question,
      timestamp: Date.now(),
    };

    const assistantId = generateId();
    const assistantMessage: AskMessageType = {
      id: assistantId,
      role: 'assistant',
      content: '',
      sources: [],
      status: 'streaming',
      timestamp: Date.now(),
    };

    setMessages((prev) => [...prev, userMessage, assistantMessage]);
    setIsStreaming(true);

    const abort = askThemedge(question, {
      onSources: (sources, queryId) => {
        setMessages((prev) =>
          prev.map((m) =>
            m.id === assistantId ? { ...m, sources, queryId } : m
          )
        );
      },
      onToken: (text) => {
        setMessages((prev) =>
          prev.map((m) =>
            m.id === assistantId ? { ...m, content: m.content + text } : m
          )
        );
      },
      onDone: () => {
        setMessages((prev) =>
          prev.map((m) =>
            m.id === assistantId ? { ...m, status: 'done' } : m
          )
        );
        setIsStreaming(false);
        abortRef.current = null;
      },
      onError: (error) => {
        setMessages((prev) =>
          prev.map((m) =>
            m.id === assistantId
              ? { ...m, status: 'error', error }
              : m
          )
        );
        setIsStreaming(false);
        abortRef.current = null;
      },
    });

    abortRef.current = abort;
  }, []);

  const handleStop = useCallback(() => {
    if (abortRef.current) {
      abortRef.current();
      abortRef.current = null;
    }
    setIsStreaming(false);
    setMessages((prev) =>
      prev.map((m) =>
        m.status === 'streaming' ? { ...m, status: 'done' } : m
      )
    );
  }, []);

  const handleClear = useCallback(() => {
    if (isStreaming) return;
    setMessages([]);
    localStorage.removeItem(STORAGE_KEY);
  }, [isStreaming]);

  return (
    <div className="flex h-[calc(100vh-180px)] min-h-[500px] flex-col">
      {/* Header */}
      {messages.length > 0 && (
        <div className="mb-3 flex items-center justify-between">
          <p className="text-xs uppercase tracking-widest text-gray-500">
            Conversazione ({messages.filter((m) => m.role === 'user').length} domande)
          </p>
          <button
            type="button"
            onClick={handleClear}
            disabled={isStreaming}
            className="rounded-lg px-3 py-1.5 text-xs font-semibold text-gray-600 transition hover:bg-gray-100 disabled:opacity-30 dark:text-gray-400 dark:hover:bg-gray-800"
          >
            🗑️ Nuova chat
          </button>
        </div>
      )}

      {/* Messages area */}
      <div
        ref={scrollRef}
        className="flex-1 space-y-6 overflow-y-auto pr-2"
      >
        {messages.length === 0 ? (
          <div className="flex h-full items-center justify-center">
            <AskSuggestions onSelect={handleSubmit} />
          </div>
        ) : (
          <>
            {messages.map((msg, i) => {
              const sources = msg.sources ?? [];
              // Usa le sources dell'ultimo messaggio assistant precedente
              const prevAssistant = messages
                .slice(0, i)
                .reverse()
                .find((m) => m.role === 'assistant' && m.sources && m.sources.length > 0);

              const effectiveSources = sources.length > 0 ? sources : (prevAssistant?.sources ?? []);

              return (
                <div key={msg.id}>
                  <AskMessage
                    message={msg}
                    sources={effectiveSources}
                    onSourceClick={(s) => setHighlightedSourceId(s.id)}
                  />
                  {msg.role === 'assistant' && sources.length > 0 && msg.status === 'done' && (
                    <div className="ml-11">
                      <AskSources sources={sources} highlightedId={highlightedSourceId} />
                    </div>
                  )}
                </div>
              );
            })}
          </>
        )}
      </div>

      {/* Input */}
      <div className="mt-4">
        <AskInput
          onSubmit={handleSubmit}
          disabled={isStreaming}
          onStop={handleStop}
        />
        <p className="mt-2 text-center text-[10px] text-gray-500">
          Themedge AI risponde basandosi sui nostri articoli e Wiki · Può commettere errori
        </p>
      </div>
    </div>
  );
}

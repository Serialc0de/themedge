/**
 * Client SSE per l'API "Chiedi a Themedge" (RAG).
 */

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://api.themedge.com/api';

// ============================================================
// TYPES
// ============================================================

export type AskSource = {
  id: string;
  kind: 'article' | 'wiki';
  title: string;
  url: string;
  excerpt: string;
  score: number;
  icon: string;
  type: string | null;
  category: string | null;
  published_at: string | null;
};

export type AskMessage = {
  id: string;
  role: 'user' | 'assistant';
  content: string;
  sources?: AskSource[];
  queryId?: number;
  status?: 'streaming' | 'done' | 'error';
  error?: string;
  timestamp: number;
  feedback?: 1 | -1; // voto già inviato
};

export type AskCallbacks = {
  onSources: (sources: AskSource[], queryId?: number) => void;
  onToken: (text: string) => void;
  onDone: () => void;
  onError: (message: string) => void;
};

// ============================================================
// SSE CLIENT
// ============================================================

/**
 * Invia una domanda all'API e gestisce lo streaming SSE.
 * Ritorna una funzione di cleanup (abort).
 */
export function askThemedge(question: string, callbacks: AskCallbacks): () => void {
  const controller = new AbortController();

  (async () => {
    try {
      const response = await fetch(`${API_URL}/ask`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'text/event-stream',
        },
        body: JSON.stringify({ question, stream: true }),
        signal: controller.signal,
      });

      if (!response.ok) {
        callbacks.onError(`Errore HTTP ${response.status}`);
        return;
      }

      if (!response.body) {
        callbacks.onError('Risposta senza corpo');
        return;
      }

      const reader = response.body.getReader();
      const decoder = new TextDecoder();
      let buffer = '';

      while (true) {
        const { done, value } = await reader.read();
        if (done) break;

        buffer += decoder.decode(value, { stream: true });

        // Parse eventi SSE (separati da \n\n)
        let separatorIndex;
        while ((separatorIndex = buffer.indexOf('\n\n')) !== -1) {
          const rawEvent = buffer.slice(0, separatorIndex);
          buffer = buffer.slice(separatorIndex + 2);

          parseSseEvent(rawEvent, callbacks);
        }
      }

      // Evento finale pendente
      if (buffer.trim()) {
        parseSseEvent(buffer, callbacks);
      }
    } catch (err: any) {
      if (err.name === 'AbortError') {
        // cancelled by user
        return;
      }
      callbacks.onError(err.message || 'Errore di rete');
    }
  })();

  return () => controller.abort();
}

function parseSseEvent(raw: string, callbacks: AskCallbacks) {
  let eventName = 'message';
  let dataStr = '';

  for (const line of raw.split('\n')) {
    if (line.startsWith('event:')) {
      eventName = line.slice(6).trim();
    } else if (line.startsWith('data:')) {
      dataStr += line.slice(5).trim();
    }
  }

  if (!dataStr) return;

  let data: any;
  try {
    data = JSON.parse(dataStr);
  } catch {
    return;
  }

  switch (eventName) {
    case 'sources':
      callbacks.onSources(data.sources ?? [], data.query_id);
      break;
    case 'token':
      if (data.text) callbacks.onToken(data.text);
      break;
    case 'done':
      callbacks.onDone();
      break;
    case 'error':
      callbacks.onError(data.message ?? 'Errore sconosciuto');
      break;
  }
}

// ============================================================
// HELPERS
// ============================================================

export const EXAMPLE_QUESTIONS = [
  'Chi è Volodymyr Zelensky?',
  'Cosa è successo a Papa Leone?',
  'Cosa sappiamo sulla guerra in Ucraina?',
  'Quali sanzioni ha approvato l\'UE?',
  'Chi è Pietro Parolin?',
  'Dove si trova il Policlinico Gemelli?',
];

/**
 * Estrae i numeri di citazione [1] [2] da una risposta.
 */
export function extractCitations(text: string): number[] {
  const matches = text.matchAll(/\[(\d+)\]/g);
  const nums = new Set<number>();
  for (const m of matches) {
    nums.add(parseInt(m[1], 10));
  }
  return Array.from(nums).sort((a, b) => a - b);
}

// ============================================================
// FEEDBACK
// ============================================================

export type FeedbackVote = 1 | -1;

export type FeedbackResult = {
  ok: boolean;
  message: string;
  feedback_id?: number;
};

/**
 * Invia feedback 👍/👎 per una risposta.
 */
export async function sendFeedback(
  queryId: number,
  vote: FeedbackVote,
  comment?: string
): Promise<FeedbackResult> {
  try {
    const res = await fetch(`${API_URL}/ask/feedback`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ query_id: queryId, vote, comment }),
    });

    const data = await res.json();
    return data;
  } catch (err: any) {
    return { ok: false, message: err.message || 'Errore di rete' };
  }
}

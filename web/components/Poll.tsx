'use client';

import { useState } from 'react';

interface PollOption {
  id: number;
  text: string;
  votes: number;
  percentage: number;
}

interface PollData {
  id: number;
  question: string;
  description?: string;
  is_active: boolean;
  allow_multiple: boolean;
  total_votes: number;
  options: PollOption[];
}

interface Props {
  poll: PollData;
}

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://admin.themedge.com/api';

export default function Poll({ poll: initialPoll }: Props) {
  const [poll, setPoll] = useState<PollData>(initialPoll);
  const [selected, setSelected] = useState<number | null>(null);
  const [hasVoted, setHasVoted] = useState(false);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  async function handleVote() {
    if (!selected) return;

    setLoading(true);
    setError('');

    try {
      const res = await fetch(`${API_URL}/polls/${poll.id}/vote`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ option_id: selected }),
      });

      const data = await res.json();

      if (!res.ok) {
        setError(data.error || 'Errore durante il voto');
        return;
      }

      // Ricarica il sondaggio aggiornato
      const pollRes = await fetch(`${API_URL}/polls/${poll.id}`);
      if (pollRes.ok) {
        const pollData = await pollRes.json();
        setPoll(pollData.data);
      }

      setHasVoted(true);
    } catch {
      setError('Errore di rete');
    } finally {
      setLoading(false);
    }
  }

  if (!poll.is_active) {
    return (
      <div className="border-2 border-gray-300 dark:border-gray-700 p-6 my-8 bg-gray-50 dark:bg-gray-900">
        <p className="text-sm text-gray-500 dark:text-gray-400">Sondaggio chiuso</p>
      </div>
    );
  }

  return (
    <div className="border-2 border-black dark:border-gray-100 p-6 my-8 bg-white dark:bg-gray-950">
      <div className="flex items-center gap-2 mb-3">
        <span className="w-2 h-2 bg-red-700 rounded-full animate-pulse" />
        <p className="text-xs font-bold uppercase tracking-widest text-red-700">Sondaggio</p>
      </div>

      <h3 className="font-serif text-2xl font-bold mb-2">{poll.question}</h3>

      {poll.description && (
        <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">{poll.description}</p>
      )}

      <div className="space-y-2 my-4">
        {poll.options.map((option) => {
          const isSelected = selected === option.id;
          const showResults = hasVoted;

          return (
            <button
              key={option.id}
              onClick={() => !hasVoted && setSelected(option.id)}
              disabled={hasVoted}
              className={`w-full text-left relative overflow-hidden border-2 transition p-3 ${
                isSelected
                  ? 'border-red-700 bg-red-50 dark:bg-red-950/20'
                  : 'border-gray-300 dark:border-gray-700 hover:border-black dark:hover:border-gray-100'
              } ${hasVoted ? 'cursor-default' : 'cursor-pointer'}`}
            >
              {showResults && (
                <div
                  className="absolute inset-y-0 left-0 bg-red-100 dark:bg-red-950/30 transition-all duration-500"
                  style={{ width: `${option.percentage}%` }}
                />
              )}

              <div className="relative flex items-center justify-between gap-4">
                <span className="font-medium">{option.text}</span>
                {showResults && (
                  <span className="text-sm font-bold text-red-700 whitespace-nowrap">
                    {option.percentage}% ({option.votes})
                  </span>
                )}
              </div>
            </button>
          );
        })}
      </div>

      {error && (
        <p className="text-sm text-red-600 mb-3">⚠️ {error}</p>
      )}

      <div className="flex items-center justify-between mt-4">
        <p className="text-xs text-gray-500 dark:text-gray-400">
          {poll.total_votes} {poll.total_votes === 1 ? 'voto' : 'voti'}
        </p>

        {!hasVoted ? (
          <button
            onClick={handleVote}
            disabled={!selected || loading}
            className="bg-black dark:bg-gray-100 text-white dark:text-gray-900 px-6 py-2 text-xs uppercase tracking-widest font-bold hover:bg-red-700 dark:hover:bg-red-700 dark:hover:text-white transition disabled:opacity-30 disabled:cursor-not-allowed"
          >
            {loading ? 'Invio...' : 'Vota'}
          </button>
        ) : (
          <p className="text-xs uppercase tracking-widest font-bold text-green-600">
            ✅ Voto registrato
          </p>
        )}
      </div>
    </div>
  );
}

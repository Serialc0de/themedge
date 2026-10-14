'use client';

import { EXAMPLE_QUESTIONS } from '@/lib/ask';

type Props = {
  onSelect: (question: string) => void;
};

export function AskSuggestions({ onSelect }: Props) {
  return (
    <div className="mx-auto max-w-3xl">
      <div className="mb-6 text-center">
        <span className="text-5xl">💬</span>
        <h2 className="mt-4 font-serif text-2xl font-bold text-gray-900 dark:text-gray-100">
          Chiedi qualcosa a Themedge
        </h2>
        <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
          Risposte basate sui nostri articoli e sulla Wiki, con citazioni verificabili.
        </p>
      </div>

      <div className="grid gap-2 sm:grid-cols-2">
        {EXAMPLE_QUESTIONS.map((q) => (
          <button
            key={q}
            type="button"
            onClick={() => onSelect(q)}
            className="group rounded-xl border border-gray-200 bg-white p-3 text-left text-sm transition hover:-translate-y-0.5 hover:border-red-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900 dark:hover:border-red-800"
          >
            <span className="flex items-center gap-2 text-gray-700 group-hover:text-red-700 dark:text-gray-300 dark:group-hover:text-red-400">
              <span className="text-base">💡</span>
              <span>{q}</span>
            </span>
          </button>
        ))}
      </div>
    </div>
  );
}

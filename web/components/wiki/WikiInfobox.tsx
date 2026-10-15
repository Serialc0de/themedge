type Props = {
  data: Record<string, string> | null;
  type: string;
  icon?: string | null;
};

// Valori da nascondere (rumore)
const HIDDEN_VALUES = new Set([
  'sconosciuto', 'sconosciuta', 'sconosciuti', 'sconosciute',
  'n/a', 'na', 'nd', '-', '—', '', 'non disponibile', 'non nota', 'non noto',
]);

/**
 * Trasforma una chiave tipo "data_di_nascita" in "Data di nascita".
 */
function humanizeKey(key: string): string {
  return key
    .replace(/[_-]+/g, ' ')
    .trim()
    .replace(/\b\w/g, (c) => c.toUpperCase());
}

/**
 * Normalizza un valore per il confronto (per nascondere "sconosciuto").
 */
function isHiddenValue(value: unknown): boolean {
  if (value === null || value === undefined) return true;
  const v = String(value).trim().toLowerCase();
  return HIDDEN_VALUES.has(v);
}

export function WikiInfobox({ data, type, icon }: Props) {
  if (!data || Object.keys(data).length === 0) return null;

  const entries = Object.entries(data).filter(([, v]) => !isHiddenValue(v));

  if (entries.length === 0) return null;

  return (
    <aside className="rounded-xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-5 dark:border-gray-800 dark:from-gray-900 dark:to-gray-950">
      <h3 className="mb-4 flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">
        <span aria-hidden="true">{icon ?? '📊'}</span>
        <span>Informazioni</span>
      </h3>
      <dl className="space-y-3">
        {entries.map(([key, value]) => (
          <div key={key} className="grid grid-cols-3 gap-2 border-b border-gray-100 pb-3 last:border-0 last:pb-0 dark:border-gray-800">
            <dt className="text-xs font-semibold text-gray-600 dark:text-gray-400">
              {humanizeKey(key)}
            </dt>
            <dd className="col-span-2 text-sm text-gray-900 dark:text-gray-100">
              {String(value)}
            </dd>
          </div>
        ))}
      </dl>
    </aside>
  );
}

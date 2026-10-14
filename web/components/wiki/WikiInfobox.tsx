type Props = {
  data: Record<string, string> | null;
  type: string;
  icon?: string | null;
};

export function WikiInfobox({ data, type, icon }: Props) {
  if (!data || Object.keys(data).length === 0) return null;

  return (
    <aside className="rounded-xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-5 dark:border-gray-800 dark:from-gray-900 dark:to-gray-950">
      <h3 className="mb-4 flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">
        <span aria-hidden="true">{icon ?? '📊'}</span>
        <span>Informazioni</span>
      </h3>
      <dl className="space-y-3">
        {Object.entries(data).map(([key, value]) => (
          <div key={key} className="grid grid-cols-3 gap-2 border-b border-gray-100 pb-3 last:border-0 last:pb-0 dark:border-gray-800">
            <dt className="text-xs font-semibold text-gray-600 dark:text-gray-400">
              {key}
            </dt>
            <dd className="col-span-2 text-sm text-gray-900 dark:text-gray-100">
              {value}
            </dd>
          </div>
        ))}
      </dl>
    </aside>
  );
}

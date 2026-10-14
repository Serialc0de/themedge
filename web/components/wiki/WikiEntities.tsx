import Link from 'next/link';
import type { WikiPageDetail } from '@/lib/wiki';

type Props = {
  entities: WikiPageDetail['related_entities'];
};

export function WikiEntities({ entities }: Props) {
  if (entities.length === 0) return null;

  return (
    <aside className="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
      <h3 className="mb-4 flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">
        <span aria-hidden="true">🔗</span>
        <span>Correlati</span>
      </h3>
      <ul className="space-y-2">
        {entities.map((entity) => (
          <li key={`${entity.id}-${entity.relation_type}`}>
            <Link
              href={`/entita/${entity.slug}`}
              className="group flex items-center gap-2 rounded px-2 py-1 transition hover:bg-gray-50 dark:hover:bg-gray-800"
            >
              <span className="text-sm">→</span>
              <span className="text-sm text-gray-700 group-hover:text-red-700 dark:text-gray-300 dark:group-hover:text-red-400">
                {entity.name}
              </span>
              <span className="ml-auto text-[10px] uppercase text-gray-400">
                {entity.type}
              </span>
            </Link>
          </li>
        ))}
      </ul>
    </aside>
  );
}

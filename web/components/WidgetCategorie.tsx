import Link from 'next/link';

export default function WidgetCategorie({ categories }: { categories: any[] }) {
  if (!categories?.length) return null;

  return (
    <div className="border-t-4 border-black bg-white p-4 mt-8">
      <h3 className="font-serif text-lg font-bold mb-4 pb-2 border-b-2 border-black">Sezioni</h3>
      <ul className="space-y-2">
        {categories.map((cat: any) => (
          <li key={cat.id}>
            <Link href={`/categoria/${cat.slug}`} className="flex justify-between items-center py-1 group hover:text-red-700 transition">
              <span className="flex items-center gap-2 text-sm font-medium">
                <span className="w-2 h-2" style={{ backgroundColor: cat.color || '#000' }}></span>
                {cat.name}
              </span>
              <span className="text-xs text-gray-400 group-hover:text-red-700">
                {cat.articles_count || 0}
              </span>
            </Link>
          </li>
        ))}
      </ul>
    </div>
  );
}

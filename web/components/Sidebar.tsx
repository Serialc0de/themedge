import WidgetUltimOra from './WidgetUltimOra';
import WidgetSeguici from './WidgetSeguici';
import WidgetNewsletter from './WidgetNewsletter';
import WidgetCategorie from './WidgetCategorie';

export default function Sidebar({ articles, categories }: { articles: any[]; categories: any[] }) {
  return (
    <aside className="space-y-0">
      <WidgetUltimOra articles={articles} />
      <WidgetSeguici />
      <WidgetNewsletter />
      <WidgetCategorie categories={categories} />
    </aside>
  );
}

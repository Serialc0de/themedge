import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import Link from 'next/link';

type Props = {
  content: string | null;
};

export function WikiContent({ content }: Props) {
  if (!content) return null;

  return (
    <div className="prose prose-lg max-w-none font-serif dark:prose-invert prose-headings:font-serif prose-headings:font-bold prose-a:text-red-700 prose-a:no-underline hover:prose-a:underline dark:prose-a:text-red-400">
      <ReactMarkdown
        remarkPlugins={[remarkGfm]}
        components={{
          // Link esterni con target blank
          a: ({ href, children, ...rest }) => {
            if (href?.startsWith('/')) {
              return <Link href={href}>{children}</Link>;
            }
            return (
              <a href={href} target="_blank" rel="noopener noreferrer" {...rest}>
                {children}
              </a>
            );
          },
        }}
      >
        {content}
      </ReactMarkdown>
    </div>
  );
}

import { sanitizeHtml } from '@/lib/sanitize';
import { VideoEmbed } from '@/components/embeds/VideoEmbed';
import { SpotifyEmbed } from '@/components/embeds/SpotifyEmbed';
import { GenericEmbed } from '@/components/embeds/GenericEmbed';
import { TwitterEmbed } from '@/components/embeds/TwitterEmbed';
import { InstagramEmbed } from '@/components/embeds/InstagramEmbed';
import { TikTokEmbed } from '@/components/embeds/TikTokEmbed';

type Props = {
  html: string;
  className?: string;
};

/**
 * Renderizza il content dell'articolo:
 *  1. sanitizza l'HTML (difesa output)
 *  2. estrae iframe/blockquote noti e li sostituisce con i componenti React
 *  3. il resto viene reso con dangerouslySetInnerHTML
 */
export function ArticleBody({ html, className = '' }: Props) {
  const clean = sanitizeHtml(html);
  const segments = parseSegments(clean);

  return (
    <div className={`prose prose-lg max-w-none font-serif dark:prose-invert ${className}`}>
      {segments.map((seg, i) => {
        if (seg.type === 'html') {
          return <div key={i} dangerouslySetInnerHTML={{ __html: seg.content }} />;
        }
        if (seg.type === 'youtube' || seg.type === 'vimeo') {
          return <VideoEmbed key={i} src={seg.src} title={seg.title} aspect={seg.aspect} />;
        }
        if (seg.type === 'spotify') {
          return <SpotifyEmbed key={i} src={seg.src} height={seg.height} />;
        }
        if (seg.type === 'twitter') {
          return <TwitterEmbed key={i} url={seg.url} />;
        }
        if (seg.type === 'instagram') {
          return <InstagramEmbed key={i} url={seg.url} />;
        }
        if (seg.type === 'tiktok') {
          return <TikTokEmbed key={i} url={seg.url} />;
        }
        if (seg.type === 'iframe') {
          return <GenericEmbed key={i} src={seg.src} aspect={seg.aspect} title={seg.title} />;
        }
        return null;
      })}
    </div>
  );
}

type Segment =
  | { type: 'html'; content: string }
  | { type: 'youtube' | 'vimeo'; src: string; title: string; aspect: string }
  | { type: 'spotify'; src: string; height: number }
  | { type: 'twitter' | 'instagram' | 'tiktok'; url: string }
  | { type: 'iframe'; src: string; aspect: string; title: string };

/**
 * Divide l'HTML in segmenti: HTML puro + embed riconosciuti.
 */
function parseSegments(html: string): Segment[] {
  const segments: Segment[] = [];
  // Regex che cattura iframe e blockquote specifici
  const regex = /(<iframe\b[^>]*>.*?<\/iframe>|<blockquote\b[^>]*class="[^"]*(twitter-tweet|instagram-media|tiktok-embed)[^"]*"[^>]*>[\s\S]*?<\/blockquote>)/gi;

  let lastIndex = 0;
  let match: RegExpExecArray | null;

  while ((match = regex.exec(html)) !== null) {
    if (match.index > lastIndex) {
      const before = html.slice(lastIndex, match.index).trim();
      if (before) segments.push({ type: 'html', content: before });
    }

    const raw = match[0];
    const seg = classifyEmbed(raw);
    if (seg) segments.push(seg);
    else segments.push({ type: 'html', content: raw });

    lastIndex = match.index + raw.length;
  }

  const tail = html.slice(lastIndex).trim();
  if (tail) segments.push({ type: 'html', content: tail });

  return segments;
}

function classifyEmbed(raw: string): Segment | null {
  // YouTube / Vimeo / Spotify iframe
  if (raw.startsWith('<iframe')) {
    const srcMatch = raw.match(/src=["']([^"']+)["']/i);
    if (!srcMatch) return null;
    const src = srcMatch[1];

    if (/youtube(?:-nocookie)?\.com/.test(src)) {
      return { type: 'youtube', src, title: 'YouTube video', aspect: '16/9' };
    }
    if (/player\.vimeo\.com/.test(src)) {
      return { type: 'vimeo', src, title: 'Vimeo video', aspect: '16/9' };
    }
    if (/open\.spotify\.com/.test(src)) {
      const hMatch = raw.match(/height=["'](\d+)["']/i);
      const height = hMatch ? parseInt(hMatch[1], 10) : 352;
      return { type: 'spotify', src, height };
    }
    return { type: 'iframe', src, aspect: '16/9', title: 'Embed' };
  }

  // Blockquote: Twitter / Instagram / TikTok
  if (raw.includes('twitter-tweet')) {
    const urlMatch = raw.match(/href=["']([^"']+)["']/i);
    if (urlMatch) return { type: 'twitter', url: urlMatch[1] };
    return null;
  }
  if (raw.includes('instagram-media')) {
    const urlMatch = raw.match(/data-instgrm-permalink=["']([^"']+)["']/i);
    if (urlMatch) return { type: 'instagram', url: urlMatch[1] };
    return null;
  }
  if (raw.includes('tiktok-embed')) {
    const urlMatch = raw.match(/cite=["']([^"']+)["']/i);
    if (urlMatch) return { type: 'tiktok', url: urlMatch[1] };
    return null;
  }

  return null;
}

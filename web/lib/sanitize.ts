import DOMPurify from 'isomorphic-dompurify';

const ALLOWED_TAGS = [
  'p', 'br', 'hr', 'div', 'span',
  'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
  'strong', 'b', 'em', 'i', 'u', 's', 'small', 'sub', 'sup', 'mark',
  'ul', 'ol', 'li', 'dl', 'dt', 'dd',
  'blockquote', 'cite', 'q',
  'pre', 'code', 'kbd', 'samp',
  'a', 'img', 'figure', 'figcaption', 'picture', 'source',
  'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption',
  'iframe', 'video', 'audio', 'track',
];

const ALLOWED_ATTR = [
  'class', 'id', 'title', 'style',
  'href', 'target', 'rel',
  'src', 'alt', 'width', 'height', 'loading', 'srcset', 'sizes',
  'frameborder', 'allow', 'allowfullscreen', 'referrerpolicy',
  'controls', 'poster', 'preload', 'muted', 'loop', 'playsinline',
  'type', 'cite', 'data-*', 'data-instgrm-permalink', 'data-instgrm-version',
  'data-video-id', 'data-dnt',
];

export function sanitizeHtml(html: string | null | undefined): string {
  if (!html) return '';
  return DOMPurify.sanitize(html, {
    ALLOWED_TAGS,
    ALLOWED_ATTR,
    ALLOW_DATA_ATTR: true,
    ALLOWED_URI_REGEXP: /^(?:https?|mailto|tel):/i,
    FORBID_TAGS: ['script', 'style', 'object', 'embed', 'form', 'input', 'button'],
    FORBID_ATTR: ['onerror', 'onload', 'onclick', 'onmouseover', 'onfocus', 'onblur'],
  });
}

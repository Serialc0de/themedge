'use client';

type Props = {
  src: string;
  aspect?: string;
  title?: string;
};

export function GenericEmbed({ src, aspect = '16/9', title = 'Embed' }: Props) {
  return (
    <div className="embed-generic my-6 overflow-hidden rounded-lg bg-black" style={{ aspectRatio: aspect }}>
      <iframe
        src={src}
        title={title}
        className="h-full w-full"
        loading="lazy"
        referrerPolicy="strict-origin-when-cross-origin"
        allowFullScreen
      />
    </div>
  );
}

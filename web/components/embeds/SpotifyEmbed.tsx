'use client';

type Props = {
  src: string;
  height?: number;
};

export function SpotifyEmbed({ src, height = 352 }: Props) {
  return (
    <div className="embed-spotify my-6 overflow-hidden rounded-lg">
      <iframe
        src={src}
        width="100%"
        height={height}
        frameBorder="0"
        allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
        loading="lazy"
        title="Spotify embed"
      />
    </div>
  );
}

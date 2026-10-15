import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  allowedDevOrigins: ['178.83.121.96'],

  images: {
    remotePatterns: [
      { protocol: 'https', hostname: 'admin.themedge.com' },
      { protocol: 'https', hostname: 'themedge.com' },
      { protocol: 'https', hostname: 'www.themedge.com' },
      { protocol: 'https', hostname: 'api.themedge.com' },
      { protocol: 'https', hostname: 'cdn.themedge.com' },
      { protocol: 'http', hostname: 'localhost' },
      { protocol: 'https', hostname: 'localhost' },
      { protocol: 'https', hostname: 'www.gravatar.com' },
      { protocol: 'https', hostname: 'gravatar.com' },
      { protocol: 'https', hostname: 'secure.gravatar.com' },
      { protocol: 'https', hostname: 'i.ytimg.com' },
      { protocol: 'https', hostname: 'img.youtube.com' },
      { protocol: 'https', hostname: 'youtube.com' },
      { protocol: 'https', hostname: 'www.youtube.com' },
      { protocol: 'https', hostname: 'i.vimeocdn.com' },
      { protocol: 'https', hostname: 'vimeo.com' },
      { protocol: 'https', hostname: 'pbs.twimg.com' },
      { protocol: 'https', hostname: 'scontent.cdninstagram.com' },
      { protocol: 'https', hostname: 'p16-sign-va.tiktokcdn.com' },
      { protocol: 'https', hostname: 'image-cdn-ak.spotifycdn.com' },
      { protocol: 'https', hostname: 'images.unsplash.com' },
      { protocol: 'https', hostname: 'via.placeholder.com' },
      { protocol: 'https', hostname: 'placehold.co' },
    ],
  },

  async redirects() {
    return [
      {
        source: '/categoria/:slug',
        destination: '/notizie/:slug',
        permanent: true,
      },
    ];
  },
};

export default nextConfig;

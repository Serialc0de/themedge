import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  allowedDevOrigins: ['178.83.121.96'],

  images: {
    remotePatterns: [
      {
        protocol: 'https',
        hostname: 'admin.themedge.com',
      },
      {
        protocol: 'https',
        hostname: 'themedge.com',
      },
      {
        protocol: 'https',
        hostname: 'www.gravatar.com',
      },
      {
        protocol: 'https',
        hostname: 'gravatar.com',
      },
      {
        protocol: 'https',
        hostname: 'secure.gravatar.com',
      },
    ],
  },
};

export default nextConfig;

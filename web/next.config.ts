import type { NextConfig } from "next";

const nextConfig: NextConfig = {
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
    ],
  },
};

export default nextConfig;

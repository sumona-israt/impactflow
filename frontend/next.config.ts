import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Required for docker/nextjs/Dockerfile's multi-stage build, which copies
  // only .next/standalone into the runtime image.
  output: "standalone",
};

export default nextConfig;

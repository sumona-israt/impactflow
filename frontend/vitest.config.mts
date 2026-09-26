import { defineConfig } from "vitest/config";

export default defineConfig({
  resolve: {
    tsconfigPaths: true,
  },
  test: {
    environment: "jsdom",
    setupFiles: ["./vitest.setup.ts"],
    globals: false,
    // e2e/ holds Playwright specs (a different test runner, different
    // `test` global) — excluded so Vitest doesn't try to execute them.
    exclude: ["node_modules/**", "e2e/**"],
  },
});

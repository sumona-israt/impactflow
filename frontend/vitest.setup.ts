import { cleanup } from "@testing-library/react";
import { afterEach } from "vitest";
import "@testing-library/jest-dom/vitest";

// RTL's automatic afterEach-cleanup relies on detecting Jest-style globals,
// which this project doesn't enable (test.globals: false, explicit imports
// everywhere else) — so it's wired up explicitly here instead.
afterEach(() => {
  cleanup();
});

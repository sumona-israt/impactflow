import { describe, expect, test } from "vitest";
import { formatCurrency, formatDate, statusLabel } from "./format";

describe("statusLabel", () => {
  test("title-cases a snake_case status", () => {
    expect(statusLabel("pending_approval")).toBe("Pending Approval");
  });

  test("passes through a single word", () => {
    expect(statusLabel("active")).toBe("Active");
  });
});

describe("formatDate", () => {
  test("renders an em dash for null/undefined", () => {
    expect(formatDate(null)).toBe("—");
    expect(formatDate(undefined)).toBe("—");
  });

  test("formats a real date string", () => {
    expect(formatDate("2026-01-15")).toMatch(/2026/);
  });
});

describe("formatCurrency", () => {
  test("renders an em dash for null/undefined/empty string", () => {
    expect(formatCurrency(null)).toBe("—");
    expect(formatCurrency(undefined)).toBe("—");
    expect(formatCurrency("")).toBe("—");
  });

  test("renders an em dash for a non-numeric string", () => {
    expect(formatCurrency("not-a-number")).toBe("—");
  });

  test("formats a numeric string and a number the same way", () => {
    expect(formatCurrency("1000")).toBe(formatCurrency(1000));
  });
});

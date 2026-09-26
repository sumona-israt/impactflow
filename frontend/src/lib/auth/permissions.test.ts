import { describe, expect, test } from "vitest";
import type { AuthUser } from "@/types/auth";
import { hasAnyPermission, hasPermission } from "./permissions";

function userWith(permissions: string[]): AuthUser {
  return {
    id: 1,
    name: "Test User",
    email: "test@example.com",
    phone: null,
    roles: [],
    permissions,
    last_login_at: null,
  };
}

describe("hasPermission", () => {
  test("is false for a null user", () => {
    expect(hasPermission(null, "programs.viewAny")).toBe(false);
  });

  test("is false when the user lacks the permission", () => {
    expect(hasPermission(userWith(["beneficiaries.viewAny"]), "programs.viewAny")).toBe(false);
  });

  test("is true when the user holds the permission among others", () => {
    expect(hasPermission(userWith(["beneficiaries.viewAny", "programs.viewAny"]), "programs.viewAny")).toBe(true);
  });
});

describe("hasAnyPermission", () => {
  test("is false for a null user regardless of the list", () => {
    expect(hasAnyPermission(null, ["programs.viewAny", "expenses.viewAny"])).toBe(false);
  });

  test("is false when the user holds none of the listed permissions", () => {
    expect(hasAnyPermission(userWith(["beneficiaries.viewAny"]), ["programs.viewAny", "expenses.viewAny"])).toBe(
      false,
    );
  });

  test("is true when the user holds at least one of the listed permissions", () => {
    expect(hasAnyPermission(userWith(["expenses.viewAny"]), ["programs.viewAny", "expenses.viewAny"])).toBe(true);
  });
});

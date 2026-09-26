import { describe, expect, test } from "vitest";
import { toApiError } from "./errors";

describe("toApiError", () => {
  test("shapes a Laravel 422 validation error response", async () => {
    const response = new Response(
      JSON.stringify({
        message: "The given data was invalid.",
        errors: { email: ["The email field is required."] },
      }),
      { status: 422, statusText: "Unprocessable Content" },
    );

    const error = await toApiError(response);

    expect(error.status).toBe(422);
    expect(error.message).toBe("The given data was invalid.");
    expect(error.fieldError("email")).toBe("The email field is required.");
    expect(error.fieldError("password")).toBeUndefined();
  });

  test("falls back to statusText when the body has no message", async () => {
    const response = new Response(JSON.stringify({}), { status: 500, statusText: "Internal Server Error" });

    const error = await toApiError(response);

    expect(error.status).toBe(500);
    expect(error.message).toBe("Internal Server Error");
    expect(error.fieldErrors).toEqual({});
  });

  test("falls back to a generic message when the body is not JSON", async () => {
    const response = new Response("<html>not json</html>", { status: 502, statusText: "" });

    const error = await toApiError(response);

    expect(error.status).toBe(502);
    expect(error.message).toBe("Something went wrong.");
  });
});

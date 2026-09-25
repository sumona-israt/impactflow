/**
 * Normalizes Laravel's two error shapes (see docs/api-design.md §1) into one
 * type so every feature module handles errors the same way, once.
 */
export class ApiError extends Error {
  readonly status: number;
  readonly fieldErrors: Record<string, string[]>;

  constructor(status: number, message: string, fieldErrors: Record<string, string[]> = {}) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.fieldErrors = fieldErrors;
  }

  fieldError(field: string): string | undefined {
    return this.fieldErrors[field]?.[0];
  }
}

export async function toApiError(response: Response): Promise<ApiError> {
  let body: unknown = null;

  try {
    body = await response.json();
  } catch {
    // response had no JSON body (e.g. a proxy/server error page)
  }

  const message =
    (body as { message?: string } | null)?.message ??
    response.statusText ??
    "Something went wrong.";

  const errors = (body as { errors?: Record<string, string[]> } | null)?.errors ?? {};

  return new ApiError(response.status, message, errors);
}

import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, test, vi } from "vitest";
import { PaginationControls } from "./pagination-controls";

describe("PaginationControls", () => {
  test("shows 'No results' when total is 0", () => {
    render(<PaginationControls meta={{ page: 1, per_page: 25, total: 0 }} onPageChange={vi.fn()} />);

    expect(screen.getByText("No results")).toBeInTheDocument();
  });

  test("shows the current page summary when there are results", () => {
    render(<PaginationControls meta={{ page: 2, per_page: 25, total: 60 }} onPageChange={vi.fn()} />);

    expect(screen.getByText("Showing page 2 of 3 (60 total)")).toBeInTheDocument();
  });

  test("disables Previous on the first page and Next on the last page", () => {
    render(<PaginationControls meta={{ page: 1, per_page: 25, total: 25 }} onPageChange={vi.fn()} />);

    expect(screen.getByRole("button", { name: "Previous" })).toBeDisabled();
    expect(screen.getByRole("button", { name: "Next" })).toBeDisabled();
  });

  test("clicking Next/Previous calls onPageChange with the right page number", () => {
    const onPageChange = vi.fn();
    render(<PaginationControls meta={{ page: 2, per_page: 25, total: 100 }} onPageChange={onPageChange} />);

    fireEvent.click(screen.getByRole("button", { name: "Next" }));
    expect(onPageChange).toHaveBeenCalledWith(3);

    fireEvent.click(screen.getByRole("button", { name: "Previous" }));
    expect(onPageChange).toHaveBeenCalledWith(1);
  });
});

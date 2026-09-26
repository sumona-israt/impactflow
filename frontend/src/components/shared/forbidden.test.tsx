import { render, screen } from "@testing-library/react";
import { describe, expect, test } from "vitest";
import { Forbidden } from "./forbidden";

describe("Forbidden", () => {
  test("renders the default message", () => {
    render(<Forbidden />);

    expect(screen.getByText("Forbidden")).toBeInTheDocument();
    expect(screen.getByText("You don't have permission to view this page.")).toBeInTheDocument();
  });

  test("renders a custom message", () => {
    render(<Forbidden message="You don't have permission to generate any report." />);

    expect(screen.getByText("You don't have permission to generate any report.")).toBeInTheDocument();
  });
});

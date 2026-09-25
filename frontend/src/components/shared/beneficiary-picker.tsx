"use client";

import { useQuery } from "@tanstack/react-query";
import { useEffect, useState } from "react";
import { Input } from "@/components/ui/input";
import { listBeneficiaries } from "@/lib/api/endpoints/beneficiaries";
import type { BeneficiaryListItem } from "@/types/beneficiaries";

export function BeneficiaryPicker({
  onSelect,
}: {
  onSelect: (beneficiary: BeneficiaryListItem) => void;
}) {
  const [query, setQuery] = useState("");
  const [debounced, setDebounced] = useState("");

  useEffect(() => {
    const timeout = setTimeout(() => setDebounced(query), 300);
    return () => clearTimeout(timeout);
  }, [query]);

  const { data, isFetching } = useQuery({
    queryKey: ["beneficiary-picker", debounced],
    queryFn: () => listBeneficiaries({ q: debounced }),
    enabled: debounced.trim().length > 1,
  });

  return (
    <div className="space-y-2">
      <Input
        placeholder="Search beneficiary by name…"
        value={query}
        onChange={(e) => setQuery(e.target.value)}
      />
      {debounced.trim().length > 1 && (
        <div className="max-h-48 overflow-y-auto rounded-md border">
          {isFetching && <p className="p-2 text-sm text-muted-foreground">Searching…</p>}
          {!isFetching && data?.data.length === 0 && (
            <p className="p-2 text-sm text-muted-foreground">No beneficiaries found.</p>
          )}
          {data?.data.map((beneficiary) => (
            <button
              key={beneficiary.id}
              type="button"
              className="flex w-full flex-col items-start px-3 py-2 text-left text-sm hover:bg-muted"
              onClick={() => {
                onSelect(beneficiary);
                setQuery("");
                setDebounced("");
              }}
            >
              <span className="font-medium">{beneficiary.full_name}</span>
              <span className="text-xs text-muted-foreground">{beneficiary.district ?? "No district on file"}</span>
            </button>
          ))}
        </div>
      )}
    </div>
  );
}

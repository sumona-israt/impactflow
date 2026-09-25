import { Button } from "@/components/ui/button";
import type { PageMeta } from "@/types/rbac";

export function PaginationControls({
  meta,
  onPageChange,
}: {
  meta: PageMeta;
  onPageChange: (page: number) => void;
}) {
  const lastPage = Math.max(1, Math.ceil(meta.total / meta.per_page));

  return (
    <div className="flex items-center justify-between text-sm text-muted-foreground">
      <span>
        {meta.total === 0
          ? "No results"
          : `Showing page ${meta.page} of ${lastPage} (${meta.total} total)`}
      </span>
      <div className="flex gap-2">
        <Button
          variant="outline"
          size="sm"
          disabled={meta.page <= 1}
          onClick={() => onPageChange(meta.page - 1)}
        >
          Previous
        </Button>
        <Button
          variant="outline"
          size="sm"
          disabled={meta.page >= lastPage}
          onClick={() => onPageChange(meta.page + 1)}
        >
          Next
        </Button>
      </div>
    </div>
  );
}

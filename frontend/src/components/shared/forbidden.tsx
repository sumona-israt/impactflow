import { ShieldAlert } from "lucide-react";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";

export function Forbidden({ message = "You don't have permission to view this page." }: { message?: string }) {
  return (
    <Alert variant="destructive" className="max-w-lg">
      <ShieldAlert className="size-4" />
      <AlertTitle>Forbidden</AlertTitle>
      <AlertDescription>{message}</AlertDescription>
    </Alert>
  );
}

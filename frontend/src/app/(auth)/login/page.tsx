import { redirect } from "next/navigation";
import { getServerAuth } from "@/lib/auth/session";
import { LoginForm } from "./login-form";

export default async function LoginPage() {
  const user = await getServerAuth();

  if (user) {
    redirect("/dashboard");
  }

  return (
    <div className="flex min-h-screen flex-1 items-center justify-center bg-muted/30 px-4">
      <div className="w-full max-w-sm">
        <div className="mb-8 text-center">
          <h1 className="text-xl font-semibold tracking-tight">ImpactFlow</h1>
          <p className="mt-1 text-sm text-muted-foreground">
            NGO Operations. Connected. Automated. Measurable.
          </p>
        </div>
        <LoginForm />
      </div>
    </div>
  );
}

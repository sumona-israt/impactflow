import { redirect } from "next/navigation";
import { getServerAuth } from "@/lib/auth/session";

export default async function RootPage() {
  const user = await getServerAuth();

  redirect(user ? "/dashboard" : "/login");
}

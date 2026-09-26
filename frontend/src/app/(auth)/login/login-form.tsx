"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { login } from "@/lib/api/endpoints/auth";
import { ApiError } from "@/lib/api/errors";

const loginSchema = z.object({
  email: z.string().min(1, "Email is required").email("Enter a valid email address"),
  password: z.string().min(1, "Password is required"),
});

type LoginValues = z.infer<typeof loginSchema>;

export function LoginForm() {
  const router = useRouter();
  const [showPassword, setShowPassword] = useState(false);

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<LoginValues>({
    resolver: zodResolver(loginSchema),
    defaultValues: { email: "", password: "" },
  });

  async function onSubmit(values: LoginValues) {
    try {
      await login(values.email, values.password);
      router.push("/dashboard");
      router.refresh();
    } catch (error) {
      if (error instanceof ApiError) {
        setError("email", { message: error.fieldError("email") ?? error.message });
        return;
      }
      setError("email", { message: "Unable to reach the server. Please try again." });
    }
  }

  return (
    <section className="w-full md:w-[48%] lg:w-[46%] h-full bg-white flex flex-col justify-between overflow-y-auto px-6 sm:px-12 lg:px-16 py-8">
      {/* Top utility row */}
      <div className="w-full flex items-center justify-between pb-4">
        <div className="flex items-center gap-2.5">
          {/* Mobile logo */}
          <div className="flex items-center gap-2 md:hidden">
            <div
              className="w-8 h-8 rounded-lg flex items-center justify-center"
              style={{ background: "#1e2a5e" }}
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="3" fill="white" />
                <circle cx="4" cy="6" r="2" fill="rgba(255,255,255,0.7)" />
                <circle cx="20" cy="6" r="2" fill="rgba(255,255,255,0.7)" />
                <circle cx="4" cy="18" r="2" fill="rgba(255,255,255,0.7)" />
                <circle cx="20" cy="18" r="2" fill="rgba(255,255,255,0.7)" />
                <line x1="6" y1="6" x2="10" y2="10" stroke="rgba(255,255,255,0.5)" strokeWidth="1.5" />
                <line x1="18" y1="6" x2="14" y2="10" stroke="rgba(255,255,255,0.5)" strokeWidth="1.5" />
                <line x1="6" y1="18" x2="10" y2="14" stroke="rgba(255,255,255,0.5)" strokeWidth="1.5" />
                <line x1="18" y1="18" x2="14" y2="14" stroke="rgba(255,255,255,0.5)" strokeWidth="1.5" />
              </svg>
            </div>
            <span className="text-base font-bold text-[#1e2a5e]">ImpactFlow</span>
          </div>
          {/* Desktop gateway node indicator */}
          <div className="hidden md:flex items-center gap-2 text-[12px] text-slate-500 font-medium">
            <span className="w-2 h-2 rounded-full bg-emerald-500" />
            Gateway Node: Geneva Primary (CHE-01)
          </div>
        </div>
        <div className="flex items-center gap-3">
          <button
            type="button"
            className="inline-flex items-center gap-1 px-2.5 py-1 rounded text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors text-[12px] font-medium focus:outline-none"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm6.93 6h-2.95c-.32-1.25-.78-2.45-1.38-3.56 1.84.63 3.37 1.91 4.33 3.56zM12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96zM4.26 14C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2 0 .68.06 1.34.14 2H4.26zm.82 2h2.95c.32 1.25.78 2.45 1.38 3.56-1.84-.63-3.37-1.9-4.33-3.56zm2.95-8H5.08c.96-1.66 2.49-2.93 4.33-3.56C8.81 5.55 8.35 6.75 8.03 8zM12 19.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96zM14.34 14H9.66c-.09-.66-.16-1.32-.16-2 0-.68.07-1.35.16-2h4.68c.09.65.16 1.32.16 2 0 .68-.07 1.34-.16 2zm.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95c-.96 1.65-2.49 2.93-4.33 3.56zM16.36 14c.08-.66.14-1.32.14-2 0-.68-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2h-3.38z" />
            </svg>
            EN-US
            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
              <path d="M7 10l5 5 5-5z" />
            </svg>
          </button>
        </div>
      </div>

      {/* Main sign-in container */}
      <div className="w-full max-w-[420px] mx-auto my-auto py-4">
        {/* Header */}
        <div className="mb-8">
          <div
            className="w-10 h-10 rounded-lg border border-slate-200 p-2 mb-5 hidden md:flex items-center justify-center"
            style={{ background: "#f8fafc" }}
          >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="12" r="3" fill="#1e2a5e" />
              <circle cx="4" cy="6" r="2" fill="#4f6bc7" />
              <circle cx="20" cy="6" r="2" fill="#4f6bc7" />
              <circle cx="4" cy="18" r="2" fill="#4f6bc7" />
              <circle cx="20" cy="18" r="2" fill="#4f6bc7" />
              <line x1="6" y1="6" x2="10" y2="10" stroke="#94a3b8" strokeWidth="1.5" />
              <line x1="18" y1="6" x2="14" y2="10" stroke="#94a3b8" strokeWidth="1.5" />
              <line x1="6" y1="18" x2="10" y2="14" stroke="#94a3b8" strokeWidth="1.5" />
              <line x1="18" y1="18" x2="14" y2="14" stroke="#94a3b8" strokeWidth="1.5" />
            </svg>
          </div>
          <h2 className="text-[28px] font-bold text-[#1e2a5e] tracking-tight leading-tight">
            Welcome back
          </h2>
          <p className="text-[14px] text-slate-500 mt-1.5">Sign in to your account</p>
        </div>

        {/* Form */}
        <form className="space-y-4" onSubmit={handleSubmit(onSubmit)} noValidate>
          {/* Email */}
          <div>
            <label htmlFor="email" className="block text-[13px] font-medium text-slate-700 mb-1.5">
              Work Email <span aria-hidden="true" className="text-red-500">*</span>
            </label>
            <div className="relative">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                </svg>
              </div>
              <input
                id="email"
                type="email"
                autoComplete="email"
                placeholder="your.name@organization.org"
                aria-invalid={Boolean(errors.email)}
                className="block w-full pl-10 pr-3.5 h-[38px] text-[14px] bg-white border border-slate-300 rounded text-slate-900 placeholder:text-slate-400 focus:border-[#2d4a9e] focus:outline-none focus:ring-2 focus:ring-[#4f6bc7]/20 transition-colors"
                {...register("email")}
              />
            </div>
            {errors.email && (
              <p className="mt-1 text-[12px] text-red-600">{errors.email.message}</p>
            )}
          </div>

          {/* Password */}
          <div>
            <label htmlFor="password" className="block text-[13px] font-medium text-slate-700 mb-1.5">
              Password <span aria-hidden="true" className="text-red-500">*</span>
            </label>
            <div className="relative">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z" />
                </svg>
              </div>
              <input
                id="password"
                type={showPassword ? "text" : "password"}
                autoComplete="current-password"
                placeholder="••••••••••••"
                aria-invalid={Boolean(errors.password)}
                className="block w-full pl-10 pr-10 h-[38px] text-[14px] bg-white border border-slate-300 rounded text-slate-900 placeholder:text-slate-400 focus:border-[#2d4a9e] focus:outline-none focus:ring-2 focus:ring-[#4f6bc7]/20 transition-colors"
                {...register("password")}
              />
              <button
                type="button"
                aria-label="Toggle password visibility"
                onClick={() => setShowPassword((v) => !v)}
                className="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition-colors"
              >
                {showPassword ? (
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z" />
                  </svg>
                ) : (
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
                  </svg>
                )}
              </button>
            </div>
            {errors.password && (
              <p className="mt-1 text-[12px] text-red-600">{errors.password.message}</p>
            )}
          </div>

          {/* Remember me & forgot password */}
          <div className="flex items-center justify-between pt-1">
            <label className="flex items-center gap-2 cursor-pointer select-none">
              <input
                type="checkbox"
                id="remember-device"
                className="h-4 w-4 rounded border-slate-300 text-[#1e2a5e] focus:ring-[#4f6bc7]"
              />
              <span className="text-[13px] text-slate-500">Remember this device for 30 days</span>
            </label>
            <a
              href="#"
              className="text-[13px] font-medium text-[#3e59ae] hover:text-[#1e2a5e] hover:underline transition-colors focus:outline-none"
            >
              Forgot password?
            </a>
          </div>

          {/* Submit */}
          <div className="pt-2">
            <button
              type="submit"
              disabled={isSubmitting}
              className="w-full h-10 px-4 flex items-center justify-center gap-2 text-white font-semibold text-[15px] rounded transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#4f6bc7] disabled:opacity-60 disabled:cursor-not-allowed"
              style={{ background: isSubmitting ? "#4f6bc7" : "#4f6bc7" }}
              onMouseEnter={(e) => { if (!isSubmitting) e.currentTarget.style.background = "#3e59ae"; }}
              onMouseLeave={(e) => { e.currentTarget.style.background = "#4f6bc7"; }}
            >
              {isSubmitting ? (
                <>
                  <svg className="animate-spin" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  Signing in…
                </>
              ) : (
                <>
                  Sign In
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z" />
                  </svg>
                </>
              )}
            </button>
          </div>
        </form>

        {/* SSO Divider */}
        <div className="relative my-6">
          <div aria-hidden="true" className="absolute inset-0 flex items-center">
            <div className="w-full border-t border-slate-200" />
          </div>
          <div className="relative flex justify-center">
            <span className="px-3 bg-white text-[11px] font-semibold uppercase tracking-wider text-slate-400">
              Or authenticate via Institutional SSO
            </span>
          </div>
        </div>

        {/* SSO Button */}
        <button
          type="button"
          className="w-full h-10 px-4 bg-white border border-slate-300 hover:border-slate-400 hover:bg-slate-50 text-[#1e2a5e] font-semibold text-[14px] rounded flex items-center justify-center gap-2.5 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-[#4f6bc7]"
        >
          <svg width="18" height="18" viewBox="0 0 24 24" fill="#1e2a5e">
            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z" />
          </svg>
          Continue with Humanitarian ID / Okta SSO
        </button>

        {/* Info notice */}
        <div className="mt-6 p-3 rounded bg-blue-50 border border-blue-100 flex items-start gap-2.5">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="#3e59ae" className="shrink-0 mt-0.5">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" />
          </svg>
          <p className="text-[13px] text-slate-600">
            Offline field dispatch staff using satellite hardware tokens must sync via the{" "}
            <a href="#" className="text-[#3e59ae] font-medium hover:underline">
              Field Cache Terminal
            </a>
            .
          </p>
        </div>
      </div>

      {/* Footer */}
      <footer className="w-full pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2 text-center sm:text-left">
        <span className="text-[12px] font-mono text-slate-400">
          ImpactFlow ERP v2.0 • Build 4.18.2
        </span>
        <div className="text-[13px] text-slate-500">
          Need urgent system access?{" "}
          <a
            href="#"
            className="font-medium text-[#3e59ae] hover:text-[#1e2a5e] hover:underline transition-colors"
          >
            Contact Global IT Dispatch
          </a>
        </div>
      </footer>
    </section>
  );
}

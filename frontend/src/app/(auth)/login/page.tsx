import { redirect } from "next/navigation";
import { getServerAuth } from "@/lib/auth/session";
import { LoginForm } from "./login-form";

export default async function LoginPage() {
  const user = await getServerAuth();

  if (user) {
    redirect("/dashboard");
  }

  return (
    <main className="w-full h-screen flex flex-col md:flex-row overflow-hidden">
      {/* LEFT PANEL — Brand Showcase */}
      <section
        className="hidden md:flex md:w-[52%] lg:w-[54%] relative flex-col justify-between p-10 lg:p-14 overflow-hidden text-white"
        style={{
          background: "linear-gradient(135deg, #061449 0%, #1e2a5e 40%, #2d4a9e 80%, #3e59ae 100%)",
        }}
      >
        {/* Subtle grid pattern */}
        <div
          className="absolute inset-0 pointer-events-none opacity-30"
          style={{
            backgroundSize: "32px 32px",
            backgroundImage: `
              linear-gradient(to right, rgba(255,255,255,0.05) 1px, transparent 1px),
              linear-gradient(to bottom, rgba(255,255,255,0.05) 1px, transparent 1px)
            `,
          }}
        />
        {/* Glow accents */}
        <div className="absolute -right-24 -top-24 w-96 h-96 bg-[#4f6bc7]/20 rounded-full blur-3xl pointer-events-none" />
        <div className="absolute -left-20 bottom-1/4 w-80 h-80 bg-[#061449]/40 rounded-full blur-2xl pointer-events-none" />

        {/* Top header */}
        <div className="relative z-10 flex items-center justify-between">
          <div className="flex items-center gap-3.5">
            <div
              className="w-11 h-11 rounded-lg flex items-center justify-center"
              style={{ background: "rgba(255,255,255,0.1)", border: "1px solid rgba(255,255,255,0.2)" }}
            >
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
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
            <div>
              <span className="text-lg font-bold tracking-tight block leading-none">ImpactFlow</span>
              <span className="text-[10px] font-semibold uppercase tracking-widest text-blue-200/80 mt-0.5 block">
                Enterprise NGO Infrastructure
              </span>
            </div>
          </div>
          <div
            className="inline-flex items-center gap-2 px-3 py-1 rounded-full text-white text-[11px] font-semibold"
            style={{ background: "rgba(255,255,255,0.1)", border: "1px solid rgba(255,255,255,0.15)" }}
          >
            <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
            Regional Nodes Active
          </div>
        </div>

        {/* Center content */}
        <div className="relative z-10 my-auto py-6 max-w-xl">
          <div
            className="inline-flex items-center gap-2 px-2.5 py-1 rounded text-blue-200 text-[12px] font-medium mb-5"
            style={{ background: "rgba(255,255,255,0.08)", border: "1px solid rgba(255,255,255,0.15)" }}
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z" />
            </svg>
            High-Assurance Financial &amp; Relief Operations
          </div>

          <h1 className="text-4xl font-bold leading-tight tracking-tight mb-4">
            NGO Operations.<br />
            <span className="text-blue-300">Connected. Automated. Measurable.</span>
          </h1>
          <p className="text-base text-blue-100/90 leading-relaxed mb-8 max-w-lg">
            Institutional resource planning, automated donor traceability, and
            real-time field coordination across sovereign operational corridors.
          </p>

          {/* Stats grid */}
          <div className="grid grid-cols-2 gap-3.5 pt-2">
            <div
              className="p-3.5 rounded-lg transition-colors"
              style={{ background: "rgba(255,255,255,0.07)", border: "1px solid rgba(255,255,255,0.15)" }}
            >
              <div className="flex items-center justify-between mb-2">
                <span className="text-[10px] font-bold uppercase tracking-wider text-blue-200/80">
                  Active Deployments
                </span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="rgba(255,255,255,0.6)" className="shrink-0">
                  <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                </svg>
              </div>
              <div className="text-3xl font-bold text-white tracking-tight">140+</div>
              <div className="text-[11px] text-white/70 mt-0.5">Field Missions Connected</div>
            </div>
            <div
              className="p-3.5 rounded-lg transition-colors"
              style={{ background: "rgba(255,255,255,0.07)", border: "1px solid rgba(255,255,255,0.15)" }}
            >
              <div className="flex items-center justify-between mb-2">
                <span className="text-[10px] font-bold uppercase tracking-wider text-blue-200/80">
                  Governance Framework
                </span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="rgba(255,255,255,0.6)" className="shrink-0">
                  <path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 9H9V9h10v2zm-4 4H9v-2h6v2zm4-8H9V5h10v2z" />
                </svg>
              </div>
              <div className="text-3xl font-bold text-white tracking-tight">Tier-4</div>
              <div className="text-[11px] text-white/70 mt-0.5">Data Sovereignty &amp; IATI Compliant</div>
            </div>
          </div>

          {/* Status badges */}
          <div className="mt-4 flex flex-wrap items-center gap-2">
            {[
              { dot: "bg-emerald-400", text: "UN-OCHA Synced: 99.98%" },
              { dot: "bg-blue-400", text: "Ledger Trace: Zero-Lag" },
              { dot: "bg-cyan-400", text: "Multi-Currency Reconciled" },
            ].map(({ dot, text }) => (
              <span
                key={text}
                className="px-2.5 py-1 rounded text-white/80 text-[12px] font-mono flex items-center gap-1.5"
                style={{ background: "rgba(0,0,0,0.25)", border: "1px solid rgba(255,255,255,0.1)" }}
              >
                <span className={`w-1.5 h-1.5 rounded-full ${dot}`} />
                {text}
              </span>
            ))}
          </div>
        </div>

        {/* Bottom security footer */}
        <div
          className="relative z-10 pt-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-blue-200/80"
          style={{ borderTop: "1px solid rgba(255,255,255,0.15)" }}
        >
          <div className="flex items-center gap-2.5 text-[13px] font-medium">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="white" className="shrink-0">
              <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z" />
            </svg>
            256-bit TLS • SOC2 Type II Certified
          </div>
          <div className="text-[11px] text-white/60">IATI Standard v2.03 Protocol</div>
        </div>
      </section>

      {/* RIGHT PANEL — Sign-in form */}
      <LoginForm />
    </main>
  );
}

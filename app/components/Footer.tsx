"use client";

export default function Footer() {
  return (
    <footer className="border-t border-white/5 py-12 px-6">
      <div className="max-w-7xl mx-auto flex flex-col gap-8">
        {/* Top row */}
        <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
          <div className="flex items-center gap-2">
            <div className="w-6 h-6 rounded-md bg-gradient-to-br from-violet-500 to-blue-500 flex items-center justify-center">
              <span className="text-[10px] font-bold text-white">V</span>
            </div>
            <span className="text-sm text-white/30">Direct WordPress access for AI agents.</span>
          </div>

          <div className="flex items-center gap-6">
            {[
              { label: "GitHub", href: "https://github.com/Rankwisy/vibepress" },
              { label: "Documentation", href: "https://github.com/Rankwisy/vibepress#readme" },
              { label: "Privacy", href: "#" },
              { label: "Terms", href: "#" },
            ].map((link) => (
              <a
                key={link.label}
                href={link.href}
                target={link.href.startsWith("http") ? "_blank" : undefined}
                rel={link.href.startsWith("http") ? "noopener noreferrer" : undefined}
                className="text-xs text-white/30 hover:text-white/60 transition-colors"
              >
                {link.label}
              </a>
            ))}
          </div>
        </div>

        {/* Company info + copyright */}
        <div className="border-t border-white/5 pt-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div className="flex flex-col sm:flex-row sm:items-center gap-3 text-xs text-white/25">
            <a href="https://rankwise.eu" target="_blank" rel="noopener noreferrer" className="hover:text-white/50 transition-colors font-medium text-white/35">
              Rankwise LTD
            </a>
            <span className="hidden sm:block text-white/10">·</span>
            <span>24–26 Arcadia Avenue, London, N3 2JU, United Kingdom</span>
            <span className="hidden sm:block text-white/10">·</span>
            <a href="mailto:info@rankwise.eu" className="hover:text-white/50 transition-colors">info@rankwise.eu</a>
            <span className="hidden sm:block text-white/10">·</span>
            <a href="https://wa.me/34664889152" target="_blank" rel="noopener noreferrer" className="hover:text-white/50 transition-colors">
              WhatsApp +34 664 889 152
            </a>
          </div>
          <p className="text-xs text-white/20">
            &copy; {new Date().getFullYear()} Rankwise LTD. All rights reserved.
          </p>
        </div>
      </div>
    </footer>
  );
}

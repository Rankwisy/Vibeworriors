"use client";

export default function Footer() {
  return (
    <footer className="border-t border-white/5 py-12 px-6">
      <div className="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-6">
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
    </footer>
  );
}

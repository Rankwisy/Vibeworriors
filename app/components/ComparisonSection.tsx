"use client";

import { motion, useInView } from "framer-motion";
import { useRef } from "react";

const rows = [
  { feature: "WordPress Functions", traditional: "Limited", vibe: "Full Access" },
  { feature: "Database", traditional: "Restricted", vibe: "Direct" },
  { feature: "Filesystem", traditional: "No", vibe: "Yes" },
  { feature: "Plugin Access", traditional: "Limited", vibe: "Full" },
  { feature: "Custom Code", traditional: "No", vibe: "Yes" },
  { feature: "AI Capability", traditional: "Partial", vibe: "Complete" },
];

export default function ComparisonSection() {
  const ref = useRef(null);
  const inView = useInView(ref, { once: true, margin: "-80px" });

  return (
    <section ref={ref} id="comparison" className="py-32 px-6 relative">
      <div className="absolute inset-0 pointer-events-none">
        <div className="absolute top-1/2 right-0 w-80 h-64 bg-blue-600/6 rounded-full blur-3xl" />
      </div>

      <div className="max-w-4xl mx-auto">
        <div className="text-center mb-16">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={inView ? { opacity: 1, y: 0 } : {}}
            className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-white/10 bg-white/5 text-white/50 text-xs font-medium mb-6"
          >
            Compare
          </motion.div>
          <motion.h2
            initial={{ opacity: 0, y: 30 }}
            animate={inView ? { opacity: 1, y: 0 } : {}}
            transition={{ delay: 0.1 }}
            className="text-4xl md:text-5xl font-bold tracking-tight"
          >
            No Competition
          </motion.h2>
        </div>

        <motion.div
          initial={{ opacity: 0, y: 30 }}
          animate={inView ? { opacity: 1, y: 0 } : {}}
          transition={{ delay: 0.2 }}
          className="glass-card rounded-2xl overflow-hidden"
        >
          {/* Header */}
          <div className="grid grid-cols-3 border-b border-white/5">
            <div className="p-5 text-sm font-medium text-white/40">Feature</div>
            <div className="p-5 text-sm font-medium text-white/40 border-l border-white/5">Traditional APIs</div>
            <div className="p-5 text-sm font-semibold text-violet-300 border-l border-violet-500/20 bg-violet-500/5 flex items-center gap-2">
              <span className="w-1.5 h-1.5 rounded-full bg-violet-400" />
              VibeWarrior
            </div>
          </div>

          {rows.map((row, i) => (
            <motion.div
              key={row.feature}
              initial={{ opacity: 0 }}
              animate={inView ? { opacity: 1 } : {}}
              transition={{ delay: 0.3 + i * 0.07 }}
              className={`grid grid-cols-3 ${i < rows.length - 1 ? "border-b border-white/5" : ""} hover:bg-white/[0.02] transition-colors`}
            >
              <div className="p-5 text-sm text-white/60">{row.feature}</div>
              <div className="p-5 border-l border-white/5 flex items-center gap-2">
                <span className="text-red-400 text-xs">✕</span>
                <span className="text-sm text-white/30">{row.traditional}</span>
              </div>
              <div className="p-5 border-l border-violet-500/10 bg-violet-500/[0.03] flex items-center gap-2">
                <span className="text-green-400 text-xs">✓</span>
                <span className="text-sm text-white/80 font-medium">{row.vibe}</span>
              </div>
            </motion.div>
          ))}
        </motion.div>
      </div>
    </section>
  );
}

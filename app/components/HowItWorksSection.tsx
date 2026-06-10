"use client";

import { motion, useInView } from "framer-motion";
import { useRef } from "react";

const steps = [
  { num: "01", title: "Install VibeWarrior", desc: "Upload and activate the plugin in your WordPress admin panel." },
  { num: "02", title: "Connect Your AI Agent", desc: "Point your AI agent to your WordPress endpoint." },
  { num: "03", title: "Authenticate via HTTPS", desc: "Set your secret key. All requests are verified on every call." },
  { num: "04", title: "Execute WordPress Operations", desc: "Your AI now calls WordPress functions, queries the DB, and edits files." },
  { num: "05", title: "Review Every Action", desc: "Keep human review in your workflow before approving critical operations." },
];

export default function HowItWorksSection() {
  const ref = useRef(null);
  const inView = useInView(ref, { once: true, margin: "-80px" });

  return (
    <section ref={ref} id="how-it-works" className="py-32 px-6 relative">
      <div className="max-w-4xl mx-auto">
        <div className="text-center mb-20">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={inView ? { opacity: 1, y: 0 } : {}}
            className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-white/10 bg-white/5 text-white/50 text-xs font-medium mb-6"
          >
            Setup
          </motion.div>
          <motion.h2
            initial={{ opacity: 0, y: 30 }}
            animate={inView ? { opacity: 1, y: 0 } : {}}
            transition={{ delay: 0.1 }}
            className="text-4xl md:text-5xl font-bold tracking-tight"
          >
            How It Works
          </motion.h2>
        </div>

        <div className="relative">
          {/* Vertical line */}
          <div className="absolute left-[22px] top-10 bottom-10 w-px bg-gradient-to-b from-violet-500/50 via-blue-500/30 to-transparent hidden md:block" />

          <div className="space-y-0">
            {steps.map((step, i) => (
              <motion.div
                key={step.num}
                initial={{ opacity: 0, x: -30 }}
                animate={inView ? { opacity: 1, x: 0 } : {}}
                transition={{ duration: 0.5, delay: 0.2 + i * 0.12 }}
                className="flex gap-6 group"
              >
                <div className="flex flex-col items-center flex-shrink-0">
                  <div className="w-11 h-11 rounded-full glass-card border border-violet-500/30 flex items-center justify-center z-10 group-hover:border-violet-400/60 transition-colors">
                    <span className="text-violet-400 text-xs font-bold">{step.num}</span>
                  </div>
                  {i < steps.length - 1 && <div className="w-px flex-1 bg-gradient-to-b from-violet-500/20 to-transparent mt-1 mb-1 min-h-[32px] md:hidden" />}
                </div>
                <div className={`pb-10 ${i < steps.length - 1 ? "border-b border-white/5" : ""} flex-1 pt-2`}>
                  <h3 className="font-semibold text-white mb-2">{step.title}</h3>
                  <p className="text-white/40 text-sm leading-relaxed">{step.desc}</p>
                </div>
              </motion.div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}

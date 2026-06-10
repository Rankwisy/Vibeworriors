"use client";

import { motion, useInView } from "framer-motion";
import { useRef } from "react";

const stackItems = [
  { label: "AI Agent", color: "bg-violet-500", glow: "shadow-violet-500/30" },
  { label: "VibeWarrior Plugin", color: "bg-blue-500", glow: "shadow-blue-500/30" },
  { label: "WordPress Core", color: "bg-indigo-500", glow: "shadow-indigo-500/30" },
  { label: "Database + Filesystem", color: "bg-slate-600", glow: "shadow-slate-500/20" },
];

export default function SolutionSection() {
  const ref = useRef(null);
  const inView = useInView(ref, { once: true, margin: "-100px" });

  return (
    <section ref={ref} id="features" className="py-32 px-6 relative">
      <div className="absolute inset-0 pointer-events-none">
        <div className="absolute top-1/2 right-0 w-80 h-80 bg-violet-600/8 rounded-full blur-3xl" />
      </div>

      <div className="max-w-7xl mx-auto">
        <div className="text-center mb-20">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={inView ? { opacity: 1, y: 0 } : {}}
            transition={{ duration: 0.6 }}
            className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-violet-500/30 bg-violet-500/10 text-violet-300 text-xs font-medium mb-6"
          >
            The Solution
          </motion.div>
          <motion.h2
            initial={{ opacity: 0, y: 30 }}
            animate={inView ? { opacity: 1, y: 0 } : {}}
            transition={{ duration: 0.7, delay: 0.1 }}
            className="text-4xl md:text-5xl font-bold tracking-tight"
          >
            Give AI <span className="gradient-text">Real WordPress Access</span>
          </motion.h2>
        </div>

        <div className="grid lg:grid-cols-2 gap-16 items-center">
          {/* Stack illustration */}
          <motion.div
            initial={{ opacity: 0, x: -40 }}
            animate={inView ? { opacity: 1, x: 0 } : {}}
            transition={{ duration: 0.8, delay: 0.2 }}
            className="flex flex-col items-center gap-0"
          >
            {stackItems.map((item, i) => (
              <div key={item.label} className="flex flex-col items-center w-full max-w-sm">
                <motion.div
                  initial={{ opacity: 0, scale: 0.9 }}
                  animate={inView ? { opacity: 1, scale: 1 } : {}}
                  transition={{ delay: 0.3 + i * 0.15 }}
                  className={`w-full glass-card rounded-xl px-6 py-4 flex items-center justify-between shadow-lg ${item.glow}`}
                >
                  <span className="text-white/80 font-medium text-sm">{item.label}</span>
                  <div className={`w-2.5 h-2.5 rounded-full ${item.color}`} />
                </motion.div>
                {i < stackItems.length - 1 && (
                  <motion.div
                    initial={{ opacity: 0, scaleY: 0 }}
                    animate={inView ? { opacity: 1, scaleY: 1 } : {}}
                    transition={{ delay: 0.45 + i * 0.15 }}
                    className="w-px h-8 bg-gradient-to-b from-violet-500/50 to-blue-500/30 origin-top"
                  />
                )}
              </div>
            ))}
          </motion.div>

          {/* Copy */}
          <motion.div
            initial={{ opacity: 0, x: 40 }}
            animate={inView ? { opacity: 1, x: 0 } : {}}
            transition={{ duration: 0.8, delay: 0.3 }}
          >
            <p className="text-lg text-white/60 leading-relaxed mb-6">
              The plugin executes PHP inside your WordPress environment. There
              is no abstraction layer.
            </p>
            <p className="text-lg text-white/60 leading-relaxed mb-10">
              Your AI calls WordPress functions directly, exactly like a
              developer writing code inside your application.
            </p>
            <div className="glass-card rounded-xl p-5 border border-violet-500/10">
              <p className="text-sm text-white/40 font-mono">
                <span className="text-violet-400">{"// "}</span>No wrappers. No REST endpoints. No abstraction.
              </p>
              <p className="text-sm text-white/40 font-mono mt-1">
                <span className="text-yellow-300">{"get_posts()"}</span>
                <span className="text-white/30">{" → runs natively inside WordPress"}</span>
              </p>
            </div>
          </motion.div>
        </div>
      </div>
    </section>
  );
}

"use client";

import { motion, useInView } from "framer-motion";
import { useRef } from "react";

export default function SandboxSection() {
  const ref = useRef(null);
  const inView = useInView(ref, { once: true, margin: "-100px" });

  const items = [
    "Files are placed in a sandbox folder",
    "Fatal crashes are detected",
    "Automatic recovery is available",
    "Experiments remain isolated",
  ];

  return (
    <section ref={ref} className="py-32 px-6 relative">
      <div className="absolute inset-0 pointer-events-none">
        <div className="absolute bottom-0 left-1/2 -translate-x-1/2 w-96 h-64 bg-violet-600/6 rounded-full blur-3xl" />
      </div>

      <div className="max-w-5xl mx-auto">
        <div className="grid lg:grid-cols-2 gap-12 items-start">
          <div>
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={inView ? { opacity: 1, y: 0 } : {}}
              className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-green-500/20 bg-green-500/8 text-green-400 text-xs font-medium mb-6"
            >
              Safety
            </motion.div>
            <motion.h2
              initial={{ opacity: 0, y: 30 }}
              animate={inView ? { opacity: 1, y: 0 } : {}}
              transition={{ delay: 0.1 }}
              className="text-4xl md:text-5xl font-bold tracking-tight mb-6"
            >
              Safe by <span className="gradient-text">Design</span>
            </motion.h2>
            <motion.p
              initial={{ opacity: 0 }}
              animate={inView ? { opacity: 1 } : {}}
              transition={{ delay: 0.2 }}
              className="text-white/50 mb-8 leading-relaxed"
            >
              When AI creates new PHP files:
            </motion.p>
            <ul className="space-y-3">
              {items.map((item, i) => (
                <motion.li
                  key={item}
                  initial={{ opacity: 0, x: -20 }}
                  animate={inView ? { opacity: 1, x: 0 } : {}}
                  transition={{ delay: 0.3 + i * 0.1 }}
                  className="flex items-center gap-3 text-white/60 text-sm"
                >
                  <span className="w-5 h-5 rounded-full bg-green-500/15 border border-green-500/30 flex items-center justify-center flex-shrink-0">
                    <span className="text-green-400 text-xs">✓</span>
                  </span>
                  {item}
                </motion.li>
              ))}
            </ul>
          </div>

          <motion.div
            initial={{ opacity: 0, x: 40 }}
            animate={inView ? { opacity: 1, x: 0 } : {}}
            transition={{ delay: 0.3 }}
          >
            <div className="rounded-2xl border border-amber-500/20 bg-amber-500/5 p-6">
              <div className="flex items-start gap-3 mb-4">
                <span className="text-amber-400 text-xl">⚠️</span>
                <div>
                  <h4 className="font-semibold text-amber-300 text-sm mb-1">Important Notice</h4>
                  <p className="text-amber-200/60 text-sm leading-relaxed">
                    Direct PHP execution bypasses the sandbox. Any executed code
                    can do anything PHP can do.
                  </p>
                </div>
              </div>
              <div className="border-t border-amber-500/10 pt-4">
                <p className="text-amber-200/50 text-xs font-medium uppercase tracking-wider mb-2">Recommended for</p>
                <div className="flex gap-2 flex-wrap">
                  {["Development", "Staging"].map((env) => (
                    <span
                      key={env}
                      className="px-2.5 py-1 rounded-md bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs"
                    >
                      {env}
                    </span>
                  ))}
                  <span className="px-2.5 py-1 rounded-md bg-red-500/10 border border-red-500/20 text-red-400 text-xs line-through opacity-50">
                    Production
                  </span>
                </div>
              </div>
            </div>
          </motion.div>
        </div>
      </div>
    </section>
  );
}

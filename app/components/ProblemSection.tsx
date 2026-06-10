"use client";

import { motion } from "framer-motion";
import { useInView } from "framer-motion";
import { useRef } from "react";

const limitations = [
  "Using WordPress functions",
  "Accessing plugins",
  "Working with custom code",
  "Performing advanced operations",
];

export default function ProblemSection() {
  const ref = useRef(null);
  const inView = useInView(ref, { once: true, margin: "-100px" });

  return (
    <section ref={ref} className="py-32 px-6 relative">
      <div className="absolute inset-0 pointer-events-none">
        <div className="absolute top-1/2 left-0 w-64 h-64 bg-red-600/5 rounded-full blur-3xl" />
      </div>

      <div className="max-w-4xl mx-auto text-center">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={inView ? { opacity: 1, y: 0 } : {}}
          transition={{ duration: 0.6 }}
          className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-red-500/20 bg-red-500/8 text-red-400 text-xs font-medium mb-6"
        >
          The Problem
        </motion.div>

        <motion.h2
          initial={{ opacity: 0, y: 30 }}
          animate={inView ? { opacity: 1, y: 0 } : {}}
          transition={{ duration: 0.7, delay: 0.1 }}
          className="text-4xl md:text-5xl font-bold tracking-tight mb-6"
        >
          WordPress APIs{" "}
          <span className="text-white/30">Limit AI</span>
        </motion.h2>

        <motion.p
          initial={{ opacity: 0, y: 20 }}
          animate={inView ? { opacity: 1, y: 0 } : {}}
          transition={{ duration: 0.6, delay: 0.2 }}
          className="text-lg text-white/50 mb-12 max-w-2xl mx-auto leading-relaxed"
        >
          Most AI integrations only expose a small set of endpoints. This
          creates an abstraction layer that prevents AI from:
        </motion.p>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={inView ? { opacity: 1, y: 0 } : {}}
          transition={{ duration: 0.6, delay: 0.3 }}
          className="glass-card rounded-2xl p-8 mb-8 max-w-xl mx-auto"
        >
          <ul className="space-y-3 text-left">
            {limitations.map((item, i) => (
              <motion.li
                key={item}
                initial={{ opacity: 0, x: -20 }}
                animate={inView ? { opacity: 1, x: 0 } : {}}
                transition={{ delay: 0.4 + i * 0.1 }}
                className="flex items-center gap-3 text-white/60"
              >
                <span className="w-5 h-5 rounded-full border border-red-500/30 bg-red-500/10 flex items-center justify-center flex-shrink-0">
                  <span className="text-red-400 text-xs">✕</span>
                </span>
                {item}
              </motion.li>
            ))}
          </ul>
        </motion.div>

        <motion.p
          initial={{ opacity: 0 }}
          animate={inView ? { opacity: 1 } : {}}
          transition={{ delay: 0.8 }}
          className="text-white/30 text-sm"
        >
          As a result, AI agents remain limited.
        </motion.p>
      </div>
    </section>
  );
}

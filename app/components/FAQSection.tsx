"use client";

import { motion, useInView, AnimatePresence } from "framer-motion";
import { useRef, useState } from "react";

const faqs = [
  {
    q: "Is this safe for production?",
    a: "VibeWarrior is intended for development and staging environments. Direct code execution can perform any action available to PHP. Use appropriate caution and always review AI actions before approving them.",
  },
  {
    q: "Does it work with OpenAI?",
    a: "Yes. Any AI model capable of tool use can connect—OpenAI, Claude, Gemini, or any self-hosted model that supports function calling.",
  },
  {
    q: "Can it access plugins?",
    a: "Yes. The AI runs inside WordPress and can interact with all installed plugins, call their functions, and access their data.",
  },
  {
    q: "Can I review actions before they execute?",
    a: "Yes. Human review should remain part of your workflow. You can build an approval step between the AI's planned action and its execution.",
  },
];

function FAQItem({ q, a, i, inView }: { q: string; a: string; i: number; inView: boolean }) {
  const [open, setOpen] = useState(false);

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={inView ? { opacity: 1, y: 0 } : {}}
      transition={{ delay: 0.2 + i * 0.08 }}
      className="glass-card rounded-2xl overflow-hidden"
    >
      <button
        onClick={() => setOpen((o) => !o)}
        className="w-full flex items-center justify-between p-6 text-left hover:bg-white/[0.02] transition-colors"
      >
        <span className="font-medium text-white text-sm pr-6">{q}</span>
        <motion.span
          animate={{ rotate: open ? 45 : 0 }}
          transition={{ duration: 0.2 }}
          className="text-white/30 text-xl flex-shrink-0"
        >
          +
        </motion.span>
      </button>
      <AnimatePresence>
        {open && (
          <motion.div
            initial={{ height: 0, opacity: 0 }}
            animate={{ height: "auto", opacity: 1 }}
            exit={{ height: 0, opacity: 0 }}
            transition={{ duration: 0.25 }}
            className="overflow-hidden"
          >
            <p className="px-6 pb-6 text-white/40 text-sm leading-relaxed border-t border-white/5 pt-4">{a}</p>
          </motion.div>
        )}
      </AnimatePresence>
    </motion.div>
  );
}

export default function FAQSection() {
  const ref = useRef(null);
  const inView = useInView(ref, { once: true, margin: "-80px" });

  return (
    <section ref={ref} className="py-32 px-6">
      <div className="max-w-2xl mx-auto">
        <div className="text-center mb-16">
          <motion.h2
            initial={{ opacity: 0, y: 30 }}
            animate={inView ? { opacity: 1, y: 0 } : {}}
            className="text-4xl md:text-5xl font-bold tracking-tight"
          >
            FAQ
          </motion.h2>
        </div>
        <div className="space-y-3">
          {faqs.map((faq, i) => (
            <FAQItem key={faq.q} q={faq.q} a={faq.a} i={i} inView={inView} />
          ))}
        </div>
      </div>
    </section>
  );
}

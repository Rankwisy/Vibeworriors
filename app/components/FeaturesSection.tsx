"use client";

import { motion, useInView } from "framer-motion";
import { useRef } from "react";

const features = [
  {
    icon: "⚡",
    title: "Native PHP Execution",
    desc: "Run PHP directly inside WordPress. Full access to the runtime environment.",
  },
  {
    icon: "🔧",
    title: "Full WordPress Function Access",
    desc: "Call any WordPress function. get_posts(), update_option(), wp_insert_post()—all available.",
  },
  {
    icon: "🗄️",
    title: "Database Access",
    desc: "Read and modify content, settings, metadata and custom tables via $wpdb.",
  },
  {
    icon: "📁",
    title: "Filesystem Control",
    desc: "Inspect, create and update files directly in the WordPress installation.",
  },
  {
    icon: "🔐",
    title: "Secure HTTPS Authentication",
    desc: "Every request is authenticated via a secret key. No anonymous access.",
  },
  {
    icon: "🛡️",
    title: "Sandbox Protection",
    desc: "New PHP files are placed in an isolated sandbox. Fatal crashes are detected and recovery is automatic.",
  },
  {
    icon: "🤖",
    title: "Bring Your Own Model",
    desc: "Works with OpenAI, Claude, Gemini and self-hosted models. Any model with tool use support.",
  },
  {
    icon: "🔓",
    title: "No Vendor Lock-In",
    desc: "The plugin is lightweight and open source. Install it, use it, fork it.",
  },
];

export default function FeaturesSection() {
  const ref = useRef(null);
  const inView = useInView(ref, { once: true, margin: "-80px" });

  return (
    <section ref={ref} id="features" className="py-32 px-6 relative">
      <div className="absolute inset-0 pointer-events-none">
        <div className="absolute top-0 left-1/2 -translate-x-1/2 w-px h-32 bg-gradient-to-b from-transparent to-violet-500/20" />
      </div>

      <div className="max-w-7xl mx-auto">
        <div className="text-center mb-16">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={inView ? { opacity: 1, y: 0 } : {}}
            transition={{ duration: 0.6 }}
            className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-white/10 bg-white/5 text-white/50 text-xs font-medium mb-6"
          >
            Capabilities
          </motion.div>
          <motion.h2
            initial={{ opacity: 0, y: 30 }}
            animate={inView ? { opacity: 1, y: 0 } : {}}
            transition={{ duration: 0.7, delay: 0.1 }}
            className="text-4xl md:text-5xl font-bold tracking-tight"
          >
            Everything Your AI Needs
          </motion.h2>
        </div>

        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {features.map((feature, i) => (
            <motion.div
              key={feature.title}
              initial={{ opacity: 0, y: 30 }}
              animate={inView ? { opacity: 1, y: 0 } : {}}
              transition={{ duration: 0.5, delay: i * 0.07 }}
              whileHover={{ y: -4 }}
              className="glass-card glass-card-hover rounded-2xl p-6 cursor-default transition-all duration-300"
            >
              <div className="text-2xl mb-4">{feature.icon}</div>
              <h3 className="font-semibold text-white text-sm mb-2">{feature.title}</h3>
              <p className="text-white/40 text-sm leading-relaxed">{feature.desc}</p>
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}

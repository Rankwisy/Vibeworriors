import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import "./globals.css";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "VibeWarrior — Direct WordPress Access for AI Agents",
  description:
    "Run PHP inside WordPress and give AI agents direct access to WordPress functions, the database, and the filesystem. Secure, authenticated, and designed for development environments.",
  keywords:
    "wordpress ai, wordpress agent, ai wordpress plugin, php execution wordpress, wordpress automation, ai developer tools, wordpress mcp, wordpress ai integration",
  openGraph: {
    title: "VibeWarrior — Direct WordPress Access for AI Agents",
    description:
      "Run PHP inside WordPress and give AI agents direct access to WordPress functions, the database, and the filesystem.",
    url: "https://vibewarriors.netlify.app",
    siteName: "VibeWarrior",
    type: "website",
  },
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html
      lang="en"
      className={`${geistSans.variable} ${geistMono.variable} h-full antialiased`}
    >
      <body className="min-h-full flex flex-col bg-black text-white">{children}</body>
    </html>
  );
}

import Navbar from "./components/Navbar";
import HeroSection from "./components/HeroSection";
import ProblemSection from "./components/ProblemSection";
import SolutionSection from "./components/SolutionSection";
import FeaturesSection from "./components/FeaturesSection";
import SandboxSection from "./components/SandboxSection";
import HowItWorksSection from "./components/HowItWorksSection";
import ComparisonSection from "./components/ComparisonSection";
import GitHubSection from "./components/GitHubSection";
import FAQSection from "./components/FAQSection";
import CTASection from "./components/CTASection";
import Footer from "./components/Footer";

export default function Home() {
  return (
    <main className="bg-black min-h-screen">
      <Navbar />
      <HeroSection />
      <ProblemSection />
      <SolutionSection />
      <FeaturesSection />
      <SandboxSection />
      <HowItWorksSection />
      <ComparisonSection />
      <GitHubSection />
      <FAQSection />
      <CTASection />
      <Footer />
    </main>
  );
}

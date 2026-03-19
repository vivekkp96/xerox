<template>
    <div class="landing-page">
        <!-- Navigation -->
        <nav class="navbar">
            <div class="container nav-content">
                <div class="logo">
                    <span class="logo-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                            <path fill-rule="evenodd" d="M7.5 3.75A1.5 1.5 0 006 5.25v13.5a1.5 1.5 0 001.5 1.5h6a1.5 1.5 0 001.5-1.5V15a.75.75 0 011.5 0v3.75a3 3 0 01-3 3h-6a3 3 0 01-3-3V5.25a3 3 0 013-3h6a3 3 0 013 3V9A.75.75 0 0115 9V5.25a1.5 1.5 0 00-1.5-1.5h-6zm10.72 4.72a.75.75 0 011.06 0l3 3a.75.75 0 010 1.06l-3 3a.75.75 0 11-1.06-1.06l1.72-1.72H9a.75.75 0 010-1.5h10.94l-1.72-1.72a.75.75 0 010-1.06z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    Xerox
                </div>
                <div class="nav-links">
                    <router-link to="/login" class="nav-link">Login</router-link>
                    <router-link to="/register" class="btn btn-primary btn-sm">Sign Up</router-link>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <header class="hero">
            <div class="container hero-container">
                <div class="hero-content">
                    <h1 class="hero-title">
                        Professional Printing, <br>
                        <span class="text-highlight">Made Simple.</span>
                    </h1>
                    <p class="hero-description">
                        Upload your documents, customize your preferences, and get high-quality prints delivered to your door. Fast, reliable, and affordable.
                    </p>
                    <div class="hero-buttons">
                        <router-link to="/order" class="btn btn-primary btn-lg">Start Printing</router-link>
                        <a href="#features" class="btn btn-outline btn-lg">Learn More</a>
                    </div>
                </div>
                <div class="hero-image-wrapper">
                    <div class="hero-card">
                        <div class="card-header">
                            <div class="dot red"></div>
                            <div class="dot yellow"></div>
                            <div class="dot green"></div>
                        </div>
                        <div class="card-body">
                            <div class="skeleton-line w-75"></div>
                            <div class="skeleton-line w-50"></div>
                            <div class="skeleton-rect"></div>
                            <div class="skeleton-row">
                                <div class="skeleton-box"></div>
                                <div class="skeleton-box"></div>
                            </div>
                        </div>
                    </div>
                    <div class="floating-badge">
                        <span class="badge-icon">✓</span>
                        <span>High Quality</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Features Section -->
        <section id="features" class="features-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Why Choose Xerox?</h2>
                    <p class="section-subtitle">We handle your documents with care and precision.</p>
                </div>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                            </svg>
                        </div>
                        <h3>Easy Upload</h3>
                        <p>Drag and drop your PDF or Word documents. We support multiple files and formats.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                            </svg>
                        </div>
                        <h3>Custom Options</h3>
                        <p>Select paper size, color mode, and specific pages. Get exactly what you need.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3>Fast Turnaround</h3>
                        <p>We process orders quickly. Track your order status from dashboard to delivery.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <div class="cta-box">
                    <h2>Ready to get started?</h2>
                    <p>Create an account today and get your first order processed immediately.</p>
                    <router-link to="/register" class="btn btn-white btn-lg">Create Free Account</router-link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <div class="container footer-content">
                <div class="footer-brand">
                    <span class="footer-logo">Xerox</span>
                    <p>Your trusted partner for all printing needs.</p>
                </div>
                <div class="footer-links">
                    <div class="link-group">
                        <h4>Service</h4>
                        <router-link to="/order">Order</router-link>
                        <a href="#">Pricing</a>
                        <a href="#">FAQ</a>
                    </div>
                    <div class="link-group">
                        <h4>Company</h4>
                        <a href="#">About Us</a>
                        <a href="#">Contact</a>
                        <a href="#">Terms</a>
                    </div>
                </div>
            </div>
            <div class="container copyright">
                &copy; {{ new Date().getFullYear() }} Xerox. All rights reserved.
            </div>
        </footer>
    </div>
</template>

<script>
export default {
    name: 'LandingPage'
}
</script>

<style scoped>
/* Clean & Modern Styles */
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');

/* Variables */
:root {
    --primary-color: #8b5cf6;
    --primary-dark: #7c3aed;
    --text-main: #334155;
    --text-light: #64748b;
    --bg-light: #f8fafc;
    --bg-white: #ffffff;
}

.landing-page {
    font-family: 'Outfit', sans-serif;
    color: var(--text-main);
    background-color: var(--bg-light);
    overflow-x: hidden;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

/* Navbar */
.navbar {
    background-color: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid #e5e7eb;
    position: sticky;
    top: 0;
    z-index: 100;
    padding: 1rem 0;
}

.nav-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    font-size: 1.5rem;
    font-weight: 800;
    color: #8b5cf6;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.logo-icon {
    width: 24px;
    height: 24px;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.nav-link {
    color: var(--text-light);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.95rem;
    transition: color 0.2s;
}

.nav-link:hover {
    color: #8b5cf6;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    cursor: pointer;
    border: 1px solid transparent;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.btn-lg {
    padding: 0.875rem 2rem;
    font-size: 1.125rem;
}

.btn-primary {
    background-color: #8b5cf6;
    color: white;
    box-shadow: 0 4px 6px -1px rgba(139, 92, 246, 0.2);
}

.btn-primary:hover {
    background-color: #7c3aed;
    transform: translateY(-1px);
    box-shadow: 0 6px 8px -1px rgba(139, 92, 246, 0.3);
}

.btn-outline {
    background-color: transparent;
    border-color: #d1d5db;
    color: #374151;
}

.btn-outline:hover {
    border-color: #9ca3af;
    background-color: #f3f4f6;
    color: #111827;
}

.btn-white {
    background-color: white;
    color: #8b5cf6;
}

.btn-white:hover {
    background-color: #f8fafc;
}

/* Hero Section */
.hero {
    padding: 5rem 0;
    background: radial-gradient(circle at top right, #f5f3ff 0%, transparent 40%),
                radial-gradient(circle at bottom left, #f5f3ff 0%, transparent 40%);
}

.hero-container {
    display: grid;
    grid-template-columns: 1fr;
    gap: 4rem;
    align-items: center;
}

@media (min-width: 992px) {
    .hero-container {
        grid-template-columns: 1fr 1fr;
    }
}

.hero-title {
    font-size: 2.5rem;
    font-weight: 800;
    line-height: 1.1;
    color: #1e293b;
    margin-bottom: 1.5rem;
    letter-spacing: -0.02em;
}

@media (min-width: 768px) {
    .hero-title {
        font-size: 3.5rem;
    }
}

.text-highlight {
    color: #8b5cf6;
    background: linear-gradient(120deg, rgba(139, 92, 246, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
    background-repeat: no-repeat;
    background-size: 100% 0.4em;
    background-position: 0 88%;
}

.hero-description {
    font-size: 1.125rem;
    color: var(--text-light);
    margin-bottom: 2.5rem;
    line-height: 1.6;
    max-width: 34rem;
}

.hero-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

/* Hero Image / Illustration */
.hero-image-wrapper {
    position: relative;
    display: flex;
    justify-content: center;
}

.hero-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    width: 100%;
    max-width: 400px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    transform: rotate(-2deg);
    transition: transform 0.3s ease;
}

.hero-card:hover {
    transform: rotate(0deg) scale(1.02);
}

.card-header {
    background: #f1f5f9;
    padding: 0.75rem 1rem;
    display: flex;
    gap: 0.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.dot {
    width: 0.75rem;
    height: 0.75rem;
    border-radius: 50%;
}

.red { background-color: #ef4444; }
.yellow { background-color: #f59e0b; }
.green { background-color: #10b981; }

.card-body {
    padding: 2rem;
}

.skeleton-line {
    height: 1rem;
    background-color: #e2e8f0;
    border-radius: 0.25rem;
    margin-bottom: 1rem;
}

.w-75 { width: 75%; }
.w-50 { width: 50%; }

.skeleton-rect {
    height: 8rem;
    background-color: #f1f5f9;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
    border: 2px dashed #d1d5db;
}

.skeleton-row {
    display: flex;
    gap: 1rem;
}

.skeleton-box {
    height: 2rem;
    width: 4rem;
    background-color: #e2e8f0;
    border-radius: 0.25rem;
}

.floating-badge {
    position: absolute;
    bottom: -1rem;
    right: 1rem;
    background: white;
    padding: 0.75rem 1.25rem;
    border-radius: 9999px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #059669;
    border: 1px solid #e5e7eb;
    animation: float 3s ease-in-out infinite;
}

.badge-icon {
    background: #d1fae5;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

/* Features Section */
.features-section {
    padding: 6rem 0;
    background-color: white;
}

.section-header {
    text-align: center;
    max-width: 48rem;
    margin: 0 auto 4rem;
}

.section-title {
    font-size: 2.25rem;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 1rem;
}

.section-subtitle {
    font-size: 1.125rem;
    color: var(--text-light);
}

.features-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
}

@media (min-width: 768px) {
    .features-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.feature-card {
    padding: 2rem;
    border-radius: 1rem;
    background-color: #f8fafc;
    border: 1px solid #f3f4f6;
    transition: all 0.3s ease;
}

.feature-card:hover {
    background-color: white;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    transform: translateY(-5px);
    border-color: #e5e7eb;
}

.feature-icon {
    width: 3.5rem;
    height: 3.5rem;
    background-color: #f5f3ff;
    color: #8b5cf6;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
}

.feature-icon svg {
    width: 1.75rem;
    height: 1.75rem;
}

.feature-card h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.75rem;
}

.feature-card p {
    color: var(--text-light);
    line-height: 1.6;
}

/* CTA Section */
.cta-section {
    padding: 6rem 0;
}

.cta-box {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    border-radius: 1.5rem;
    padding: 4rem 2rem;
    text-align: center;
    color: white;
    box-shadow: 0 20px 25px -5px rgba(139, 92, 246, 0.3);
}

.cta-box h2 {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 1rem;
}

.cta-box p {
    font-size: 1.25rem;
    color: #ede9fe;
    margin-bottom: 2.5rem;
    max-width: 36rem;
    margin-left: auto;
    margin-right: auto;
}

/* Footer */
.footer {
    background-color: #1e293b;
    color: #d1d5db;
    padding: 4rem 0 2rem;
}

.footer-content {
    display: grid;
    grid-template-columns: 1fr;
    gap: 3rem;
    margin-bottom: 3rem;
}

@media (min-width: 768px) {
    .footer-content {
        grid-template-columns: 1fr 2fr;
    }
}

.footer-brand p {
    margin-top: 1rem;
    color: #9ca3af;
}

.footer-logo {
    font-size: 1.5rem;
    font-weight: 800;
    color: white;
}

.footer-links {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
}

@media (min-width: 640px) {
    .footer-links {
        grid-template-columns: repeat(3, 1fr);
    }
}

.link-group h4 {
    color: white;
    font-weight: 600;
    margin-bottom: 1.25rem;
}

.link-group a {
    display: block;
    color: #9ca3af;
    text-decoration: none;
    margin-bottom: 0.75rem;
    transition: color 0.2s;
}

.link-group a:hover {
    color: white;
}

.copyright {
    border-top: 1px solid #374151;
    padding-top: 2rem;
    text-align: center;
    color: #94a3b8;
    font-size: 0.875rem;
}
</style>
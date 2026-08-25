<?php
/**
 * Template Name: NDIS Blog & Knowledge Hub
 * Description: Modern, dynamic blog hub supporting standard WordPress posts and comprehensive NDIS pillar guides.
 */

get_header();

// 1. Fetch dynamic WordPress posts
$paged = (get_query_var('paged')) ? get_query_var('paged') : ((get_query_var('page')) ? get_query_var('page') : 1);
$dynamic_posts_query = new WP_Query(array(
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => 'DESC'
));
?>

<!-- SCHEMA STRUCTURED DATA FOR BLOG & SEO -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Blog",
      "@id": "https://www.supportfoundation.com.au/blog/#blog",
      "url": "https://www.supportfoundation.com.au/blog/",
      "name": "Support Foundation NDIS Knowledge Hub & Blog",
      "description": "Comprehensive guides and updates on Supported Independent Living (SIL), 24/7 crisis support, support coordination, and disability care across Australia.",
      "publisher": {
        "@type": "Organization",
        "name": "Support Foundation Australia",
        "url": "https://www.supportfoundation.com.au/",
        "logo": {
          "@type": "ImageObject",
          "url": "https://www.supportfoundation.com.au/wp-content/uploads/2024/02/cropped-support-foundation-logo.png"
        }
      }
    },
    {
      "@type": "FAQPage",
      "@id": "https://www.supportfoundation.com.au/blog/#faq",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "What is mandatory SIL registration for NDIS providers in 2026?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "From 1 July 2026, all Supported Independent Living (SIL) providers must be registered with the NDIS Quality and Safeguards Commission, complying with four new SIL-specific Practice Standards covering Supported Decision-Making, Safeguarding, Practice Governance, and Separate Tenancy and Support Agreements."
          }
        },
        {
          "@type": "Question",
          "name": "Why choose a Registered NDIS Service Provider in Australia?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Registered NDIS providers comply with audited federal quality standards, undergo mandatory worker screening, maintain strict incident management, and can support Agency-Managed, Plan-Managed, and Self-Managed participants."
          }
        },
        {
          "@type": "Question",
          "name": "How does Support Foundation provide 24/7 emergency crisis support?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Support Foundation operates an emergency 24/7 hotline (02 8386 1433) providing rapid response for care breakdowns, crisis respite, domestic violence safety planning, and immediate Short-Term Accommodation (STA) placement."
          }
        }
      ]
    }
  ]
}
</script>

<style>
/* ==========================================================================
   SUPPORT FOUNDATION MODERN BLOG & KNOWLEDGE HUB STYLES
   ========================================================================== */
:root {
    --sf-emerald: #047857;
    --sf-emerald-dark: #064e3b;
    --sf-emerald-light: #10b981;
    --sf-emerald-bg: #ecfdf5;
    --sf-mint-border: #a7f3d0;
    --sf-slate-900: #0f172a;
    --sf-slate-800: #1e293b;
    --sf-slate-700: #334155;
    --sf-slate-600: #475569;
    --sf-slate-500: #64748b;
    --sf-slate-200: #e2e8f0;
    --sf-slate-100: #f1f5f9;
    --sf-slate-50: #f8fafc;
    --sf-purple: #7a00df;
    --sf-purple-bg: #f5f3ff;
}

.sf-blog-page-wrap {
    background-color: var(--sf-slate-50);
    color: var(--sf-slate-700);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    min-height: 100vh;
}

/* --- HERO SECTION --- */
.sf-blog-hero {
    background: linear-gradient(135deg, #022c22 0%, #064e3b 40%, #047857 85%, #059669 100%);
    color: #ffffff;
    padding: 5rem 1.5rem 4rem;
    position: relative;
    overflow: hidden;
    text-align: center;
}

.sf-blog-hero::before {
    content: '';
    position: absolute;
    top: -30%;
    right: -10%;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(16, 185, 129, 0.25) 0%, rgba(4, 120, 87, 0) 70%);
    border-radius: 50%;
    pointer-events: none;
}

.sf-blog-hero::after {
    content: '';
    position: absolute;
    bottom: -20%;
    left: -10%;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(122, 0, 223, 0.15) 0%, rgba(4, 120, 87, 0) 70%);
    border-radius: 50%;
    pointer-events: none;
}

.sf-blog-hero-container {
    max-width: 960px;
    margin: 0 auto;
    position: relative;
    z-index: 2;
}

.sf-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    color: #a7f3d0;
    font-size: 0.85rem;
    font-weight: 700;
    padding: 6px 18px;
    border-radius: 50px;
    margin-bottom: 1.25rem;
    text-transform: uppercase;
    letter-spacing: 0.75px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.sf-hero-title {
    font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
    font-size: clamp(2.2rem, 5vw, 3.4rem);
    font-weight: 800;
    line-height: 1.15;
    margin-bottom: 1.25rem;
    color: #ffffff;
    letter-spacing: -0.5px;
}

.sf-hero-subtitle {
    font-size: clamp(1.05rem, 2vw, 1.25rem);
    line-height: 1.65;
    color: #d1fae5;
    max-width: 820px;
    margin: 0 auto 2.25rem;
    font-weight: 400;
}

/* Search Box */
.sf-hero-search-wrapper {
    max-width: 680px;
    margin: 0 auto 2rem;
    position: relative;
    display: flex;
    align-items: center;
}

.sf-search-input-field {
    width: 100%;
    padding: 16px 24px 16px 64px !important;
    font-size: 1.05rem;
    border-radius: 50px;
    border: 2px solid rgba(255, 255, 255, 0.4);
    background: #ffffff;
    color: var(--sf-slate-900);
    box-shadow: 0 12px 35px -5px rgba(0, 0, 0, 0.25);
    outline: none;
    transition: all 0.25s ease;
    box-sizing: border-box;
}

.sf-search-input-field:focus {
    border-color: #34d399;
    background: #ffffff;
    box-shadow: 0 16px 40px -5px rgba(16, 185, 129, 0.4);
}

.sf-search-icon-svg {
    position: absolute;
    left: 24px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--sf-emerald);
    pointer-events: none;
    z-index: 10;
    width: 22px;
    height: 22px;
}

/* Category Filter Pills */
.sf-filter-nav {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
    margin-top: 1rem;
}

.sf-filter-btn {
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.25);
    color: #ffffff;
    padding: 8px 18px;
    border-radius: 30px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
}

.sf-filter-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-1px);
    color: #ffffff;
}

.sf-filter-btn.active {
    background: #ffffff;
    color: var(--sf-emerald-dark);
    border-color: #ffffff;
    font-weight: 700;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
}

/* --- MAIN LAYOUT --- */
.sf-blog-main-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 3.5rem 1.5rem 5rem;
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 3rem;
    box-sizing: border-box;
}

@media (max-width: 1024px) {
    .sf-blog-main-container {
        grid-template-columns: 1fr;
        gap: 2.5rem;
    }
}

/* --- FEATURED SPOTLIGHT CARD --- */
.sf-featured-spotlight {
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid var(--sf-slate-200);
    padding: 2.5rem;
    margin-bottom: 3.5rem;
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05);
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    border-left: 6px solid var(--sf-emerald);
}

.sf-featured-spotlight:hover {
    box-shadow: 0 20px 35px -10px rgba(4, 120, 87, 0.12);
    transform: translateY(-2px);
}

.sf-featured-tag-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 1.25rem;
}

.sf-badge-featured {
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
    color: var(--sf-emerald-dark);
    font-weight: 700;
    font-size: 0.8rem;
    padding: 6px 14px;
    border-radius: 50px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 1px solid var(--sf-mint-border);
}

.sf-read-time-badge {
    color: var(--sf-slate-500);
    font-size: 0.85rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
}

.sf-spotlight-title {
    font-family: 'Outfit', sans-serif;
    font-size: clamp(1.6rem, 3vw, 2.25rem);
    font-weight: 800;
    color: var(--sf-slate-900);
    line-height: 1.25;
    margin-bottom: 1rem;
}

.sf-spotlight-excerpt {
    font-size: 1.05rem;
    line-height: 1.75;
    color: var(--sf-slate-700);
    margin-bottom: 1.75rem;
}

.sf-spotlight-key-points {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
    margin-bottom: 2rem;
}

.sf-key-point-item {
    background: var(--sf-slate-50);
    border: 1px solid var(--sf-slate-200);
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--sf-slate-800);
    display: flex;
    align-items: center;
    gap: 10px;
}

.sf-key-point-item svg {
    color: var(--sf-emerald);
    flex-shrink: 0;
}

.sf-spotlight-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    padding-top: 1.5rem;
    border-top: 1px solid var(--sf-slate-100);
}

.sf-author-meta {
    display: flex;
    align-items: center;
    gap: 12px;
}

.sf-avatar-circle {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--sf-emerald);
    color: #ffffff;
    font-weight: 800;
    font-size: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(4, 120, 87, 0.25);
}

.sf-author-info strong {
    display: block;
    color: var(--sf-slate-900);
    font-size: 0.95rem;
}

.sf-author-info span {
    color: var(--sf-slate-500);
    font-size: 0.8rem;
}

.sf-btn-primary {
    background: var(--sf-emerald);
    color: #ffffff;
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.95rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
}

.sf-btn-primary:hover {
    background: var(--sf-emerald-dark);
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(4, 120, 87, 0.25);
}

/* --- SECTION HEADINGS --- */
.sf-section-header-wrap {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
}

.sf-section-heading {
    font-family: 'Outfit', sans-serif;
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--sf-slate-900);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.sf-post-count-badge {
    background: var(--sf-emerald-bg);
    color: var(--sf-emerald-dark);
    font-size: 0.85rem;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 20px;
    border: 1px solid var(--sf-mint-border);
}

/* --- BLOG CARDS GRID --- */
.sf-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2rem;
    margin-bottom: 3.5rem;
}

@media (max-width: 640px) {
    .sf-cards-grid {
        grid-template-columns: 1fr;
    }
}

.sf-blog-card {
    background: #ffffff;
    border: 1px solid var(--sf-slate-200);
    border-radius: 18px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.sf-blog-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 16px 30px -5px rgba(0, 0, 0, 0.08);
    border-color: var(--sf-mint-border);
}

.sf-card-thumbnail-wrap {
    height: 180px;
    background: linear-gradient(135deg, #064e3b 0%, #047857 100%);
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sf-card-thumbnail-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.sf-blog-card:hover .sf-card-thumbnail-wrap img {
    transform: scale(1.05);
}

.sf-card-illustration-overlay {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at center, rgba(255,255,255,0.15) 0%, rgba(0,0,0,0.15) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255, 255, 255, 0.9);
}

.sf-card-body {
    padding: 1.75rem;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.sf-card-category-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.85rem;
}

.sf-card-tag {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 4px 10px;
    border-radius: 6px;
    background: var(--sf-emerald-bg);
    color: var(--sf-emerald-dark);
}

.sf-card-date {
    font-size: 0.8rem;
    color: var(--sf-slate-500);
}

.sf-card-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1.35;
    color: var(--sf-slate-900);
    margin-bottom: 0.85rem;
    transition: color 0.2s ease;
}

.sf-card-title a {
    color: inherit;
    text-decoration: none;
}

.sf-card-title a:hover, .sf-blog-card:hover .sf-card-title {
    color: var(--sf-emerald);
}

.sf-card-excerpt {
    font-size: 0.925rem;
    line-height: 1.65;
    color: var(--sf-slate-600);
    margin-bottom: 1.5rem;
    flex-grow: 1;
}

.sf-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 1rem;
    border-top: 1px solid var(--sf-slate-100);
    margin-top: auto;
}

.sf-card-read-link {
    color: var(--sf-emerald);
    font-weight: 700;
    font-size: 0.9rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: gap 0.2s ease;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
}

.sf-card-read-link:hover {
    color: var(--sf-emerald-dark);
    gap: 8px;
}

/* --- ARTICLE READER MODAL (FOR FULL PILLAR GUIDES) --- */
.sf-reader-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 999999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    opacity: 0;
    transition: opacity 0.25s ease;
}

.sf-reader-modal-overlay.active {
    display: flex;
    opacity: 1;
}

.sf-reader-modal-container {
    background: #ffffff;
    border-radius: 20px;
    max-width: 900px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.4);
    position: relative;
    padding: 3rem 2.5rem;
    box-sizing: border-box;
}

@media (max-width: 640px) {
    .sf-reader-modal-container {
        padding: 2rem 1.25rem;
        max-height: 95vh;
    }
}

.sf-modal-close-btn {
    position: sticky;
    top: 0;
    float: right;
    background: var(--sf-slate-100);
    border: 1px solid var(--sf-slate-200);
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--sf-slate-700);
    cursor: pointer;
    transition: all 0.2s ease;
    z-index: 10;
    margin-bottom: -40px;
}

.sf-modal-close-btn:hover {
    background: var(--sf-emerald-bg);
    color: var(--sf-emerald-dark);
    border-color: var(--sf-mint-border);
    transform: scale(1.1);
}

.sf-modal-article-body {
    color: var(--sf-slate-700);
    font-size: 1.05rem;
    line-height: 1.85;
}

.sf-modal-article-body h2 {
    font-family: 'Outfit', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    color: var(--sf-slate-900);
    line-height: 1.25;
    margin-bottom: 1.25rem;
}

.sf-modal-article-body h3 {
    font-family: 'Outfit', sans-serif;
    font-size: 1.45rem;
    font-weight: 700;
    color: var(--sf-slate-800);
    margin: 2rem 0 0.85rem;
    padding-bottom: 0.4rem;
    border-bottom: 2px solid var(--sf-slate-100);
}

.sf-modal-article-body h4 {
    font-family: 'Outfit', sans-serif;
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--sf-slate-900);
    margin: 1.5rem 0 0.6rem;
}

.sf-modal-article-body p {
    margin-bottom: 1.35rem;
}

.sf-modal-article-body ul, .sf-modal-article-body ol {
    margin: 0 0 1.5rem 1.75rem;
}

.sf-modal-article-body li {
    margin-bottom: 0.5rem;
}

/* Callout Q&A Box */
.sf-paa-box {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-left: 5px solid #0284c7;
    padding: 1.5rem;
    border-radius: 12px;
    margin: 1.75rem 0;
}

.sf-paa-question {
    font-weight: 700;
    color: #0369a1;
    font-size: 1.05rem;
    margin-bottom: 0.5rem;
}

.sf-paa-answer {
    color: var(--sf-slate-700);
    margin-bottom: 0;
    line-height: 1.7;
}

.sf-highlight-card {
    background: var(--sf-emerald-bg);
    border: 1px solid var(--sf-mint-border);
    border-left: 5px solid var(--sf-emerald-light);
    padding: 1.5rem;
    border-radius: 12px;
    margin: 1.75rem 0;
}

.sf-internal-link {
    color: var(--sf-emerald);
    font-weight: 700;
    text-decoration: underline;
}

/* --- SIDEBAR STYLES --- */
.sf-sidebar-wrap {
    position: sticky;
    top: 100px;
}

.sf-sidebar-card {
    background: #ffffff;
    border: 1px solid var(--sf-slate-200);
    border-radius: 18px;
    padding: 1.75rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.02);
}

.sf-sidebar-card-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--sf-slate-900);
    margin-bottom: 1.25rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--sf-emerald-light);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.sf-category-list-nav {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sf-category-list-nav li {
    margin-bottom: 0.5rem;
}

.sf-category-list-nav button {
    width: 100%;
    text-align: left;
    background: none;
    border: none;
    padding: 8px 12px;
    border-radius: 8px;
    color: var(--sf-slate-700);
    font-size: 0.925rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.2s ease;
}

.sf-category-list-nav button:hover, .sf-category-list-nav button.active {
    background: var(--sf-emerald-bg);
    color: var(--sf-emerald-dark);
}

.sf-category-count {
    background: var(--sf-slate-100);
    color: var(--sf-slate-600);
    font-size: 0.75rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 10px;
}

.sf-sidebar-crisis-box {
    background: linear-gradient(135deg, #022c22, #064e3b);
    color: #ffffff;
    border-radius: 18px;
    padding: 1.75rem;
    margin-bottom: 2rem;
    box-shadow: 0 8px 20px rgba(4, 120, 87, 0.2);
    text-align: center;
}

.sf-crisis-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.25rem;
    font-weight: 800;
    color: #ffffff;
    margin-bottom: 0.5rem;
}

.sf-crisis-phone-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    background: #10b981;
    color: #ffffff;
    font-weight: 800;
    font-size: 1.1rem;
    padding: 12px 16px;
    border-radius: 10px;
    text-decoration: none;
    margin: 1rem 0 0.5rem;
    transition: all 0.2s ease;
}

.sf-crisis-phone-btn:hover {
    background: #34d399;
    color: #064e3b;
    transform: scale(1.02);
}

.sf-no-results-box {
    display: none;
    text-align: center;
    padding: 4rem 2rem;
    background: #ffffff;
    border: 2px dashed var(--sf-slate-200);
    border-radius: 16px;
    color: var(--sf-slate-500);
    grid-column: 1 / -1;
}

/* Pagination */
.sf-pagination-wrap {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 2.5rem;
}

.sf-pagination-wrap .page-numbers {
    padding: 10px 18px;
    border-radius: 10px;
    border: 1px solid var(--sf-slate-200);
    background: #ffffff;
    color: var(--sf-slate-700);
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s ease;
}

.sf-pagination-wrap .page-numbers.current, .sf-pagination-wrap .page-numbers:hover {
    background: var(--sf-emerald);
    color: #ffffff;
    border-color: var(--sf-emerald);
}
</style>

<div class="sf-blog-page-wrap">

    <!-- HERO SECTION -->
    <header class="sf-blog-hero">
        <div class="sf-blog-hero-container">
            <span class="sf-hero-badge">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Registered NDIS Provider #4050064716
            </span>
            <h1 class="sf-hero-title">NDIS Blog & Knowledge Hub</h1>
            <p class="sf-hero-subtitle">Articles, updates, and compliance guides on Supported Independent Living (SIL), 24/7 Crisis Care, Specialist Support Coordination, Emergency Housing, and Nursing Care across Australia.</p>
            
            <!-- LIVE INSTANT SEARCH -->
            <div class="sf-hero-search-wrapper">
                <svg class="sf-search-icon-svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="sf-search-input" class="sf-search-input-field" placeholder="Search NDIS topics (e.g. SIL Registration, 24/7 Crisis, STA, Nursing)..." onkeyup="sfFilterCards()">
            </div>

            <!-- CATEGORY FILTER PILLS -->
            <nav class="sf-filter-nav" aria-label="Blog categories">
                <button class="sf-filter-btn active" data-cat="all" onclick="sfSelectCategory('all', this)">🌟 All Articles</button>
                <button class="sf-filter-btn" data-cat="sil-housing" onclick="sfSelectCategory('sil-housing', this)">🏡 SIL & Housing</button>
                <button class="sf-filter-btn" data-cat="compliance" onclick="sfSelectCategory('compliance', this)">🛡️ NDIS Compliance</button>
                <button class="sf-filter-btn" data-cat="crisis" onclick="sfSelectCategory('crisis', this)">🚨 24/7 Crisis & STA</button>
                <button class="sf-filter-btn" data-cat="coordination" onclick="sfSelectCategory('coordination', this)">🧭 Support Coordination</button>
                <button class="sf-filter-btn" data-cat="nursing" onclick="sfSelectCategory('nursing', this)">🩺 Nursing & In-Home</button>
                <button class="sf-filter-btn" data-cat="careers" onclick="sfSelectCategory('careers', this)">💼 Careers & Jobs</button>
            </nav>
        </div>
    </header>

    <!-- MAIN CONTENT + SIDEBAR -->
    <div class="sf-blog-main-container">
        
        <main>
            <!-- FEATURED SPOTLIGHT ARTICLE: SIL REGISTRATION COMMITMENT -->
            <article class="sf-featured-spotlight sf-filterable-item" data-category="sil-housing" data-search-text="sil registration 2026 practice standards supported independent living quality safeguarding compliance">
                <div class="sf-featured-tag-row">
                    <span class="sf-badge-featured">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        Featured Spotlight Guide · 2026 Standards
                    </span>
                    <span class="sf-read-time-badge">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        8 min read
                    </span>
                </div>

                <h2 class="sf-spotlight-title">Support Foundation’s Commitment to Mandatory SIL Registration & NDIS Practice Standards</h2>
                
                <p class="sf-spotlight-excerpt">
                    The introduction of mandatory NDIS registration for Supported Independent Living (SIL) providers from 1 July 2026 represents a critical step for participant rights and home safety. Explore our proactive quality frameworks across Supported Decision-Making, Proactive Safeguarding, Practice Governance, and Separation of Tenancy & Support Agreements.
                </p>

                <!-- Key Takeaways Chips -->
                <div class="sf-spotlight-key-points">
                    <div class="sf-key-point-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        1. Supported Decision-Making
                    </div>
                    <div class="sf-key-point-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        2. Participant Safeguarding
                    </div>
                    <div class="sf-key-point-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        3. Capable Practice Governance
                    </div>
                    <div class="sf-key-point-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        4. Separate Tenancy & SIL
                    </div>
                </div>

                <div class="sf-spotlight-footer">
                    <div class="sf-author-meta">
                        <div class="sf-avatar-circle">SF</div>
                        <div class="sf-author-info">
                            <strong>Support Foundation Clinical Governance</strong>
                            <span>NDIS Registered Provider #4050064716</span>
                        </div>
                    </div>

                    <button class="sf-btn-primary" onclick="sfOpenModal('modal-sil')">
                        Read Full Guide
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </button>
                </div>
            </article>

            <!-- SECTION HEADER -->
            <div class="sf-section-header-wrap">
                <h2 class="sf-section-heading">
                    Latest Articles & Guides
                </h2>
                <span id="sf-visible-count" class="sf-post-count-badge">Loading articles...</span>
            </div>

            <!-- CARDS GRID CONTAINER -->
            <div class="sf-cards-grid" id="sf-cards-grid">

                <!-- 1. DYNAMIC WORDPRESS POSTS LOOP (ADDED VIA WP ADMIN) -->
                <?php if ($dynamic_posts_query->have_posts()) : ?>
                    <?php while ($dynamic_posts_query->have_posts()) : $dynamic_posts_query->the_post(); 
                        $categories = get_the_category();
                        $cat_name = !empty($categories) ? esc_html($categories[0]->name) : 'NDIS Update';
                        $cat_slug = !empty($categories) ? esc_attr($categories[0]->slug) : 'compliance';
                        $post_content = get_the_content();
                        $read_time = max(1, round(str_word_count(strip_tags($post_content)) / 200));
                        $search_corpus = esc_attr(strtolower(get_the_title() . ' ' . get_the_excerpt() . ' ' . $cat_name));
                    ?>
                    <article class="sf-blog-card sf-filterable-item" data-category="<?php echo $cat_slug; ?>" data-search-text="<?php echo $search_corpus; ?>">
                        <div class="sf-card-thumbnail-wrap">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('medium_large', array('alt' => esc_attr(get_the_title()))); ?>
                            <?php else : ?>
                                <div class="sf-card-illustration-overlay">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="sf-card-body">
                            <div class="sf-card-category-row">
                                <span class="sf-card-tag"><?php echo $cat_name; ?></span>
                                <span class="sf-card-date"><?php echo get_the_date('M j, Y'); ?></span>
                            </div>
                            <h3 class="sf-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            <p class="sf-card-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 22, '...'); ?></p>
                            <div class="sf-card-footer">
                                <span class="sf-read-time-badge">⏱️ <?php echo $read_time; ?> min read</span>
                                <a href="<?php the_permalink(); ?>" class="sf-card-read-link">Read Post →</a>
                            </div>
                        </div>
                    </article>
                    <?php endwhile; wp_reset_postdata(); ?>
                <?php endif; ?>

                <!-- 2. PILLAR GUIDE CARDS (BUILT-IN HIGH-CONVERTING NDIS ARTICLES) -->
                
                <!-- Pillar: Complete Participant Guide to SIL -->
                <article class="sf-blog-card sf-filterable-item" data-category="sil-housing" data-search-text="complete participant guide to supported independent living sil costs roster of care eligibility co-tenant matching sda ilo rights">
                    <div class="sf-card-thumbnail-wrap" style="background: linear-gradient(135deg, #022c22, #047857);">
                        <div class="sf-card-illustration-overlay" title="Supported Independent Living SIL Guide" aria-label="Supported Independent Living SIL Guide">
                            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" role="img" aria-label="Supported Independent Living SIL Guide"><title>Supported Independent Living SIL Guide</title><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        </div>
                    </div>
                    <div class="sf-card-body">
                        <div class="sf-card-category-row">
                            <span class="sf-card-tag">🏡 SIL & Housing</span>
                            <span class="sf-card-date">Participant Guide</span>
                        </div>
                        <h3 class="sf-card-title">The Complete Participant Guide to Supported Independent Living (SIL) Under the NDIS</h3>
                        <p class="sf-card-excerpt">Understand what SIL funding covers vs out-of-pocket costs, how the Roster of Care works, co-tenant matching, and your tenancy rights.</p>
                        <div class="sf-card-footer">
                            <span class="sf-read-time-badge">⏱️ 7 min read</span>
                            <button class="sf-card-read-link" onclick="sfOpenModal('modal-sil-guide')">Read Guide →</button>
                        </div>
                    </div>
                </article>

                <!-- Pillar 1: Registered Provider Guide -->
                <article class="sf-blog-card sf-filterable-item" data-category="compliance" data-search-text="choosing a registered ndis service provider in australia compliance quality safeguards commission agency managed">
                    <div class="sf-card-thumbnail-wrap" style="background: linear-gradient(135deg, #0f172a, #1e3a8a);">
                        <div class="sf-card-illustration-overlay" title="Registered NDIS Service Provider Australia" aria-label="Registered NDIS Service Provider Australia">
                            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" role="img" aria-label="Registered NDIS Service Provider Australia"><title>Registered NDIS Service Provider Australia</title><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                        </div>
                    </div>
                    <div class="sf-card-body">
                        <div class="sf-card-category-row">
                            <span class="sf-card-tag" style="background:#eff6ff; color:#1e40af;">🛡️ NDIS Compliance</span>
                            <span class="sf-card-date">Essential Guide</span>
                        </div>
                        <h3 class="sf-card-title">Complete Guide to Choosing a Registered NDIS Service Provider in Australia</h3>
                        <p class="sf-card-excerpt">Learn how registered providers are audited under the NDIS Quality and Safeguards Commission and why registration is essential for Agency-Managed participants.</p>
                        <div class="sf-card-footer">
                            <span class="sf-read-time-badge">⏱️ 5 min read</span>
                            <button class="sf-card-read-link" onclick="sfOpenModal('modal-p1')">Read Article →</button>
                        </div>
                    </div>
                </article>

                <!-- Pillar 2: Support Coordination -->
                <article class="sf-blog-card sf-filterable-item" data-category="coordination" data-search-text="level 2 level 3 specialist ndis support coordination sydney nsw social work funding optimization">
                    <div class="sf-card-thumbnail-wrap" style="background: linear-gradient(135deg, #3b0764, #7c3aed);">
                        <div class="sf-card-illustration-overlay" title="Specialist Support Coordination Sydney NSW" aria-label="Specialist Support Coordination Sydney NSW">
                            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" role="img" aria-label="Specialist Support Coordination Sydney NSW"><title>Specialist Support Coordination Sydney NSW</title><circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon></svg>
                        </div>
                    </div>
                    <div class="sf-card-body">
                        <div class="sf-card-category-row">
                            <span class="sf-card-tag" style="background:#f5f3ff; color:#6d28d9;">🧭 Support Coordination</span>
                            <span class="sf-card-date">Care Planning</span>
                        </div>
                        <h3 class="sf-card-title">Level 2 & Level 3 Specialist NDIS Support Coordination Sydney & NSW</h3>
                        <p class="sf-card-excerpt">Understand the difference between Level 2 Coordination of Supports and Level 3 Specialist Coordination delivered by qualified social workers.</p>
                        <div class="sf-card-footer">
                            <span class="sf-read-time-badge">⏱️ 6 min read</span>
                            <button class="sf-card-read-link" onclick="sfOpenModal('modal-p2')">Read Article →</button>
                        </div>
                    </div>
                </article>

                <!-- Pillar 3: 24/7 Crisis Support -->
                <article class="sf-blog-card sf-filterable-item" data-category="crisis" data-search-text="24/7 crisis support ndis emergency hotline urgent respite immediate intervention australia">
                    <div class="sf-card-thumbnail-wrap" style="background: linear-gradient(135deg, #450a0a, #dc2626);">
                        <div class="sf-card-illustration-overlay" title="24/7 Emergency Crisis Support NDIS" aria-label="24/7 Emergency Crisis Support NDIS">
                            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" role="img" aria-label="24/7 Emergency Crisis Support NDIS"><title>24/7 Emergency Crisis Support NDIS</title><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                        </div>
                    </div>
                    <div class="sf-card-body">
                        <div class="sf-card-category-row">
                            <span class="sf-card-tag" style="background:#fef2f2; color:#b91c1c;">🚨 24/7 Crisis Support</span>
                            <span class="sf-card-date">Rapid Response</span>
                        </div>
                        <h3 class="sf-card-title">24/7 Crisis Support NDIS Services for Immediate Urgent Care</h3>
                        <p class="sf-card-excerpt">How our round-the-clock emergency team deploys rapid respite and accommodation when informal carers fall ill or urgent care breakdowns occur.</p>
                        <div class="sf-card-footer">
                            <span class="sf-read-time-badge">⏱️ 4 min read</span>
                            <button class="sf-card-read-link" onclick="sfOpenModal('modal-p3')">Read Article →</button>
                        </div>
                    </div>
                </article>

                <!-- Pillar 4: Emergency Housing -->
                <article class="sf-blog-card sf-filterable-item" data-category="crisis" data-search-text="rapid emergency housing ndis provider immediate sta accommodation crisis respite">
                    <div class="sf-card-thumbnail-wrap" style="background: linear-gradient(135deg, #134e4a, #0d9488);">
                        <div class="sf-card-illustration-overlay" title="NDIS Emergency Housing and STA Placement" aria-label="NDIS Emergency Housing and STA Placement">
                            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" role="img" aria-label="NDIS Emergency Housing and STA Placement"><title>NDIS Emergency Housing and STA Placement</title><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><line x1="12" y1="11" x2="12" y2="17"></line><line x1="9" y1="14" x2="15" y2="14"></line></svg>
                        </div>
                    </div>
                    <div class="sf-card-body">
                        <div class="sf-card-category-row">
                            <span class="sf-card-tag" style="background:#f0fdfa; color:#0f766e;">🏠 Emergency Housing</span>
                            <span class="sf-card-date">Immediate Placement</span>
                        </div>
                        <h3 class="sf-card-title">Rapid Emergency Housing NDIS Provider & Immediate Accommodation</h3>
                        <p class="sf-card-excerpt">Step-by-step guidance on accessing NDIS-funded crisis respite and safe Short-Term Accommodation when facing sudden homelessness.</p>
                        <div class="sf-card-footer">
                            <span class="sf-read-time-badge">⏱️ 5 min read</span>
                            <button class="sf-card-read-link" onclick="sfOpenModal('modal-p4')">Read Article →</button>
                        </div>
                    </div>
                </article>

                <!-- Pillar 5: STA & Respite -->
                <article class="sf-blog-card sf-filterable-item" data-category="sil-housing" data-search-text="short term accommodation sta ndis respite care 14 days living skills">
                    <div class="sf-card-thumbnail-wrap" style="background: linear-gradient(135deg, #451a03, #d97706);">
                        <div class="sf-card-illustration-overlay" title="NDIS Short Term Accommodation STA Respite" aria-label="NDIS Short Term Accommodation STA Respite">
                            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" role="img" aria-label="NDIS Short Term Accommodation STA Respite"><title>NDIS Short Term Accommodation STA Respite</title><path d="M2 4v16"></path><path d="M2 8h18a2 2 0 0 1 2 2v10"></path><path d="M2 17h20"></path><path d="M6 8v9"></path></svg>
                        </div>
                    </div>
                    <div class="sf-card-body">
                        <div class="sf-card-category-row">
                            <span class="sf-card-tag" style="background:#fffbeb; color:#b45309;">🛌 Short Term Respite</span>
                            <span class="sf-card-date">Capacity Building</span>
                        </div>
                        <h3 class="sf-card-title">Short Term Accommodation STA NDIS & Respite Care Explained</h3>
                        <p class="sf-card-excerpt">Discover how STA funding provides up to 14 days of temporary supported housing, giving informal carers rest and building participant independence.</p>
                        <div class="sf-card-footer">
                            <span class="sf-read-time-badge">⏱️ 4 min read</span>
                            <button class="sf-card-read-link" onclick="sfOpenModal('modal-p5')">Read Article →</button>
                        </div>
                    </div>
                </article>

                <!-- Pillar 6: Domestic Violence Support -->
                <article class="sf-blog-card sf-filterable-item" data-category="crisis" data-search-text="trauma informed domestic violence support ndis safety planning relocation advocacy">
                    <div class="sf-card-thumbnail-wrap" style="background: linear-gradient(135deg, #4c0519, #e11d48);">
                        <div class="sf-card-illustration-overlay" title="Trauma Informed Domestic Violence Support NDIS" aria-label="Trauma Informed Domestic Violence Support NDIS">
                            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" role="img" aria-label="Trauma Informed Domestic Violence Support NDIS"><title>Trauma Informed Domestic Violence Support NDIS</title><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
                        </div>
                    </div>
                    <div class="sf-card-body">
                        <div class="sf-card-category-row">
                            <span class="sf-card-tag" style="background:#fff1f2; color:#be123c;">🛡️ Trauma & DV Support</span>
                            <span class="sf-card-date">Confidential Care</span>
                        </div>
                        <h3 class="sf-card-title">Trauma-Informed Domestic Violence Support NDIS Safety Planning</h3>
                        <p class="sf-card-excerpt">Confidential safety planning, emergency relocation assistance, and social work advocacy for NDIS participants escaping domestic violence.</p>
                        <div class="sf-card-footer">
                            <span class="sf-read-time-badge">⏱️ 5 min read</span>
                            <button class="sf-card-read-link" onclick="sfOpenModal('modal-p6')">Read Article →</button>
                        </div>
                    </div>
                </article>

                <!-- Pillar 7: Personal Care Nursing -->
                <article class="sf-blog-card sf-filterable-item" data-category="nursing" data-search-text="in home personal care nursing ndis complex daily living medication hygiene bowel care">
                    <div class="sf-card-thumbnail-wrap" style="background: linear-gradient(135deg, #164e63, #0891b2);">
                        <div class="sf-card-illustration-overlay" title="In Home Personal Care and Nursing NDIS" aria-label="In Home Personal Care and Nursing NDIS">
                            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" role="img" aria-label="In Home Personal Care and Nursing NDIS"><title>In Home Personal Care and Nursing NDIS</title><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                        </div>
                    </div>
                    <div class="sf-card-body">
                        <div class="sf-card-category-row">
                            <span class="sf-card-tag" style="background:#ecfeff; color:#0e7490;">🩺 Personal & Nursing</span>
                            <span class="sf-card-date">Clinical Care</span>
                        </div>
                        <h3 class="sf-card-title">In-Home Personal Care Nursing NDIS & Complex Daily Living Support</h3>
                        <p class="sf-card-excerpt">High-intensity daily support including medication management, hygiene, catheter and bowel care provided by qualified registered caregivers.</p>
                        <div class="sf-card-footer">
                            <span class="sf-read-time-badge">⏱️ 5 min read</span>
                            <button class="sf-card-read-link" onclick="sfOpenModal('modal-p7')">Read Article →</button>
                        </div>
                    </div>
                </article>

                <!-- Pillar 8: Psychosocial Recovery -->
                <article class="sf-blog-card sf-filterable-item" data-category="nursing" data-search-text="psychosocial recovery coaching ndis mental health capacity building resilience">
                    <div class="sf-card-thumbnail-wrap" style="background: linear-gradient(135deg, #2e1065, #7c3aed);">
                        <div class="sf-card-illustration-overlay" title="Psychosocial Recovery Coaching Mental Health NDIS" aria-label="Psychosocial Recovery Coaching Mental Health NDIS">
                            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" role="img" aria-label="Psychosocial Recovery Coaching Mental Health NDIS"><title>Psychosocial Recovery Coaching Mental Health NDIS</title><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                        </div>
                    </div>
                    <div class="sf-card-body">
                        <div class="sf-card-category-row">
                            <span class="sf-card-tag" style="background:#f5f3ff; color:#6d28d9;">🧠 Mental Health</span>
                            <span class="sf-card-date">Recovery Coaching</span>
                        </div>
                        <h3 class="sf-card-title">Psychosocial Recovery Coaching NDIS & Mental Health Support</h3>
                        <p class="sf-card-excerpt">How recovery coaches help participants with complex mental health challenges build autonomy, self-advocacy, and strong community ties.</p>
                        <div class="sf-card-footer">
                            <span class="sf-read-time-badge">⏱️ 4 min read</span>
                            <button class="sf-card-read-link" onclick="sfOpenModal('modal-p8')">Read Article →</button>
                        </div>
                    </div>
                </article>

                <!-- Pillar 9: Careers & Support Worker Jobs -->
                <article class="sf-blog-card sf-filterable-item" data-category="careers" data-search-text="apply online disability support worker jobs sydney nsw careers recruitment hourly rates">
                    <div class="sf-card-thumbnail-wrap" style="background: linear-gradient(135deg, #0f172a, #334155);">
                        <div class="sf-card-illustration-overlay" title="Disability Support Worker Careers Sydney NSW" aria-label="Disability Support Worker Careers Sydney NSW">
                            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" role="img" aria-label="Disability Support Worker Careers Sydney NSW"><title>Disability Support Worker Careers Sydney NSW</title><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                        </div>
                    </div>
                    <div class="sf-card-body">
                        <div class="sf-card-category-row">
                            <span class="sf-card-tag" style="background:#f1f5f9; color:#334155;">💼 Careers</span>
                            <span class="sf-card-date">Now Hiring</span>
                        </div>
                        <h3 class="sf-card-title">Apply Online for Disability Support Worker Jobs Sydney & Healthcare Careers</h3>
                        <p class="sf-card-excerpt">Competitive hourly rates ($34–$55/hr), comprehensive mentoring, and rewarding career paths for dedicated disability care workers.</p>
                        <div class="sf-card-footer">
                            <span class="sf-read-time-badge">⏱️ 4 min read</span>
                            <button class="sf-card-read-link" onclick="sfOpenModal('modal-p10')">Read Careers →</button>
                        </div>
                    </div>
                </article>

                <!-- NO MATCHES PLACEHOLDER -->
                <div id="sf-no-results-box" class="sf-no-results-box">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 1rem; color: #94a3b8;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <h3 style="font-size: 1.35rem; color: var(--sf-slate-900); margin-bottom: 0.5rem;">No matching articles found</h3>
                    <p>Try searching for a different term or select "All Articles" to browse our full NDIS knowledge library.</p>
                </div>

            </div>

            <!-- PAGINATION (IF MULTIPLE WP POST PAGES) -->
            <?php if ($dynamic_posts_query->max_num_pages > 1) : ?>
                <div class="sf-pagination-wrap">
                    <?php
                    echo paginate_links(array(
                        'total'     => $dynamic_posts_query->max_num_pages,
                        'current'   => $paged,
                        'prev_text' => '← Previous',
                        'next_text' => 'Next →',
                    ));
                    ?>
                </div>
            <?php endif; ?>

        </main>

        <!-- SIDEBAR -->
        <aside>
            <div class="sf-sidebar-wrap">
                
                <!-- Category Nav Widget -->
                <div class="sf-sidebar-card">
                    <h3 class="sf-sidebar-card-title">
                        Browse by Topic
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                    </h3>
                    <ul class="sf-category-list-nav">
                        <li>
                            <button class="active" onclick="sfSelectCategory('all', this)">
                                <span>🌟 All Topics</span>
                                <span class="sf-category-count" id="count-all">11</span>
                            </button>
                        </li>
                        <li>
                            <button onclick="sfSelectCategory('sil-housing', this)">
                                <span>🏡 SIL & Supported Housing</span>
                                <span class="sf-category-count">2</span>
                            </button>
                        </li>
                        <li>
                            <button onclick="sfSelectCategory('compliance', this)">
                                <span>🛡️ NDIS Registration & Audits</span>
                                <span class="sf-category-count">2</span>
                            </button>
                        </li>
                        <li>
                            <button onclick="sfSelectCategory('crisis', this)">
                                <span>🚨 24/7 Crisis Respite & STA</span>
                                <span class="sf-category-count">3</span>
                            </button>
                        </li>
                        <li>
                            <button onclick="sfSelectCategory('coordination', this)">
                                <span>🧭 Support Coordination</span>
                                <span class="sf-category-count">1</span>
                            </button>
                        </li>
                        <li>
                            <button onclick="sfSelectCategory('nursing', this)">
                                <span>🩺 Nursing & Mental Health</span>
                                <span class="sf-category-count">2</span>
                            </button>
                        </li>
                        <li>
                            <button onclick="sfSelectCategory('careers', this)">
                                <span>💼 Healthcare Careers</span>
                                <span class="sf-category-count">1</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- 24/7 Crisis Response Banner -->
                <div class="sf-sidebar-crisis-box">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🚨</div>
                    <h3 class="sf-crisis-title">24/7 Crisis & Emergency Placement</h3>
                    <p style="font-size: 0.9rem; line-height: 1.6; color: #d1fae5; margin-bottom: 0.75rem;">
                        Urgent care breakdown, domestic violence safety planning, or immediate respite needed?
                    </p>
                    <a href="tel:0283861433" class="sf-crisis-phone-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.18 6.18l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        02 8386 1433
                    </a>
                    <span style="font-size: 0.75rem; color: #a7f3d0;">On-Call Welfare Team Available 24/7</span>
                </div>

                <!-- Make a Referral CTA -->
                <div class="sf-sidebar-card">
                    <h3 class="sf-sidebar-card-title">Make a Service Referral</h3>
                    <p style="font-size: 0.925rem; line-height: 1.6; color: var(--sf-slate-600); margin-bottom: 1.25rem;">
                        Submit a participant referral for SIL housing vacancies, specialist support coordination, or community care.
                    </p>
                    <a href="https://zfrmz.com/sIh6uDqI2c9PaujmOoTR" target="_blank" rel="noopener noreferrer" class="sf-btn-primary" style="width: 100%; box-sizing: border-box; justify-content: center;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Make Participant Referral
                    </a>
                </div>

                <!-- Career Application CTA -->
                <div class="sf-sidebar-card">
                    <h3 class="sf-sidebar-card-title">Work With Us</h3>
                    <p style="font-size: 0.925rem; line-height: 1.6; color: var(--sf-slate-600); margin-bottom: 1.25rem;">
                        We are hiring NDIS Support Workers, Coordinators, and Registered Nurses across NSW, VIC, ACT, SA & TAS.
                    </p>
                    <a href="https://forms.zohopublic.com/virtualoffice15585/form/ServiceAgreement/formperma/hSFh-yUR-CRf3xaROJUA4fFm3jYvNk5g1gPmRsdpd6I" target="_blank" rel="noopener noreferrer" class="sf-btn-primary" style="background: var(--sf-slate-900); width: 100%; box-sizing: border-box; justify-content: center;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                        Apply Online Now
                    </a>
                </div>

            </div>
        </aside>

    </div>

</div>

<!-- ==========================================================================
     FULL ARTICLE READER MODALS (FOR PILLAR GUIDES)
     ========================================================================== -->

<!-- 1. SIL Registration 2026 Modal -->
<div id="modal-sil" class="sf-reader-modal-overlay" onclick="sfCloseModalOnBg(event, 'modal-sil')">
    <div class="sf-reader-modal-container">
        <button class="sf-modal-close-btn" onclick="sfCloseModal('modal-sil')" aria-label="Close article">✕</button>
        <div class="sf-modal-article-body">
            <span class="sf-badge-featured" style="margin-bottom: 1rem;">🏡 Mandatory SIL Registration 2026 · Practice Standards</span>
            <h2>Support Foundation’s Commitment to Mandatory SIL Registration & NDIS Practice Standards</h2>
            <p style="font-size: 1.15rem; color: var(--sf-emerald); font-weight: 600;">Strengthening Quick, Quality, Quantity and Independence in Supported Independent Living</p>
            
            <p>At <strong>Support Foundation</strong>, we believe that every person with disability has the right to live with dignity, safety, independence and genuine choice and control over their own life. Supported Independent Living (SIL) is far more than providing assistance with daily activities — a participant's SIL home is their home first. It is a place where they should feel safe, respected, listened to and empowered to make decisions about how they live.</p>

            <p>The introduction of <strong>mandatory NDIS registration for SIL providers from 1 July 2026</strong>, together with the new SIL-specific NDIS Practice Standards, represents an important change for the disability sector. The new standards place a strong focus on participant rights, safety, quality of support, safeguarding and good practice within the home. Support Foundation is fully committed to meeting these requirements and embedding the principles behind the standards into our everyday SIL operations.</p>

            <div class="sf-highlight-card">
                <strong style="color: var(--sf-emerald-dark); font-size: 1.1rem; display: block; margin-bottom: 0.5rem;">Our Core Approach to Mandatory Registration</strong>
                Support Foundation is taking a structured approach to mandatory SIL registration and quality improvement. Our focus is not simply on obtaining registration or preparing for an audit — we want to ensure that our policies, workforce, governance and day-to-day practices genuinely reflect the rights and needs of the people we support.
            </div>

            <h3>The Four SIL-Specific NDIS Practice Standards</h3>
            <p>The NDIS Commission describes these standards as setting clear expectations for SIL providers, supporting consistent service delivery, quality and safety for participants and accountability across the sector. The four standards guiding our approach are:</p>

            <h4>1. Supported Decision-Making (Your Life. Your Choices. Your Voice.)</h4>
            <p>We believe that people with disability should be supported to make their own decisions, rather than having decisions made for them simply because they require support. The Supported Decision-Making Standard requires providers to support participants to understand and exercise their rights when making decisions about their home, daily life, relationships, routines, supports and community participation.</p>
            <ul>
                <li>Providing information in a way each participant can understand.</li>
                <li>Using the participant's preferred language, communication method and communication tools.</li>
                <li>Giving participants sufficient time to consider their options.</li>
                <li>Respecting each participant's will and preferences while supporting informed choices and the dignity of risk.</li>
                <li>Providing appropriate training and refresher training for workers in supported decision-making.</li>
            </ul>

            <div class="sf-paa-box">
                <div class="sf-paa-question">💡 Q: What does Supported Decision-Making mean in a SIL home?</div>
                <div class="sf-paa-answer">A: Supported decision-making means supporting a participant to make their own decision — not replacing their decision with the preference of a worker, provider, family member or other supporter. The NDIS Commission specifically emphasizes accessible information and decision-making support in the home and community.</div>
            </div>

            <h4>2. Safeguarding (Everyone Deserves to Feel Safe at Home)</h4>
            <p>A participant's home must be a place where they feel safe, respected and protected from violence, abuse, neglect, exploitation, bullying and other forms of harm. Support Foundation continues strengthening our safeguarding systems to ensure risks are identified early and responded to appropriately.</p>
            <ul>
                <li>Proactive identification and assessment of risks within the home, with active participant involvement in discussions.</li>
                <li>Appropriate incident reporting and management, responding promptly to concerns about harm, bullying or conflict.</li>
                <li>Supporting respectful relationships between people living in shared accommodation while maintaining participants' access to family, friends and community.</li>
                <li>Building stable, consistent worker relationships applying trauma-informed and person-centred approaches.</li>
            </ul>

            <h4>3. Practice Governance (Quality Support Starts with a Capable Workforce)</h4>
            <p>Good governance means more than having policies sitting in a folder. At Support Foundation, our policies, training, supervision and leadership systems translate into observable good practice in the participant's home. Workers understand that a SIL property is the participant's home, not simply a workplace.</p>
            <ul>
                <li>Appropriate induction, onboarding, participant-specific training, and competency assessments.</li>
                <li><strong>Participant Involvement in Co-Tenants:</strong> Shared living works best when people feel comfortable with the people they live alongside. Support Foundation involves existing participants in decisions affecting their home environment, including consultation when considering new co-tenants.</li>
            </ul>

            <h4>4. Agreements About Tenancy, Housing and Support Arrangements</h4>
            <p>One of the vital principles of the new SIL standards is the clear distinction between tenancy or housing arrangements and SIL support arrangements. Where Support Foundation provides both tenancy and SIL support, we ensure these arrangements are appropriately documented and clearly distinguished.</p>

            <div class="sf-paa-box">
                <div class="sf-paa-question">💡 Q: Can I lose my home if I change my SIL provider?</div>
                <div class="sf-paa-answer">A: No. Under the NDIS Practice Standards, tenancy and SIL support arrangements are legally separate. A participant should never feel that they will automatically lose their home simply because they choose to change their SIL provider.</div>
            </div>

            <h3>Building a Stronger SIL Workforce</h3>
            <p>The quality of SIL ultimately depends on the people providing the support. Support Foundation is committed to ensuring that workers have the skills, knowledge, training and supervision necessary to provide safe and person-centred support.</p>
            <p>Our workforce development approach focuses on:</p>
            <ul>
                <li><strong>Person-centred practice:</strong> Understanding each participant as an individual and tailoring support accordingly.</li>
                <li><strong>Trauma-informed practice:</strong> Understanding how past experiences may affect a person's behaviour, communication and support needs.</li>
                <li><strong>Active support:</strong> Supporting participants to participate in everyday activities and build independence rather than doing everything for them.</li>
                <li><strong>Supported decision-making:</strong> Helping participants make their own decisions and express their will and preferences.</li>
                <li><strong>Positive behaviour support:</strong> Using evidence-informed approaches to understand and respond to behaviours of concern while respecting participant rights.</li>
                <li><strong>Safeguarding and de-escalation:</strong> Ensuring workers can identify risks and respond appropriately to conflict, harm and safety concerns.</li>
            </ul>

            <h3>Continuous Improvement: Registration Is Only the Beginning</h3>
            <p>For Support Foundation, mandatory registration is not simply an audit requirement. It is an opportunity to continually evaluate our care quality:</p>
            <ul>
                <li>Are our participants genuinely making decisions about their lives?</li>
                <li>Do participants feel safe in their homes?</li>
                <li>Do our workers have the skills they need?</li>
                <li>Are our services consistent across different workers and shifts?</li>
                <li>Do participants understand their rights?</li>
                <li>Are participants involved in decisions about their home and who they live with?</li>
                <li>Can participants raise concerns without fear?</li>
                <li>Are our tenancy and service arrangements fair, transparent and clearly understood?</li>
            </ul>

            <h3>Our Commitment to People We Support</h3>
            <p>At Support Foundation, our commitment is straightforward. We will continue working towards meeting all applicable mandatory SIL registration requirements and embedding the four SIL Practice Standards into our organisation. We will continue to:</p>
            <ul>
                <li><strong>Support choice and control:</strong> Helping participants make genuine decisions about their lives, home and supports.</li>
                <li><strong>Promote safety:</strong> Maintaining strong safeguarding systems and responding to concerns appropriately.</li>
                <li><strong>Strengthen our workforce:</strong> Ensuring workers are appropriately trained, supported and competent.</li>
                <li><strong>Respect the home:</strong> Recognising that every SIL property is first and foremost the participant's home.</li>
                <li><strong>Protect rights:</strong> Supporting dignity, privacy, independence, relationships and the right to make informed choices.</li>
                <li><strong>Promote independence:</strong> Using active support and person-centred approaches to help participants build skills and achieve their goals.</li>
                <li><strong>Listen and improve:</strong> Using participant feedback, incidents, complaints, supervision and quality reviews to continuously improve our services.</li>
            </ul>

            <div style="margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid var(--sf-slate-200); display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="https://zfrmz.com/sIh6uDqI2c9PaujmOoTR" target="_blank" rel="noopener noreferrer" class="sf-btn-primary">Inquire About SIL Vacancies</a>
                <a href="tel:0283861433" class="sf-btn-primary" style="background: var(--sf-slate-900);">Call Team (02 8386 1433)</a>
            </div>
        </div>
    </div>
</div>

<!-- 2. Registered Provider Guide Modal -->
<div id="modal-p1" class="sf-reader-modal-overlay" onclick="sfCloseModalOnBg(event, 'modal-p1')">
    <div class="sf-reader-modal-container">
        <button class="sf-modal-close-btn" onclick="sfCloseModal('modal-p1')">✕</button>
        <div class="sf-modal-article-body">
            <span class="sf-card-tag" style="margin-bottom: 1rem; display: inline-block;">🛡️ NDIS Compliance</span>
            <h2>Complete Guide to Choosing a Registered NDIS Service Provider in Australia</h2>
            <p>Finding a trusted <strong>Registered NDIS Service Provider in Australia</strong> is the single most critical decision for participants, carers, and families looking for safe, high-quality disability support. A registered provider operates under direct regulation by the <strong>NDIS Quality and Safeguards Commission</strong>, guaranteeing independent quality audits, mandatory worker screening, and strict incident reporting standards.</p>
            <h3>What Makes a Service Provider "Registered"?</h3>
            <p>To achieve formal registration status, an organization must complete detailed compliance audits across governance, risk management, clinical care delivery, and human rights protection. At Support Foundation Australia (NDIS Registration #4050064716), registration represents our commitment to ethical social work standards, AASW principles, and community care excellence.</p>
            <div class="sf-paa-box">
                <div class="sf-paa-question">💡 Q: Why should I choose a Registered NDIS Service Provider in Australia?</div>
                <div class="sf-paa-answer">A: Choosing a Registered NDIS Provider ensures your supports comply with audited federal quality standards. Registered providers can deliver services to participants across all funding management types: Agency-Managed (NDIS-Managed), Plan-Managed, and Self-Managed plans.</div>
            </div>
            <h3>Plan Compatibility: Agency-Managed vs Self-Managed</h3>
            <p>Participants whose plans are managed by the NDIA (Agency-Managed) are legally restricted to receiving services from a Registered NDIS Service Provider in Australia. Plan-managed and self-managed participants also gain peace of mind knowing registered providers adhere to strict safeguards.</p>
        </div>
    </div>
</div>

<!-- 3. Support Coordination Modal -->
<div id="modal-p2" class="sf-reader-modal-overlay" onclick="sfCloseModalOnBg(event, 'modal-p2')">
    <div class="sf-reader-modal-container">
        <button class="sf-modal-close-btn" onclick="sfCloseModal('modal-p2')">✕</button>
        <div class="sf-modal-article-body">
            <span class="sf-card-tag" style="margin-bottom: 1rem; display: inline-block;">🧭 Support Coordination</span>
            <h2>Level 2 & Level 3 Specialist NDIS Support Coordination Sydney & NSW</h2>
            <p>Navigating the complexity of NDIS funding requires expert guidance. Our <strong>NDIS Support Coordination Sydney & NSW</strong> team works directly with participants and families to connect them with mainstream healthcare, community services, and crisis accommodation across New South Wales.</p>
            <div class="sf-paa-box">
                <div class="sf-paa-question">💡 Q: What is the difference between Level 2 and Level 3 NDIS Support Coordination in Sydney?</div>
                <div class="sf-paa-answer">A: Level 2 Support Coordination helps participants build independence to manage service provider relationships. Level 3 Specialist Support Coordination is delivered by qualified social workers to manage high-risk participant crises, complex health transitions, and multi-agency care plans.</div>
            </div>
            <h3>How Our Support Coordinators Help You</h3>
            <ul>
                <li>Optimizing participant NDIS plan funding allocations to maximize support value.</li>
                <li>Resolving informal care breakdown and housing crises with immediate multi-agency response.</li>
                <li>Preparing clinical evidence for NDIS plan reviews, change of circumstances, and appeals.</li>
            </ul>
        </div>
    </div>
</div>

<!-- 4. Crisis Modal -->
<div id="modal-p3" class="sf-reader-modal-overlay" onclick="sfCloseModalOnBg(event, 'modal-p3')">
    <div class="sf-reader-modal-container">
        <button class="sf-modal-close-btn" onclick="sfCloseModal('modal-p3')">✕</button>
        <div class="sf-modal-article-body">
            <span class="sf-card-tag" style="margin-bottom: 1rem; display: inline-block;">🚨 24/7 Crisis Support</span>
            <h2>24/7 Crisis Support NDIS Services for Immediate Urgent Care</h2>
            <p>Disability care emergencies require immediate, professional action. Our <strong>24/7 Crisis Support NDIS</strong> response team operates around the clock, providing rapid intervention when care breaks down, informal carers become unwell, or urgent housing relocation is required.</p>
            <div class="sf-paa-box">
                <div class="sf-paa-question">💡 Q: How fast can 24/7 Crisis Support NDIS be arranged?</div>
                <div class="sf-paa-answer">A: Support Foundation operates an emergency 24/7 crisis hotline at 02-8386-1433. On-call welfare coordinators review crisis requests immediately and deploy emergency support workers or accommodation placement within hours.</div>
            </div>
            <p>Contact our emergency response team anytime on <a href="tel:0283861433" class="sf-internal-link">02 8386 1433</a>.</p>
        </div>
    </div>
</div>

<!-- 5. Emergency Housing Modal -->
<div id="modal-p4" class="sf-reader-modal-overlay" onclick="sfCloseModalOnBg(event, 'modal-p4')">
    <div class="sf-reader-modal-container">
        <button class="sf-modal-close-btn" onclick="sfCloseModal('modal-p4')">✕</button>
        <div class="sf-modal-article-body">
            <span class="sf-card-tag" style="margin-bottom: 1rem; display: inline-block;">🏠 Emergency Housing</span>
            <h2>Rapid Emergency Housing NDIS Provider & Immediate Accommodation</h2>
            <p>Facing sudden homelessness or unsafe living conditions is a critical emergency. As an established <strong>Emergency Housing NDIS Provider</strong>, Support Foundation places participants into safe, accessible Short-Term Accommodation (STA) rapidly.</p>
            <div class="sf-paa-box">
                <div class="sf-paa-question">💡 Q: Can NDIS fund emergency housing accommodation?</div>
                <div class="sf-paa-answer">A: Yes. NDIS Short-Term Accommodation (STA) and Crisis Respite funding can cover up to 14 days of emergency housing, 24/7 support worker care, and daily meals for participants experiencing housing crisis.</div>
            </div>
        </div>
    </div>
</div>

<!-- 6. STA Modal -->
<div id="modal-p5" class="sf-reader-modal-overlay" onclick="sfCloseModalOnBg(event, 'modal-p5')">
    <div class="sf-reader-modal-container">
        <button class="sf-modal-close-btn" onclick="sfCloseModal('modal-p5')">✕</button>
        <div class="sf-modal-article-body">
            <span class="sf-card-tag" style="margin-bottom: 1rem; display: inline-block;">🛌 Short Term Respite</span>
            <h2>Short Term Accommodation STA NDIS & Respite Care Explained</h2>
            <p><strong>Short Term Accommodation STA NDIS</strong> funding provides temporary supported housing away from home for up to 14 days at a time. STA gives informal carers an opportunity to recharge while participants build new daily living skills and community connections.</p>
        </div>
    </div>
</div>

<!-- 7. Domestic Violence Modal -->
<div id="modal-p6" class="sf-reader-modal-overlay" onclick="sfCloseModalOnBg(event, 'modal-p6')">
    <div class="sf-reader-modal-container">
        <button class="sf-modal-close-btn" onclick="sfCloseModal('modal-p6')">✕</button>
        <div class="sf-modal-article-body">
            <span class="sf-card-tag" style="margin-bottom: 1rem; display: inline-block;">🛡️ Trauma & DV Support</span>
            <h2>Trauma-Informed Domestic Violence Support NDIS Safety Planning</h2>
            <p>Our dedicated <strong>Domestic Violence Support NDIS</strong> team delivers confidential safety planning, safe emergency relocation, and social work advocacy for NDIS participants escaping domestic or family violence.</p>
        </div>
    </div>
</div>

<!-- 8. Personal Care Modal -->
<div id="modal-p7" class="sf-reader-modal-overlay" onclick="sfCloseModalOnBg(event, 'modal-p7')">
    <div class="sf-reader-modal-container">
        <button class="sf-modal-close-btn" onclick="sfCloseModal('modal-p7')">✕</button>
        <div class="sf-modal-article-body">
            <span class="sf-card-tag" style="margin-bottom: 1rem; display: inline-block;">🩺 Personal & Nursing</span>
            <h2>In-Home Personal Care Nursing NDIS & Complex Daily Living Support</h2>
            <p>Our <strong>Personal Care Nursing NDIS</strong> services assist participants with daily living activities, hygiene, grooming, medication administration, and complex high-intensity nursing support in the comfort of their home.</p>
        </div>
    </div>
</div>

<!-- 9. Psychosocial Recovery Modal -->
<div id="modal-p8" class="sf-reader-modal-overlay" onclick="sfCloseModalOnBg(event, 'modal-p8')">
    <div class="sf-reader-modal-container">
        <button class="sf-modal-close-btn" onclick="sfCloseModal('modal-p8')">✕</button>
        <div class="sf-modal-article-body">
            <span class="sf-card-tag" style="margin-bottom: 1rem; display: inline-block;">🧠 Mental Health</span>
            <h2>Psychosocial Recovery Coaching NDIS & Mental Health Support</h2>
            <p>Working alongside participants with mental health conditions, a <strong>Psychosocial Recovery Coach NDIS</strong> builds resilience, capacity, and self-advocacy to foster long-term mental health recovery.</p>
        </div>
    </div>
</div>

<!-- 10. Careers Modal -->
<div id="modal-p10" class="sf-reader-modal-overlay" onclick="sfCloseModalOnBg(event, 'modal-p10')">
    <div class="sf-reader-modal-container">
        <button class="sf-modal-close-btn" onclick="sfCloseModal('modal-p10')">✕</button>
        <div class="sf-modal-article-body">
            <span class="sf-card-tag" style="margin-bottom: 1rem; display: inline-block;">💼 Healthcare Careers</span>
            <h2>Apply Online for Disability Support Worker Jobs Sydney & Healthcare Careers</h2>
            <p>Looking for <strong>Disability Support Worker Jobs Sydney & NSW</strong>? Support Foundation is actively hiring compassionate support workers, nurses, and coordinators with competitive hourly pay rates ($34–$55/hr). Submit your application directly via our <a href="https://forms.zohopublic.com/virtualoffice15585/form/ServiceAgreement/formperma/hSFh-yUR-CRf3xaROJUA4fFm3jYvNk5g1gPmRsdpd6I" target="_blank" rel="noopener noreferrer" class="sf-internal-link">Job Application Form</a>.</p>
        </div>
    </div>
</div>

<!-- 11. Complete Participant Guide to SIL Modal -->
<div id="modal-sil-guide" class="sf-reader-modal-overlay" onclick="sfCloseModalOnBg(event, 'modal-sil-guide')">
    <div class="sf-reader-modal-container">
        <button class="sf-modal-close-btn" onclick="sfCloseModal('modal-sil-guide')" aria-label="Close article">✕</button>
        <div class="sf-modal-article-body">
            <span class="sf-card-tag" style="margin-bottom: 1rem; display: inline-block;">🏡 SIL & Supported Housing</span>
            <h2>The Complete Participant Guide to Supported Independent Living (SIL) Under the NDIS</h2>
            <p style="font-size: 1.15rem; color: var(--sf-emerald); font-weight: 600;">Everything NDIS participants, carers, and families need to know about navigating Home & Living funding, understanding living expenses vs support costs, co-tenant matching, and choosing a trusted registered provider.</p>
            
            <p>Moving into your own home or transitioning into supported accommodation is one of the most empowering milestones in an NDIS participant's journey. However, navigating <strong>Supported Independent Living (SIL)</strong>, understanding the difference between housing types, calculating out-of-pocket costs, and deciphering the NDIA's "Roster of Care" can feel overwhelming for participants and their families.</p>

            <div class="sf-highlight-card">
                <strong style="color: var(--sf-emerald-dark); font-size: 1.1rem; display: block; margin-bottom: 0.5rem;">💡 Key Takeaway in Brief</strong>
                SIL funding pays for the <strong>support workers</strong> who assist you with daily living activities (showering, medication, cooking, community outings) 24 hours a day, 7 days a week. It does <em>not</em> pay for your rent, food, or electricity bills, which are covered by your ordinary living allowance (e.g. Disability Support Pension and Commonwealth Rent Assistance).
            </div>

            <h3>1. Understanding the Differences: SIL vs SDA vs ILO</h3>
            <ul>
                <li><strong>SIL (Supported Independent Living):</strong> The <strong>care & support staff</strong> in the home (day shifts, active night, sleepover care) for participants with high or complex daily needs.</li>
                <li><strong>SDA (Specialist Disability Accommodation):</strong> The <strong>physical building & accessible bricks</strong> (wheelchair accessible, robust, high physical support).</li>
                <li><strong>ILO (Individualised Living Options):</strong> Flexible alternative arrangements (e.g., host families, flatmates, drop-in support).</li>
            </ul>

            <h3>2. What Does SIL Funding Pay For vs What Do You Pay?</h3>
            <p><strong>Covered by NDIS SIL:</strong> 24/7 support worker care, personal care, medication administration, meal prep, household chores, positive behavior support, overnight safety, and medical appointment transport.</p>
            <p><strong>Paid by Participant:</strong> Rent (typically 25% of DSP + 100% of Rent Assistance), shared grocery kitty, electricity/water/Wi-Fi bills, and personal leisure spending.</p>

            <h3>3. What is a "Roster of Care" (RoC)?</h3>
            <p>The Roster of Care is an exact, hour-by-hour weekly timetable detailing when you need support, staffing ratios (1:1 vs shared 1:2), and whether overnight support is active or sleepover. The NDIA uses this along with your Functional Capacity Assessment (FCA) to calculate your annual funding.</p>

            <h3>4. How Co-Tenant Matching Works at Support Foundation</h3>
            <p>We believe shared living must be safe, comfortable, and harmonious. We match housemates based on age, interests, lifestyle routines, and communication styles. We offer meet-and-greets, trial dinners, and weekend respite stays before anyone moves in permanently.</p>

            <h3>5. Your Rights: Separate Tenancy from Service Agreements</h3>
            <p>Under the new 2026 NDIS Practice Standards, your tenancy agreement is legally separate from your SIL support agreement. <strong>You have the right to change your SIL provider at any time without fear of losing your home.</strong></p>

            <div style="margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid var(--sf-slate-200); display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="https://zfrmz.com/sIh6uDqI2c9PaujmOoTR" target="_blank" rel="noopener noreferrer" class="sf-btn-primary">Inquire About SIL Vacancies</a>
                <a href="tel:0283861433" class="sf-btn-primary" style="background: var(--sf-slate-900);">Speak with an Intake Specialist (02 8386 1433)</a>
            </div>
        </div>
    </div>
</div>

<!-- JAVASCRIPT: FILTERING, SEARCH & MODAL HANDLERS -->
<script>
var currentCategory = 'all';

function sfFilterCards() {
    var searchVal = document.getElementById('sf-search-input').value.toLowerCase().trim();
    var items = document.querySelectorAll('.sf-filterable-item');
    var visibleCount = 0;

    items.forEach(function(item) {
        var itemCat = item.getAttribute('data-category');
        var itemSearchText = (item.getAttribute('data-search-text') || '') + ' ' + item.innerText.toLowerCase();
        
        var matchesCategory = (currentCategory === 'all' || itemCat === currentCategory);
        var matchesSearch = (!searchVal || itemSearchText.indexOf(searchVal) !== -1);

        if (matchesCategory && matchesSearch) {
            item.style.display = item.classList.contains('sf-featured-spotlight') ? 'block' : 'flex';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    var countBadge = document.getElementById('sf-visible-count');
    if (countBadge) {
        countBadge.textContent = visibleCount + ' Available';
    }

    var noResults = document.getElementById('sf-no-results-box');
    if (noResults) {
        noResults.style.display = (visibleCount === 0) ? 'block' : 'none';
    }
}

function sfSelectCategory(category, btnElement) {
    currentCategory = category;

    // Update filter nav buttons
    var allNavBtns = document.querySelectorAll('.sf-filter-btn');
    allNavBtns.forEach(function(btn) {
        btn.classList.remove('active');
        if (btn.getAttribute('data-cat') === category) {
            btn.classList.add('active');
        }
    });

    // Update sidebar buttons
    var sidebarBtns = document.querySelectorAll('.sf-category-list-nav button');
    sidebarBtns.forEach(function(btn) {
        btn.classList.remove('active');
    });
    if (btnElement && btnElement.tagName === 'BUTTON') {
        btnElement.classList.add('active');
    }

    sfFilterCards();
}

function sfOpenModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function sfCloseModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

function sfCloseModalOnBg(event, modalId) {
    if (event.target && event.target.id === modalId) {
        sfCloseModal(modalId);
    }
}

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        var openModals = document.querySelectorAll('.sf-reader-modal-overlay.active');
        openModals.forEach(function(m) {
            m.classList.remove('active');
        });
        document.body.style.overflow = 'auto';
    }
});

// Run filter count initialization on load
document.addEventListener('DOMContentLoaded', function() {
    sfFilterCards();
});
</script>

<?php
get_footer();

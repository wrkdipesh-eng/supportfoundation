<?php
/**
 * Template Name: ReactCorp NDIS Blog & Knowledge Hub
 * Description: Modern, dynamic blog hub supporting standard WordPress posts and comprehensive NDIS pillar guides for ReactCorp Disability Services.
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
      "@id": "https://reactcorpdisability.com.au/blog/#blog",
      "url": "https://reactcorpdisability.com.au/blog/",
      "name": "ReactCorp Disability Services NDIS Knowledge Hub & Blog",
      "description": "Comprehensive guides and updates on Supported Independent Living (SIL), 24/7 crisis support, support coordination, and disability care rights across Australia.",
      "publisher": {
        "@type": "Organization",
        "name": "ReactCorp Disability Services",
        "url": "https://reactcorpdisability.com.au/",
        "logo": {
          "@type": "ImageObject",
          "url": "https://reactcorpdisability.com.au/wp-content/uploads/2024/02/cropped-reactcorp-logo.png"
        }
      }
    },
    {
      "@type": "FAQPage",
      "@id": "https://reactcorpdisability.com.au/blog/#faq",
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
          "name": "Why choose a Registered NDIS Service Provider like ReactCorp?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Registered NDIS providers comply with audited federal quality standards, undergo mandatory worker screening, maintain strict incident management, and can support Agency-Managed, Plan-Managed, and Self-Managed participants."
          }
        },
        {
          "@type": "Question",
          "name": "How does ReactCorp provide 24/7 emergency crisis support?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "ReactCorp operates a round-the-clock emergency care hotline (0422 069 482 / 02 8386 1433) providing rapid response for care breakdowns, crisis respite, domestic violence safety planning, and immediate Short-Term Accommodation (STA) placement."
          }
        }
      ]
    }
  ]
}
</script>

<style>
/* ==========================================================================
   REACTCORP MODERN BLOG & KNOWLEDGE HUB STYLES
   ========================================================================== */
:root {
    --rc-purple: #581c87;
    --rc-purple-dark: #3b0764;
    --rc-purple-light: #7e22ce;
    --rc-fuchsia: #c084fc;
    --rc-fuchsia-bg: #faf5ff;
    --rc-fuchsia-border: #e9d5ff;
    --rc-teal: #0d9488;
    --rc-slate-900: #0f172a;
    --rc-slate-800: #1e293b;
    --rc-slate-700: #334155;
    --rc-slate-600: #475569;
    --rc-slate-500: #64748b;
    --rc-slate-200: #e2e8f0;
    --rc-slate-100: #f1f5f9;
    --rc-slate-50: #f8fafc;
}

.rc-blog-page-wrap {
    background-color: var(--rc-slate-50);
    color: var(--rc-slate-700);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    min-height: 100vh;
}

/* --- HERO SECTION --- */
.rc-blog-hero {
    background: linear-gradient(135deg, #2e1065 0%, #4c1d95 35%, #581c87 75%, #7e22ce 100%);
    color: #ffffff;
    padding: 5rem 1.5rem 4rem;
    position: relative;
    overflow: hidden;
    text-align: center;
}

.rc-blog-hero::before {
    content: '';
    position: absolute;
    top: -30%;
    right: -10%;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(192, 132, 252, 0.25) 0%, rgba(88, 28, 135, 0) 70%);
    border-radius: 50%;
    pointer-events: none;
}

.rc-blog-hero::after {
    content: '';
    position: absolute;
    bottom: -20%;
    left: -10%;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(13, 148, 136, 0.2) 0%, rgba(88, 28, 135, 0) 70%);
    border-radius: 50%;
    pointer-events: none;
}

.rc-blog-hero-container {
    max-width: 960px;
    margin: 0 auto;
    position: relative;
    z-index: 2;
}

.rc-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    color: #f0abfc;
    font-size: 0.85rem;
    font-weight: 700;
    padding: 6px 18px;
    border-radius: 50px;
    margin-bottom: 1.25rem;
    text-transform: uppercase;
    letter-spacing: 0.75px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.rc-hero-title {
    font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
    font-size: clamp(2.2rem, 5vw, 3.4rem);
    font-weight: 800;
    line-height: 1.15;
    margin-bottom: 1.25rem;
    color: #ffffff;
    letter-spacing: -0.5px;
}

.rc-hero-subtitle {
    font-size: clamp(1.05rem, 2vw, 1.25rem);
    line-height: 1.65;
    color: #f5d0fe;
    max-width: 820px;
    margin: 0 auto 2.25rem;
    font-weight: 400;
}

/* Search Box */
.rc-hero-search-wrapper {
    max-width: 680px;
    margin: 0 auto 2rem;
    position: relative;
    display: flex;
    align-items: center;
}

.rc-search-input-field {
    width: 100%;
    padding: 16px 24px 16px 64px !important;
    font-size: 1.05rem;
    border-radius: 50px;
    border: 2px solid rgba(255, 255, 255, 0.4);
    background: #ffffff;
    color: var(--rc-slate-900);
    box-shadow: 0 12px 35px -5px rgba(0, 0, 0, 0.25);
    outline: none;
    transition: all 0.25s ease;
    box-sizing: border-box;
}

.rc-search-input-field:focus {
    border-color: #c084fc;
    background: #ffffff;
    box-shadow: 0 16px 40px -5px rgba(126, 34, 206, 0.4);
}

.rc-search-icon-svg {
    position: absolute;
    left: 24px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--rc-purple-light);
    pointer-events: none;
    z-index: 10;
    width: 22px;
    height: 22px;
}

/* Category Filter Pills */
.rc-filter-nav {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
    margin-top: 1rem;
}

.rc-filter-btn {
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

.rc-filter-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-1px);
    color: #ffffff;
}

.rc-filter-btn.active {
    background: #ffffff;
    color: var(--rc-purple);
    border-color: #ffffff;
    font-weight: 700;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
}

/* --- MAIN LAYOUT --- */
.rc-blog-main-container {
    max-width: 1320px;
    margin: 0 auto;
    padding: 3.5rem 1.5rem 5rem;
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 3rem;
    align-items: start;
}

@media (max-width: 1024px) {
    .rc-blog-main-container {
        grid-template-columns: 1fr;
        gap: 2.5rem;
    }
}

/* --- SPOTLIGHT FEATURED CARD --- */
.rc-spotlight-card {
    background: #ffffff;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.06), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
    border: 1px solid var(--rc-fuchsia-border);
    margin-bottom: 3.5rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
}

.rc-spotlight-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 20px 40px -10px rgba(88, 28, 135, 0.12);
}

.rc-spotlight-inner {
    display: grid;
    grid-template-columns: 1fr;
    padding: 3rem;
}

.rc-spotlight-tag-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.rc-spotlight-badge {
    background: linear-gradient(135deg, var(--rc-purple) 0%, var(--rc-purple-light) 100%);
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.75px;
    padding: 6px 14px;
    border-radius: 50px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.rc-spotlight-date {
    font-size: 0.875rem;
    color: var(--rc-slate-500);
    font-weight: 500;
}

.rc-spotlight-title {
    font-family: 'Outfit', sans-serif;
    font-size: clamp(1.6rem, 3vw, 2.25rem);
    font-weight: 800;
    color: var(--rc-slate-900);
    line-height: 1.25;
    margin-bottom: 1rem;
}

.rc-spotlight-excerpt {
    font-size: 1.05rem;
    line-height: 1.75;
    color: var(--rc-slate-700);
    margin-bottom: 1.75rem;
}

.rc-spotlight-key-points {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
    margin-bottom: 2rem;
}

.rc-key-point-item {
    background: var(--rc-slate-50);
    border: 1px solid var(--rc-slate-200);
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--rc-slate-800);
    display: flex;
    align-items: center;
    gap: 10px;
}

.rc-key-point-item svg {
    color: var(--rc-purple-light);
    flex-shrink: 0;
}

.rc-spotlight-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    padding-top: 1.5rem;
    border-top: 1px solid var(--rc-slate-100);
}

.rc-author-meta {
    display: flex;
    align-items: center;
    gap: 12px;
}

.rc-author-avatar-sm {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--rc-purple);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.rc-author-text strong {
    display: block;
    color: var(--rc-slate-900);
    font-size: 0.95rem;
    line-height: 1.2;
}

.rc-author-text span {
    font-size: 0.8rem;
    color: var(--rc-slate-500);
}

.rc-btn-read-spotlight {
    background: linear-gradient(135deg, var(--rc-purple) 0%, var(--rc-purple-light) 100%);
    color: #ffffff;
    font-weight: 700;
    font-size: 0.95rem;
    padding: 12px 28px;
    border-radius: 50px;
    border: none;
    cursor: pointer;
    transition: all 0.25s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    box-shadow: 0 4px 15px rgba(88, 28, 135, 0.25);
}

.rc-btn-read-spotlight:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(88, 28, 135, 0.35);
    color: #ffffff;
}

/* --- SECTION HEADER --- */
.rc-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--rc-slate-200);
    flex-wrap: wrap;
    gap: 12px;
}

.rc-section-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--rc-slate-900);
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
}

.rc-section-count {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--rc-slate-500);
    background: var(--rc-slate-100);
    padding: 4px 12px;
    border-radius: 20px;
}

/* --- ARTICLE CARDS GRID --- */
.rc-articles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2rem;
    margin-bottom: 3.5rem;
}

@media (max-width: 640px) {
    .rc-articles-grid {
        grid-template-columns: 1fr;
    }
}

.rc-blog-card {
    background: #ffffff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 15px -2px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--rc-slate-200);
    display: flex;
    flex-direction: column;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.rc-blog-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 16px 30px -5px rgba(0, 0, 0, 0.08);
    border-color: var(--rc-fuchsia-border);
}

.rc-card-thumbnail-wrap {
    height: 180px;
    background: linear-gradient(135deg, #2e1065 0%, #581c87 100%);
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.rc-card-thumbnail-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.rc-blog-card:hover .rc-card-thumbnail-wrap img {
    transform: scale(1.05);
}

.rc-card-illustration-overlay {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at center, rgba(255,255,255,0.15) 0%, rgba(0,0,0,0.15) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255, 255, 255, 0.9);
}

.rc-card-body {
    padding: 1.75rem;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.rc-card-category-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.85rem;
}

.rc-card-tag {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 4px 10px;
    border-radius: 6px;
    background: var(--rc-fuchsia-bg);
    color: var(--rc-purple-light);
}

.rc-card-date {
    font-size: 0.8rem;
    color: var(--rc-slate-500);
}

.rc-card-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1.35;
    color: var(--rc-slate-900);
    margin-bottom: 0.75rem;
    transition: color 0.2s ease;
}

.rc-card-title a {
    color: inherit;
    text-decoration: none;
}

.rc-blog-card:hover .rc-card-title {
    color: var(--rc-purple);
}

.rc-card-excerpt {
    font-size: 0.925rem;
    line-height: 1.6;
    color: var(--rc-slate-600);
    margin-bottom: 1.5rem;
    flex-grow: 1;
}

.rc-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 1rem;
    border-top: 1px solid var(--rc-slate-100);
    font-size: 0.85rem;
}

.rc-read-time-badge {
    color: var(--rc-slate-500);
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.rc-card-read-link {
    color: var(--rc-purple);
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    font-size: 0.9rem;
    transition: gap 0.2s ease;
}

.rc-card-read-link:hover {
    gap: 8px;
    color: var(--rc-purple-light);
}

/* --- SIDEBAR --- */
.rc-sidebar-wrap {
    position: sticky;
    top: 90px;
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.rc-sidebar-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 1.75rem;
    box-shadow: 0 4px 15px -2px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--rc-slate-200);
}

.rc-sidebar-card-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--rc-slate-900);
    margin-bottom: 1.25rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid var(--rc-slate-100);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.rc-category-list-nav {
    list-style: none;
    padding: 0;
    margin: 0;
}

.rc-category-list-nav li {
    margin-bottom: 8px;
}

.rc-category-list-nav button {
    width: 100%;
    text-align: left;
    background: none;
    border: none;
    padding: 8px 12px;
    border-radius: 8px;
    color: var(--rc-slate-700);
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.2s ease;
}

.rc-category-list-nav button:hover,
.rc-category-list-nav button.active {
    background: var(--rc-fuchsia-bg);
    color: var(--rc-purple);
}

.rc-category-count {
    background: var(--rc-slate-100);
    color: var(--rc-slate-600);
    font-size: 0.75rem;
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 700;
}

.rc-category-list-nav button.active .rc-category-count {
    background: var(--rc-purple);
    color: #ffffff;
}

/* 24/7 Crisis Callout */
.rc-crisis-sidebar-widget {
    background: linear-gradient(135deg, #2e1065 0%, #581c87 100%);
    color: #ffffff;
    border: none;
    position: relative;
    overflow: hidden;
}

.rc-crisis-sidebar-widget::after {
    content: '';
    position: absolute;
    right: -20px;
    bottom: -20px;
    width: 120px;
    height: 120px;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
    border-radius: 50%;
}

.rc-crisis-badge {
    background: rgba(240, 171, 252, 0.25);
    color: #f0abfc;
    font-size: 0.75rem;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 4px;
    text-transform: uppercase;
    display: inline-block;
    margin-bottom: 0.75rem;
}

.rc-crisis-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.35rem;
    font-weight: 800;
    color: #ffffff;
    margin-bottom: 0.75rem;
}

.rc-crisis-phone-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #ffffff;
    color: var(--rc-purple);
    font-weight: 800;
    font-size: 1.1rem;
    padding: 12px 20px;
    border-radius: 50px;
    text-decoration: none;
    margin: 1.25rem 0 0.5rem;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
    transition: all 0.2s ease;
}

.rc-crisis-phone-btn:hover {
    transform: translateY(-2px);
    background: #fdf4ff;
    color: var(--rc-purple-light);
}

/* Modal Reader */
.rc-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.8);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    animation: rcFadeIn 0.25s ease forwards;
}

.rc-modal-container {
    background: #ffffff;
    border-radius: 20px;
    max-width: 900px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    position: relative;
    padding: 3rem;
}

@media (max-width: 768px) {
    .rc-modal-container {
        padding: 1.5rem;
    }
}

.rc-modal-close-btn {
    position: sticky;
    top: 0;
    float: right;
    background: #f1f5f9;
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 1.25rem;
    color: var(--rc-slate-700);
    z-index: 10;
    transition: all 0.2s ease;
}

.rc-modal-close-btn:hover {
    background: #e2e8f0;
    color: var(--rc-slate-900);
}

.rc-modal-article-body {
    font-size: 1.05rem;
    line-height: 1.8;
    color: var(--rc-slate-700);
}

.rc-modal-article-body h2 {
    font-family: 'Outfit', sans-serif;
    color: var(--rc-purple);
    font-size: 1.75rem;
    margin: 2rem 0 1rem;
    border-bottom: 2px solid var(--rc-slate-100);
    padding-bottom: 0.5rem;
}

.rc-modal-article-body h3 {
    font-family: 'Outfit', sans-serif;
    color: var(--rc-slate-900);
    font-size: 1.35rem;
    margin: 1.5rem 0 0.75rem;
}

.rc-modal-article-body ul, .rc-modal-article-body ol {
    margin: 1rem 0 1.5rem 1.5rem;
}

.rc-modal-article-body li {
    margin-bottom: 0.5rem;
}

.rc-modal-article-body blockquote {
    background: #faf5ff;
    border-left: 4px solid var(--rc-purple-light);
    border-radius: 0 8px 8px 0;
    padding: 1rem 1.5rem;
    margin: 1.5rem 0;
    font-style: italic;
    color: #4c1d95;
}

.rc-no-results-box {
    display: none;
    text-align: center;
    padding: 4rem 2rem;
    background: #ffffff;
    border-radius: 16px;
    border: 2px dashed var(--rc-slate-200);
    grid-column: 1 / -1;
}

@keyframes rcFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>

<div class="rc-blog-page-wrap">

    <!-- 1. HERO BANNER -->
    <header class="rc-blog-hero">
        <div class="rc-blog-hero-container">
            <span class="rc-hero-badge">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Registered NDIS Provider · #4050064716
            </span>
            <h1 class="rc-hero-title">ReactCorp Disability Updates & Blog</h1>
            <p class="rc-hero-subtitle">Comprehensive participant guides, NDIS compliance standards, Supported Independent Living (SIL) resources, and 24/7 crisis support across Australia.</p>
            
            <!-- Real-Time Interactive Search -->
            <div class="rc-hero-search-wrapper">
                <svg class="rc-search-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="rc-search-input" class="rc-search-input-field" placeholder="Search SIL rules, Roster of Care, crisis housing, support coordination..." onkeyup="rcFilterContent()">
            </div>

            <!-- Quick Filter Pills -->
            <nav class="rc-filter-nav" aria-label="Topic Filter">
                <button class="rc-filter-btn active" onclick="rcSelectCategory('all', this)">🌟 All Topics</button>
                <button class="rc-filter-btn" onclick="rcSelectCategory('sil-housing', this)">🏡 SIL & Housing</button>
                <button class="rc-filter-btn" onclick="rcSelectCategory('compliance', this)">🛡️ NDIS Compliance</button>
                <button class="rc-filter-btn" onclick="rcSelectCategory('crisis', this)">🚨 24/7 Crisis & STA</button>
                <button class="rc-filter-btn" onclick="rcSelectCategory('coordination', this)">🧭 Support Coordination</button>
                <button class="rc-filter-btn" onclick="rcSelectCategory('nursing', this)">🩺 Nursing & In-Home</button>
                <button class="rc-filter-btn" onclick="rcSelectCategory('careers', this)">💼 Careers</button>
            </nav>
        </div>
    </header>

    <!-- 2. MAIN BLOG HUB CONTENT -->
    <div class="rc-blog-main-container">

        <main id="rc-main-content">
            
            <!-- SPOTLIGHT FEATURED GUIDE -->
            <section class="rc-spotlight-card rc-filterable-item" data-category="sil-housing" data-search-text="reactcorp commitment sil registration 2026 practice standards supported decision making safeguarding governance housing support agreements">
                <div class="rc-spotlight-inner">
                    <div class="rc-spotlight-tag-row">
                        <span class="rc-spotlight-badge">⭐ Featured Policy Spotlight</span>
                        <span class="rc-card-tag">🏡 SIL & Housing</span>
                        <span class="rc-spotlight-date">Latest NDIS Regulatory Update · 2026 Standards</span>
                    </div>
                    <h2 class="rc-spotlight-title">ReactCorp’s Commitment to Continue SIL Registration & NDIS Practice Standards</h2>
                    <p class="rc-spotlight-excerpt">Strengthening Quick, Quality, Quantity, and Independence in Supported Independent Living (SIL). From 1 July 2026, mandatory NDIS registration requires robust compliance across 4 core practice standards. Here is how ReactCorp is elevating participant rights and safeguarding.</p>
                    
                    <div class="rc-spotlight-key-points">
                        <div class="rc-key-point-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Supported Decision-Making
                        </div>
                        <div class="rc-key-point-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Zero Harm Safeguarding
                        </div>
                        <div class="rc-key-point-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Practice Governance & Audits
                        </div>
                        <div class="rc-key-point-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Separate Tenancy & Support
                        </div>
                    </div>

                    <div class="rc-spotlight-footer">
                        <div class="rc-author-meta">
                            <div class="rc-author-avatar-sm">RC</div>
                            <div class="rc-author-text">
                                <strong>ReactCorp Practice Governance Team</strong>
                                <span>Published · 8 min comprehensive read</span>
                            </div>
                        </div>
                        <button class="rc-btn-read-spotlight" onclick="rcOpenModal('modal-sil')">
                            Read Full SIL Commitment →
                        </button>
                    </div>
                </div>
            </section>

            <!-- SECTION TITLE -->
            <div class="rc-section-header">
                <h2 class="rc-section-title">
                    <span>📚</span> All Guides & Updates
                </h2>
                <span class="rc-section-count" id="rc-article-counter">Showing 11 Guides</span>
            </div>

            <!-- CARDS GRID -->
            <div class="rc-articles-grid" id="rc-articles-grid">

                <!-- 1. DYNAMIC WORDPRESS POSTS LOOP (POSTS ADDED IN WP ADMIN POST-NEW.PHP) -->
                <?php if ($dynamic_posts_query->have_posts()) : ?>
                    <?php while ($dynamic_posts_query->have_posts()) : $dynamic_posts_query->the_post(); 
                        $cats = get_the_category();
                        $cat_slug = !empty($cats) ? $cats[0]->slug : 'all';
                        $cat_name = !empty($cats) ? $cats[0]->name : 'NDIS Update';
                        $word_count = str_word_count(strip_tags(get_the_content()));
                        $read_time = ceil($word_count / 200);
                        if ($read_time < 1) $read_time = 3;
                        $search_string = strtolower(get_the_title() . ' ' . strip_tags(get_the_excerpt()) . ' ' . $cat_name);
                    ?>
                    <article class="rc-blog-card rc-filterable-item" data-category="<?php echo esc_attr($cat_slug); ?>" data-search-text="<?php echo esc_attr($search_string); ?>">
                        <div class="rc-card-thumbnail-wrap">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('medium_large', array('alt' => get_the_title())); ?>
                            <?php else : ?>
                                <div class="rc-card-illustration-overlay" title="<?php the_title_attribute(); ?>" aria-label="<?php the_title_attribute(); ?>">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" role="img" aria-label="<?php the_title_attribute(); ?>"><title><?php the_title_attribute(); ?></title><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="rc-card-body">
                            <div class="rc-card-category-row">
                                <span class="rc-card-tag"><?php echo esc_html($cat_name); ?></span>
                                <span class="rc-card-date"><?php echo get_the_date('M j, Y'); ?></span>
                            </div>
                            <h3 class="rc-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            <p class="rc-card-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 22, '...'); ?></p>
                            <div class="rc-card-footer">
                                <span class="rc-read-time-badge">⏱️ <?php echo $read_time; ?> min read</span>
                                <a href="<?php the_permalink(); ?>" class="rc-card-read-link">Read Post →</a>
                            </div>
                        </div>
                    </article>
                    <?php endwhile; wp_reset_postdata(); ?>
                <?php endif; ?>

                <!-- 2. PILLAR GUIDE CARDS (BUILT-IN RESOURCEFUL GUIDES) -->
                
                <!-- Pillar: Complete Participant Guide to SIL -->
                <article class="rc-blog-card rc-filterable-item" data-category="sil-housing" data-search-text="complete participant guide to supported independent living sil costs roster of care eligibility co-tenant matching sda ilo rights">
                    <div class="rc-card-thumbnail-wrap" style="background: linear-gradient(135deg, #022c22, #047857);">
                        <div class="rc-card-illustration-overlay" title="Supported Independent Living SIL Guide" aria-label="Supported Independent Living SIL Guide">
                            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" role="img" aria-label="Supported Independent Living SIL Guide"><title>Supported Independent Living SIL Guide</title><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        </div>
                    </div>
                    <div class="rc-card-body">
                        <div class="rc-card-category-row">
                            <span class="rc-card-tag">🏡 SIL & Housing</span>
                            <span class="rc-card-date">Participant Guide</span>
                        </div>
                        <h3 class="rc-card-title">The Complete Participant Guide to Supported Independent Living (SIL) Under the NDIS</h3>
                        <p class="rc-card-excerpt">Understand what SIL funding covers vs out-of-pocket costs, how the Roster of Care works, co-tenant matching, and your tenancy rights.</p>
                        <div class="rc-card-footer">
                            <span class="rc-read-time-badge">⏱️ 7 min read</span>
                            <button class="rc-card-read-link" onclick="rcOpenModal('modal-sil-guide')">Read Guide →</button>
                        </div>
                    </div>
                </article>

                <!-- Pillar 1: Registered Provider Guide -->
                <article class="rc-blog-card rc-filterable-item" data-category="compliance" data-search-text="choosing a registered ndis service provider in australia compliance quality safeguards commission agency managed">
                    <div class="rc-card-thumbnail-wrap" style="background: linear-gradient(135deg, #0f172a, #1e3a8a);">
                        <div class="rc-card-illustration-overlay" title="Registered NDIS Service Provider Australia" aria-label="Registered NDIS Service Provider Australia">
                            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" role="img" aria-label="Registered NDIS Service Provider Australia"><title>Registered NDIS Service Provider Australia</title><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                        </div>
                    </div>
                    <div class="rc-card-body">
                        <div class="rc-card-category-row">
                            <span class="rc-card-tag" style="background:#eff6ff; color:#1e40af;">🛡️ NDIS Compliance</span>
                            <span class="rc-card-date">Essential Guide</span>
                        </div>
                        <h3 class="rc-card-title">Complete Guide to Choosing a Registered NDIS Service Provider in Australia</h3>
                        <p class="rc-card-excerpt">Learn how registered providers are audited under the NDIS Quality and Safeguards Commission and why registration is essential for Agency-Managed participants.</p>
                        <div class="rc-card-footer">
                            <span class="rc-read-time-badge">⏱️ 5 min read</span>
                            <button class="rc-card-read-link" onclick="rcOpenModal('modal-p1')">Read Article →</button>
                        </div>
                    </div>
                </article>

                <!-- Pillar 2: Support Coordination -->
                <article class="rc-blog-card rc-filterable-item" data-category="coordination" data-search-text="level 2 level 3 specialist ndis support coordination sydney nsw social work funding optimization">
                    <div class="rc-card-thumbnail-wrap" style="background: linear-gradient(135deg, #3b0764, #7c3aed);">
                        <div class="rc-card-illustration-overlay" title="Specialist Support Coordination Sydney NSW" aria-label="Specialist Support Coordination Sydney NSW">
                            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" role="img" aria-label="Specialist Support Coordination Sydney NSW"><title>Specialist Support Coordination Sydney NSW</title><circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon></svg>
                        </div>
                    </div>
                    <div class="rc-card-body">
                        <div class="rc-card-category-row">
                            <span class="rc-card-tag" style="background:#f5f3ff; color:#6d28d9;">🧭 Support Coordination</span>
                            <span class="rc-card-date">Care Planning</span>
                        </div>
                        <h3 class="rc-card-title">Level 2 & Level 3 Specialist NDIS Support Coordination Sydney & NSW</h3>
                        <p class="rc-card-excerpt">Understand the difference between Level 2 Coordination of Supports and Level 3 Specialist Coordination delivered by qualified social workers.</p>
                        <div class="rc-card-footer">
                            <span class="rc-read-time-badge">⏱️ 6 min read</span>
                            <button class="rc-card-read-link" onclick="rcOpenModal('modal-p2')">Read Article →</button>
                        </div>
                    </div>
                </article>

                <!-- Pillar 3: 24/7 Crisis Support -->
                <article class="rc-blog-card rc-filterable-item" data-category="crisis" data-search-text="24/7 crisis support ndis emergency hotline urgent respite immediate intervention australia">
                    <div class="rc-card-thumbnail-wrap" style="background: linear-gradient(135deg, #450a0a, #dc2626);">
                        <div class="rc-card-illustration-overlay" title="24/7 Emergency Crisis Support NDIS" aria-label="24/7 Emergency Crisis Support NDIS">
                            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" role="img" aria-label="24/7 Emergency Crisis Support NDIS"><title>24/7 Emergency Crisis Support NDIS</title><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                        </div>
                    </div>
                    <div class="rc-card-body">
                        <div class="rc-card-category-row">
                            <span class="rc-card-tag" style="background:#fef2f2; color:#b91c1c;">🚨 24/7 Crisis Support</span>
                            <span class="rc-card-date">Rapid Response</span>
                        </div>
                        <h3 class="rc-card-title">24/7 Crisis Support NDIS Services for Immediate Urgent Care</h3>
                        <p class="rc-card-excerpt">How our round-the-clock emergency team deploys rapid respite and accommodation when informal carers fall ill or urgent care breakdowns occur.</p>
                        <div class="rc-card-footer">
                            <span class="rc-read-time-badge">⏱️ 4 min read</span>
                            <button class="rc-card-read-link" onclick="rcOpenModal('modal-p3')">Read Article →</button>
                        </div>
                    </div>
                </article>

                <!-- Pillar 4: Emergency Housing -->
                <article class="rc-blog-card rc-filterable-item" data-category="crisis" data-search-text="rapid emergency housing ndis provider immediate sta accommodation crisis respite">
                    <div class="rc-card-thumbnail-wrap" style="background: linear-gradient(135deg, #134e4a, #0d9488);">
                        <div class="rc-card-illustration-overlay" title="NDIS Emergency Housing and STA Placement" aria-label="NDIS Emergency Housing and STA Placement">
                            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" role="img" aria-label="NDIS Emergency Housing and STA Placement"><title>NDIS Emergency Housing and STA Placement</title><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><line x1="12" y1="11" x2="12" y2="17"></line><line x1="9" y1="14" x2="15" y2="14"></line></svg>
                        </div>
                    </div>
                    <div class="rc-card-body">
                        <div class="rc-card-category-row">
                            <span class="rc-card-tag" style="background:#f0fdfa; color:#0f766e;">🏠 Emergency Housing</span>
                            <span class="rc-card-date">Immediate Placement</span>
                        </div>
                        <h3 class="rc-card-title">Rapid Emergency Housing NDIS Provider & Immediate Accommodation</h3>
                        <p class="rc-card-excerpt">Step-by-step guidance on accessing NDIS-funded crisis respite and safe Short-Term Accommodation when facing sudden homelessness.</p>
                        <div class="rc-card-footer">
                            <span class="rc-read-time-badge">⏱️ 5 min read</span>
                            <button class="rc-card-read-link" onclick="rcOpenModal('modal-p4')">Read Article →</button>
                        </div>
                    </div>
                </article>

                <!-- Pillar 5: STA & Respite -->
                <article class="rc-blog-card rc-filterable-item" data-category="sil-housing" data-search-text="short term accommodation sta ndis respite care 14 days living skills">
                    <div class="rc-card-thumbnail-wrap" style="background: linear-gradient(135deg, #451a03, #d97706);">
                        <div class="rc-card-illustration-overlay" title="NDIS Short Term Accommodation STA Respite" aria-label="NDIS Short Term Accommodation STA Respite">
                            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" role="img" aria-label="NDIS Short Term Accommodation STA Respite"><title>NDIS Short Term Accommodation STA Respite</title><path d="M2 4v16"></path><path d="M2 8h18a2 2 0 0 1 2 2v10"></path><path d="M2 17h20"></path><path d="M6 8v9"></path></svg>
                        </div>
                    </div>
                    <div class="rc-card-body">
                        <div class="rc-card-category-row">
                            <span class="rc-card-tag" style="background:#fffbeb; color:#b45309;">🛌 Short Term Respite</span>
                            <span class="rc-card-date">Capacity Building</span>
                        </div>
                        <h3 class="rc-card-title">Short Term Accommodation STA NDIS & Respite Care Explained</h3>
                        <p class="rc-card-excerpt">Discover how STA funding provides up to 14 days of temporary supported housing, giving informal carers rest and building participant independence.</p>
                        <div class="rc-card-footer">
                            <span class="rc-read-time-badge">⏱️ 4 min read</span>
                            <button class="rc-card-read-link" onclick="rcOpenModal('modal-p5')">Read Article →</button>
                        </div>
                    </div>
                </article>

                <!-- Pillar 6: Domestic Violence Support -->
                <article class="rc-blog-card rc-filterable-item" data-category="crisis" data-search-text="trauma informed domestic violence support ndis safety planning relocation advocacy">
                    <div class="rc-card-thumbnail-wrap" style="background: linear-gradient(135deg, #4c0519, #e11d48);">
                        <div class="rc-card-illustration-overlay" title="Trauma Informed Domestic Violence Support NDIS" aria-label="Trauma Informed Domestic Violence Support NDIS">
                            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" role="img" aria-label="Trauma Informed Domestic Violence Support NDIS"><title>Trauma Informed Domestic Violence Support NDIS</title><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
                        </div>
                    </div>
                    <div class="rc-card-body">
                        <div class="rc-card-category-row">
                            <span class="rc-card-tag" style="background:#fff1f2; color:#be123c;">🛡️ Trauma & DV Support</span>
                            <span class="rc-card-date">Confidential Care</span>
                        </div>
                        <h3 class="rc-card-title">Trauma-Informed Domestic Violence Support NDIS Safety Planning</h3>
                        <p class="rc-card-excerpt">Confidential safety planning, emergency relocation assistance, and social work advocacy for NDIS participants escaping domestic violence.</p>
                        <div class="rc-card-footer">
                            <span class="rc-read-time-badge">⏱️ 5 min read</span>
                            <button class="rc-card-read-link" onclick="rcOpenModal('modal-p6')">Read Article →</button>
                        </div>
                    </div>
                </article>

                <!-- Pillar 7: Personal Care Nursing -->
                <article class="rc-blog-card rc-filterable-item" data-category="nursing" data-search-text="in home personal care nursing ndis complex daily living medication hygiene bowel care">
                    <div class="rc-card-thumbnail-wrap" style="background: linear-gradient(135deg, #164e63, #0891b2);">
                        <div class="rc-card-illustration-overlay" title="In Home Personal Care and Nursing NDIS" aria-label="In Home Personal Care and Nursing NDIS">
                            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" role="img" aria-label="In Home Personal Care and Nursing NDIS"><title>In Home Personal Care and Nursing NDIS</title><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                        </div>
                    </div>
                    <div class="rc-card-body">
                        <div class="rc-card-category-row">
                            <span class="rc-card-tag" style="background:#ecfeff; color:#0e7490;">🩺 Personal & Nursing</span>
                            <span class="rc-card-date">Clinical Care</span>
                        </div>
                        <h3 class="rc-card-title">In-Home Personal Care Nursing NDIS & Complex Daily Living Support</h3>
                        <p class="rc-card-excerpt">High-intensity daily support including medication management, hygiene, catheter and bowel care provided by qualified registered caregivers.</p>
                        <div class="rc-card-footer">
                            <span class="rc-read-time-badge">⏱️ 5 min read</span>
                            <button class="rc-card-read-link" onclick="rcOpenModal('modal-p7')">Read Article →</button>
                        </div>
                    </div>
                </article>

                <!-- Pillar 8: Psychosocial Recovery -->
                <article class="rc-blog-card rc-filterable-item" data-category="nursing" data-search-text="psychosocial recovery coaching ndis mental health capacity building resilience">
                    <div class="rc-card-thumbnail-wrap" style="background: linear-gradient(135deg, #2e1065, #7c3aed);">
                        <div class="rc-card-illustration-overlay" title="Psychosocial Recovery Coaching Mental Health NDIS" aria-label="Psychosocial Recovery Coaching Mental Health NDIS">
                            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" role="img" aria-label="Psychosocial Recovery Coaching Mental Health NDIS"><title>Psychosocial Recovery Coaching Mental Health NDIS</title><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                        </div>
                    </div>
                    <div class="rc-card-body">
                        <div class="rc-card-category-row">
                            <span class="rc-card-tag" style="background:#f5f3ff; color:#6d28d9;">🧠 Mental Health</span>
                            <span class="rc-card-date">Recovery Coaching</span>
                        </div>
                        <h3 class="rc-card-title">Psychosocial Recovery Coaching NDIS & Mental Health Support</h3>
                        <p class="rc-card-excerpt">How recovery coaches help participants with complex mental health challenges build autonomy, self-advocacy, and strong community ties.</p>
                        <div class="rc-card-footer">
                            <span class="rc-read-time-badge">⏱️ 4 min read</span>
                            <button class="rc-card-read-link" onclick="rcOpenModal('modal-p8')">Read Article →</button>
                        </div>
                    </div>
                </article>

                <!-- Pillar 9: Careers & Support Worker Jobs -->
                <article class="rc-blog-card rc-filterable-item" data-category="careers" data-search-text="apply online disability support worker jobs sydney nsw careers recruitment hourly rates">
                    <div class="rc-card-thumbnail-wrap" style="background: linear-gradient(135deg, #0f172a, #334155);">
                        <div class="rc-card-illustration-overlay" title="Disability Support Worker Careers Sydney NSW" aria-label="Disability Support Worker Careers Sydney NSW">
                            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" role="img" aria-label="Disability Support Worker Careers Sydney NSW"><title>Disability Support Worker Careers Sydney NSW</title><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                        </div>
                    </div>
                    <div class="rc-card-body">
                        <div class="rc-card-category-row">
                            <span class="rc-card-tag" style="background:#f1f5f9; color:#334155;">💼 Careers</span>
                            <span class="rc-card-date">Now Hiring</span>
                        </div>
                        <h3 class="rc-card-title">Apply Online for Disability Support Worker Jobs Sydney & Healthcare Careers</h3>
                        <p class="rc-card-excerpt">Competitive hourly rates ($34–$55/hr), comprehensive mentoring, and rewarding career paths for dedicated disability care workers.</p>
                        <div class="rc-card-footer">
                            <span class="rc-read-time-badge">⏱️ 4 min read</span>
                            <button class="rc-card-read-link" onclick="rcOpenModal('modal-p10')">Read Careers →</button>
                        </div>
                    </div>
                </article>

                <!-- NO MATCHES PLACEHOLDER -->
                <div id="rc-no-results-box" class="rc-no-results-box">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 1rem; color: #94a3b8;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <h3 style="font-size: 1.35rem; color: var(--rc-slate-900); margin-bottom: 0.5rem;">No matching articles found</h3>
                    <p>Try searching for a different term or select "All Topics" to browse our full NDIS knowledge library.</p>
                </div>

            </div>

            <!-- PAGINATION -->
            <?php if ($dynamic_posts_query->max_num_pages > 1) : ?>
                <div style="display:flex; justify-content:center; gap:8px; margin-top:2rem;">
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
            <div class="rc-sidebar-wrap">
                
                <!-- Category Nav Widget -->
                <div class="rc-sidebar-card">
                    <h3 class="rc-sidebar-card-title">
                        Browse by Topic
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                    </h3>
                    <ul class="rc-category-list-nav">
                        <li>
                            <button class="active" onclick="rcSelectCategory('all', this)">
                                <span>🌟 All Topics</span>
                                <span class="rc-category-count" id="rc-count-all">11</span>
                            </button>
                        </li>
                        <li>
                            <button onclick="rcSelectCategory('sil-housing', this)">
                                <span>🏡 SIL & Housing</span>
                                <span class="rc-category-count" id="rc-count-sil">3</span>
                            </button>
                        </li>
                        <li>
                            <button onclick="rcSelectCategory('compliance', this)">
                                <span>🛡️ NDIS Compliance</span>
                                <span class="rc-category-count" id="rc-count-comp">1</span>
                            </button>
                        </li>
                        <li>
                            <button onclick="rcSelectCategory('crisis', this)">
                                <span>🚨 24/7 Crisis & STA</span>
                                <span class="rc-category-count" id="rc-count-crisis">3</span>
                            </button>
                        </li>
                        <li>
                            <button onclick="rcSelectCategory('coordination', this)">
                                <span>🧭 Support Coordination</span>
                                <span class="rc-category-count" id="rc-count-coord">1</span>
                            </button>
                        </li>
                        <li>
                            <button onclick="rcSelectCategory('nursing', this)">
                                <span>🩺 Nursing & In-Home</span>
                                <span class="rc-category-count" id="rc-count-nursing">2</span>
                            </button>
                        </li>
                        <li>
                            <button onclick="rcSelectCategory('careers', this)">
                                <span>💼 Careers</span>
                                <span class="rc-category-count" id="rc-count-careers">1</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- 24/7 Crisis Callout -->
                <div class="rc-sidebar-card rc-crisis-sidebar-widget">
                    <span class="rc-crisis-badge">Emergency NDIS Response</span>
                    <h3 class="rc-crisis-title">24/7 Crisis Support Hotline</h3>
                    <p style="font-size: 0.925rem; line-height: 1.6; color: #f5d0fe;">Immediate placement and urgent care when informal carers fall ill or emergency housing is required.</p>
                    <a href="tel:0422069482" class="rc-crisis-phone-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        0422 069 482
                    </a>
                    <div style="text-align: center; margin-top: 8px; font-size: 0.85rem; color: #f0abfc;">
                        Landline: <a href="tel:0283861433" style="color: #ffffff; text-decoration: underline;">02 8386 1433</a>
                    </div>
                </div>

                <!-- NDIS Referral Form Widget -->
                <div class="rc-sidebar-card">
                    <h3 class="rc-sidebar-card-title">
                        <span>📋</span> Online Referral
                    </h3>
                    <p style="font-size: 0.925rem; line-height: 1.6; color: var(--rc-slate-600); margin-bottom: 1.25rem;">Direct intake for Support Coordination, SIL accommodation, personal care, and emergency respite.</p>
                    <a href="https://zfrmz.com/sIh6uDqI2c9PaujmOoTR" target="_blank" rel="noopener noreferrer" style="display:block; width:100%; text-align:center; background:linear-gradient(135deg, var(--rc-purple) 0%, var(--rc-purple-light) 100%); color:#ffffff; font-weight:700; font-size:0.95rem; padding:12px 18px; border-radius:8px; text-decoration:none;">
                        Submit Online Referral →
                    </a>
                </div>

            </div>
        </aside>

    </div>

    <!-- =========================================================================
         ACCESSIBLE FULL ARTICLE READER MODALS
         ========================================================================= -->
    
    <!-- Modal: SIL Registration Commitment -->
    <div id="modal-sil" class="rc-modal-overlay" onclick="rcCloseModalOnOverlay(event, 'modal-sil')">
        <div class="rc-modal-container">
            <button class="rc-modal-close-btn" onclick="rcCloseModal('modal-sil')">✕</button>
            <div class="rc-modal-article-body">
                <span class="rc-card-tag">🏡 SIL & Housing Policy</span>
                <h1 style="font-family:'Outfit',sans-serif; color:var(--rc-slate-900); margin:0.75rem 0 1rem;">ReactCorp’s Commitment to Continue SIL Registration</h1>
                <p class="lead" style="font-size:1.15rem; font-weight:600; color:var(--rc-purple);">Strengthening Quick, Quality, Quantity, and Independence in Supported Independent Living</p>
                <hr style="margin:1.5rem 0; border:0; border-top:1px solid #e2e8f0;">
                
                <p>At <strong>ReactCorp Disability Services</strong>, we believe that every person with disability has the right to live with dignity, safety, independence and genuine choice and control over their own life.</p>
                <p>Supported Independent Living (SIL) is more than providing assistance with daily activities. A person's SIL home is their home first. It is a place where they should feel safe, respected, listened to and empowered to make decisions about how they live.</p>
                <p>The introduction of <strong>mandatory NDIS registration for SIL providers from 1 July 2026</strong>, together with the new SIL-specific NDIS Practice Standards, represents an important change for the disability sector. The new standards place a strong focus on participant rights, safety, quality of support, safeguarding and good practice within the home.</p>

                <h2>The 4 SIL Practice Standards at ReactCorp</h2>
                <ol>
                    <li><strong>Supported Decision-Making:</strong> Participants make decisions about their own daily routines, menus, housemates, and supports.</li>
                    <li><strong>Safeguarding & Zero Tolerance:</strong> Proactive identification and prevention of abuse, neglect, exploitation, or discrimination.</li>
                    <li><strong>Practice Governance:</strong> Comprehensive clinical oversight, continuous quality improvement, and active auditing.</li>
                    <li><strong>Agreements about Tenancy & Housing:</strong> Complete separation between tenancy management and disability support providers, guaranteeing tenant rights.</li>
                </ol>

                <blockquote>"Your home. Your choices. Your independence. At ReactCorp, our commitment is to provide support that enables autonomy without compromise."</blockquote>
                
                <h3>Need SIL Housing Assistance?</h3>
                <p>Contact our SIL intake team at <strong>0422 069 482</strong> or email <strong>info@reactcorpdisability.com.au</strong>.</p>
            </div>
        </div>
    </div>

    <!-- Modal: Complete Participant Guide to SIL -->
    <div id="modal-sil-guide" class="rc-modal-overlay" onclick="rcCloseModalOnOverlay(event, 'modal-sil-guide')">
        <div class="rc-modal-container">
            <button class="rc-modal-close-btn" onclick="rcCloseModal('modal-sil-guide')">✕</button>
            <div class="rc-modal-article-body">
                <span class="rc-card-tag">🏡 SIL & Housing</span>
                <h1 style="font-family:'Outfit',sans-serif; color:var(--rc-slate-900); margin:0.75rem 0 1rem;">The Complete Participant Guide to Supported Independent Living (SIL) Under the NDIS</h1>
                
                <p>Supported Independent Living (SIL) is an NDIS-funded support designed to help people with disabilities live as independently as possible while receiving help with daily tasks.</p>
                
                <h2>1. What SIL Covers vs Out-of-Pocket Living Costs</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Covered by NDIS SIL Funding</th>
                            <th>Participant Out-of-Pocket (Rent & Board)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>24/7 support worker assistance</td>
                            <td>Rental payments for the house/unit</td>
                        </tr>
                        <tr>
                            <td>Personal care (showering, dressing)</td>
                            <td>Groceries, food, and personal meals</td>
                        </tr>
                        <tr>
                            <td>Medication administration & prompts</td>
                            <td>Utilities (electricity, gas, water, internet)</td>
                        </tr>
                        <tr>
                            <td>Meal preparation and cooking support</td>
                            <td>Personal entertainment, clothing, hobbies</td>
                        </tr>
                    </tbody>
                </table>

                <h2>2. Understanding SIL vs SDA vs ILO</h2>
                <ul>
                    <li><strong>SIL (Supported Independent Living):</strong> Covers the daily support staff who help you in your home.</li>
                    <li><strong>SDA (Specialist Disability Accommodation):</strong> Covers the physical, purpose-built bricks-and-mortar housing (e.g. wheelchair accessible design).</li>
                    <li><strong>ILO (Individualised Living Options):</strong> Alternative flexible living models like host arrangements or housemates.</li>
                </ul>

                <h2>3. How the Roster of Care (RoC) Works</h2>
                <p>The Roster of Care is a detailed weekly schedule documenting how many support hours a participant requires across 1:1 direct care and shared ratios (e.g. 1:2 or 1:3). ReactCorp collaborates closely with participants and families to ensure the RoC accurately reflects every support need.</p>
            </div>
        </div>
    </div>

    <!-- Modals for Pillars 1-10 -->
    <div id="modal-p1" class="rc-modal-overlay" onclick="rcCloseModalOnOverlay(event, 'modal-p1')">
        <div class="rc-modal-container">
            <button class="rc-modal-close-btn" onclick="rcCloseModal('modal-p1')">✕</button>
            <div class="rc-modal-article-body">
                <span class="rc-card-tag">🛡️ NDIS Compliance</span>
                <h2>Complete Guide to Choosing a Registered NDIS Service Provider in Australia</h2>
                <p>Under the NDIS Quality and Safeguards Commission, Registered Providers undergo rigorous third-party audits, mandatory worker screening, and strict incident reporting. ReactCorp Disability Services (#4050064716) is fully registered and able to support Agency-Managed, Plan-Managed, and Self-Managed participants.</p>
            </div>
        </div>
    </div>

    <div id="modal-p2" class="rc-modal-overlay" onclick="rcCloseModalOnOverlay(event, 'modal-p2')">
        <div class="rc-modal-container">
            <button class="rc-modal-close-btn" onclick="rcCloseModal('modal-p2')">✕</button>
            <div class="rc-modal-article-body">
                <span class="rc-card-tag">🧭 Support Coordination</span>
                <h2>Level 2 & Level 3 Specialist NDIS Support Coordination Sydney & NSW</h2>
                <p>Support Coordination helps participants maximize their NDIS funding, connect with healthcare providers, and resolve crises. Level 3 Specialist Support Coordination is delivered by qualified social workers for complex behavioral, justice, or health intersections.</p>
            </div>
        </div>
    </div>

    <div id="modal-p3" class="rc-modal-overlay" onclick="rcCloseModalOnOverlay(event, 'modal-p3')">
        <div class="rc-modal-container">
            <button class="rc-modal-close-btn" onclick="rcCloseModal('modal-p3')">✕</button>
            <div class="rc-modal-article-body">
                <span class="rc-card-tag">🚨 24/7 Crisis Support</span>
                <h2>24/7 Crisis Support NDIS Services for Immediate Urgent Care</h2>
                <p>ReactCorp's emergency on-call team responds 24/7 to urgent care breakdowns, hospitalization discharges, and informal carer illnesses across NSW, VIC, ACT, SA, and TAS. Call <strong>0422 069 482</strong> anytime.</p>
            </div>
        </div>
    </div>

    <div id="modal-p4" class="rc-modal-overlay" onclick="rcCloseModalOnOverlay(event, 'modal-p4')">
        <div class="rc-modal-container">
            <button class="rc-modal-close-btn" onclick="rcCloseModal('modal-p4')">✕</button>
            <div class="rc-modal-article-body">
                <span class="rc-card-tag">🏠 Emergency Housing</span>
                <h2>Rapid Emergency Housing NDIS Provider & Immediate Accommodation</h2>
                <p>When unexpected homelessness or family emergencies arise, ReactCorp provides rapid placement in safe, fully supported Short-Term Accommodation (STA) properties with around-the-clock support staff.</p>
            </div>
        </div>
    </div>

    <div id="modal-p5" class="rc-modal-overlay" onclick="rcCloseModalOnOverlay(event, 'modal-p5')">
        <div class="rc-modal-container">
            <button class="rc-modal-close-btn" onclick="rcCloseModal('modal-p5')">✕</button>
            <div class="rc-modal-article-body">
                <span class="rc-card-tag">🛌 Short Term Respite</span>
                <h2>Short Term Accommodation STA NDIS & Respite Care Explained</h2>
                <p>NDIS STA funding covers up to 14 days of temporary supported accommodation. It provides essential respite for family carers while building participant life skills, social connections, and daily autonomy.</p>
            </div>
        </div>
    </div>

    <div id="modal-p6" class="rc-modal-overlay" onclick="rcCloseModalOnOverlay(event, 'modal-p6')">
        <div class="rc-modal-container">
            <button class="rc-modal-close-btn" onclick="rcCloseModal('modal-p6')">✕</button>
            <div class="rc-modal-article-body">
                <span class="rc-card-tag">🛡️ Trauma & DV Support</span>
                <h2>Trauma-Informed Domestic Violence Support NDIS Safety Planning</h2>
                <p>Confidential safety planning, emergency relocation assistance, and social work advocacy for NDIS participants escaping domestic violence or coercive environments.</p>
            </div>
        </div>
    </div>

    <div id="modal-p7" class="rc-modal-overlay" onclick="rcCloseModalOnOverlay(event, 'modal-p7')">
        <div class="rc-modal-container">
            <button class="rc-modal-close-btn" onclick="rcCloseModal('modal-p7')">✕</button>
            <div class="rc-modal-article-body">
                <span class="rc-card-tag">🩺 Personal & Nursing</span>
                <h2>In-Home Personal Care Nursing NDIS & Complex Daily Living Support</h2>
                <p>High-intensity clinical care delivered with dignity: subcutaneous injections, enteral feeding (PEG), catheter and bowel management, and medication administration overseen by qualified nurses.</p>
            </div>
        </div>
    </div>

    <div id="modal-p8" class="rc-modal-overlay" onclick="rcCloseModalOnOverlay(event, 'modal-p8')">
        <div class="rc-modal-container">
            <button class="rc-modal-close-btn" onclick="rcCloseModal('modal-p8')">✕</button>
            <div class="rc-modal-article-body">
                <span class="rc-card-tag">🧠 Mental Health</span>
                <h2>Psychosocial Recovery Coaching NDIS & Mental Health Support</h2>
                <p>Specialized recovery coaching for participants with mental health conditions. We collaborate with psychologists and clinical teams to build motivation, emotional regulation, and community participation.</p>
            </div>
        </div>
    </div>

    <div id="modal-p10" class="rc-modal-overlay" onclick="rcCloseModalOnOverlay(event, 'modal-p10')">
        <div class="rc-modal-container">
            <button class="rc-modal-close-btn" onclick="rcCloseModal('modal-p10')">✕</button>
            <div class="rc-modal-article-body">
                <span class="rc-card-tag">💼 Careers</span>
                <h2>Apply Online for Disability Support Worker Jobs Sydney & Healthcare Careers</h2>
                <p>Join the ReactCorp Disability Services care family. We offer competitive SCHADS Award rates ($34–$55/hr), flexible scheduling, comprehensive induction, and ongoing professional development across NSW.</p>
                <p>Submit your resume directly to <strong>info@reactcorpdisability.com.au</strong> or call <strong>0422 069 482</strong>.</p>
            </div>
        </div>
    </div>

</div>

<!-- INTERACTIVE FILTERING & MODAL SCRIPT -->
<script>
var rcActiveCategory = 'all';

function rcSelectCategory(cat, btn) {
    rcActiveCategory = cat;
    
    // Update active state on hero filter buttons
    var heroBtns = document.querySelectorAll('.rc-filter-btn');
    heroBtns.forEach(function(b) { b.classList.remove('active'); });
    if (btn) btn.classList.add('active');

    // Update active state on sidebar list
    var sidebarBtns = document.querySelectorAll('.rc-category-list-nav button');
    sidebarBtns.forEach(function(sb) { sb.classList.remove('active'); });

    rcFilterContent();
}

function rcFilterContent() {
    var searchInput = document.getElementById('rc-search-input');
    var query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    var items = document.querySelectorAll('.rc-filterable-item');
    var visibleCount = 0;

    items.forEach(function(item) {
        var itemCat = item.getAttribute('data-category') || '';
        var itemText = item.getAttribute('data-search-text') || '';

        var matchesCat = (rcActiveCategory === 'all' || itemCat.indexOf(rcActiveCategory) !== -1);
        var matchesSearch = (!query || itemText.indexOf(query) !== -1 || item.innerText.toLowerCase().indexOf(query) !== -1);

        if (matchesCat && matchesSearch) {
            item.style.display = '';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    var counter = document.getElementById('rc-article-counter');
    if (counter) {
        counter.textContent = 'Showing ' + visibleCount + ' ' + (visibleCount === 1 ? 'Guide' : 'Guides');
    }

    var noResults = document.getElementById('rc-no-results-box');
    if (noResults) {
        noResults.style.display = (visibleCount === 0) ? 'block' : 'none';
    }
}

function rcOpenModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function rcCloseModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function rcCloseModalOnOverlay(event, modalId) {
    if (event.target && event.target.id === modalId) {
        rcCloseModal(modalId);
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        var modals = document.querySelectorAll('.rc-modal-overlay');
        modals.forEach(function(m) { m.style.display = 'none'; });
        document.body.style.overflow = '';
    }
});
</script>

<?php get_footer(); ?>

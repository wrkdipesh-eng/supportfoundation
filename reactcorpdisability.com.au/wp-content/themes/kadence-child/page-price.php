<?php
/**
 * Template Name: Price Page Template
 * Redesigned Professional NDIS Price Guide & Information Page for ReactCorp Disability
 * Optimized for Rank #1 Google Keywords (NDIS Price Guide Compliant Provider Sydney)
 */

get_header();
?>

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">

<style>
:root {
    --rc-purple: #80387d;
    --rc-purple-dark: #581c87;
    --rc-purple-light: #faf5ff;
    --rc-pink: #f4acb7;
    --rc-dark: #0f172a;
    --rc-slate: #475569;
    --rc-slate-light: #64748b;
    --rc-border: #e2e8f0;
    --rc-bg: #f8fafc;
    --rc-white: #ffffff;
}

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    color: var(--rc-dark);
    background-color: var(--rc-bg);
    margin: 0;
    padding: 0;
    line-height: 1.6;
    overflow-x: hidden;
}

.rc-container {
    max-width: 1240px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

/* Page Hero Section */
.rc-page-hero {
    background: linear-gradient(135deg, #1e1b4b 0%, #4c1d95 50%, #80387d 100%);
    color: #ffffff !important;
    padding: 4.5rem 0 5rem 0;
    text-align: center;
}

.rc-hero-tag {
    display: inline-block;
    background: rgba(240, 171, 252, 0.2);
    border: 1px solid rgba(240, 171, 252, 0.35);
    color: #f0abfc !important;
    font-size: 0.85rem;
    font-weight: 800;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    padding: 0.4rem 1.1rem;
    border-radius: 50px;
    margin-bottom: 1.25rem;
}

.rc-hero-h1, .rc-page-hero h1 {
    font-family: 'Space Grotesk', sans-serif !important;
    font-size: 3rem !important;
    font-weight: 800 !important;
    margin: 0 0 1.25rem 0 !important;
    letter-spacing: -0.02em !important;
    color: #ffffff !important;
}

.rc-hero-p, .rc-page-hero p {
    font-size: 1.15rem !important;
    color: #f1f5f9 !important;
    max-width: 720px;
    margin: 0 auto 2.25rem auto !important;
    line-height: 1.7 !important;
}

.rc-hero-btn-group {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.rc-btn-white {
    background: #ffffff;
    color: #80387d !important;
    padding: 0.85rem 1.75rem;
    border-radius: 12px;
    font-weight: 800;
    font-size: 0.98rem;
    text-decoration: none;
    box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    transition: all 0.25s ease;
}

.rc-btn-white:hover {
    transform: translateY(-2px);
    background: #fdf4ff;
}

.rc-btn-glass {
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff !important;
    border: 1px solid rgba(255, 255, 255, 0.3);
    padding: 0.85rem 1.75rem;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.98rem;
    text-decoration: none;
    backdrop-filter: blur(10px);
    transition: all 0.25s ease;
}

.rc-btn-glass:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-2px);
}

/* Core Principles Section */
.rc-principles-sec {
    padding: 4.5rem 0;
}

.rc-principles-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
}

.rc-principle-card {
    background: #ffffff;
    border: 1px solid var(--rc-border);
    border-radius: 18px;
    padding: 2.25rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    transition: all 0.3s ease;
}

.rc-principle-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(128, 56, 125, 0.1);
    border-color: #80387d;
}

.rc-principle-icon {
    width: 56px;
    height: 56px;
    background: #fdf4ff;
    border: 1px solid rgba(128, 56, 125, 0.15);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    margin-bottom: 1.25rem;
}

.rc-principle-h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--rc-dark);
    margin: 0 0 0.75rem 0;
}

.rc-principle-p {
    font-size: 0.96rem;
    color: var(--rc-slate);
    line-height: 1.65;
    margin: 0;
}

/* Sample Price Categories Grid */
.rc-price-cats-sec {
    background: #fdf4ff;
    padding: 5rem 0;
}

.rc-sec-header {
    text-align: center;
    max-width: 720px;
    margin: 0 auto 3.5rem auto;
}

.rc-sec-tag {
    text-transform: uppercase;
    font-size: 0.85rem;
    font-weight: 800;
    letter-spacing: 1.5px;
    color: var(--rc-purple);
    margin-bottom: 0.5rem;
    display: block;
}

.rc-sec-h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2.3rem;
    font-weight: 700;
    color: var(--rc-dark);
    margin: 0 0 0.75rem 0;
}

.rc-sec-p {
    font-size: 1.05rem;
    color: var(--rc-slate);
    margin: 0;
}

.rc-price-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
}

.rc-price-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 2.25rem;
    border: 1px solid rgba(128, 56, 125, 0.12);
    box-shadow: 0 8px 25px rgba(0,0,0,0.03);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.rc-price-card-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.rc-price-badge {
    background: #80387d;
    color: #ffffff;
    font-weight: 800;
    font-size: 0.78rem;
    padding: 0.25rem 0.6rem;
    border-radius: 6px;
}

.rc-price-card-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--rc-dark);
    margin: 0;
}

.rc-price-card-desc {
    font-size: 0.98rem;
    color: var(--rc-slate);
    line-height: 1.65;
    margin-bottom: 1.25rem;
}

.rc-price-rate-box {
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 10px;
    padding: 0.9rem 1.1rem;
    font-size: 0.9rem;
    color: #334155;
    font-weight: 600;
}

/* Official NDIS Price Guide Link Box */
.rc-ndis-guide-box {
    background: linear-gradient(135deg, #80387d 0%, #581c87 100%);
    color: #ffffff !important;
    border-radius: 24px;
    padding: 3.5rem 2.5rem;
    text-align: center;
    margin-top: 4rem;
    box-shadow: 0 15px 35px rgba(128, 56, 125, 0.2);
}

.rc-guide-h2, .rc-ndis-guide-box h2 {
    font-family: 'Space Grotesk', sans-serif !important;
    font-size: 2.2rem !important;
    font-weight: 700 !important;
    margin: 0 0 1rem 0 !important;
    color: #ffffff !important;
}

.rc-guide-p, .rc-ndis-guide-box p {
    font-size: 1.1rem !important;
    color: #f0abfc !important;
    max-width: 680px;
    margin: 0 auto 2rem auto !important;
    line-height: 1.6 !important;
}

/* NDIS Blog / Reform Update Section */
.rc-blog-sec {
    padding: 5rem 0;
}

.rc-blog-card {
    background: #ffffff;
    border-radius: 24px;
    border: 1px solid var(--rc-border);
    box-shadow: 0 10px 30px rgba(0,0,0,0.04);
    padding: 3rem;
    max-width: 920px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
    position: relative;
    overflow: hidden;
}

.rc-blog-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 6px;
    height: 100%;
    background: linear-gradient(180deg, #80387d 0%, #f0abfc 100%);
}

.rc-blog-tag-wrap {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.rc-blog-tag {
    background: #fdf4ff;
    color: #80387d;
    font-size: 0.8rem;
    font-weight: 800;
    text-transform: uppercase;
    padding: 0.35rem 0.85rem;
    border-radius: 50px;
    letter-spacing: 0.5px;
}

.rc-blog-date {
    font-size: 0.88rem;
    color: var(--rc-slate-light);
    font-weight: 600;
}

.rc-blog-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.85rem;
    font-weight: 700;
    color: var(--rc-dark);
    margin: 0;
    line-height: 1.3;
}

.rc-blog-excerpt {
    font-size: 1.05rem;
    color: var(--rc-slate);
    line-height: 1.7;
    margin: 0;
}

.rc-blog-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #80387d;
    font-weight: 800;
    font-size: 1rem;
    text-decoration: none;
    transition: gap 0.2s ease;
}

.rc-blog-btn:hover {
    gap: 0.75rem;
}

@media (max-width: 1024px) {
    .rc-principles-grid { grid-template-columns: 1fr; }
    .rc-price-grid { grid-template-columns: 1fr; }
}
</style>

<main class="rc-page">

    <!-- PRICE HERO BANNER WITH TARGET KEYWORDS -->
    <section class="rc-page-hero">
        <div class="rc-container">
            <span class="rc-hero-tag" style="color:#f0abfc !important;">NDIS Price Guide Compliant Provider Sydney</span>
            <h1 class="rc-hero-h1" style="color:#ffffff !important;">NDIS Pricing & Plan Support Sydney</h1>
            <p class="rc-hero-p" style="color:#f1f5f9 !important;">
                ReactCorp strictly adheres to the official <strong>NDIS Pricing Arrangements and Price Limits in Roselands & Sydney NSW</strong>. Our pricing is transparent, fair, zero hidden fees, and fully aligned with NDIA government standards to ensure you get maximum value from your plan budget.
            </p>
            <div class="rc-hero-btn-group">
                <a href="tel:0422069482" class="rc-btn-white" title="Discuss Your NDIS Plan Funding Roselands Sydney">
                    📞 Discuss Your Funding (0422 069 482)
                </a>
                <a href="https://www.ndis.gov.au/providers/pricing-arrangements" target="_blank" rel="noopener" class="rc-btn-glass" title="Official NDIS Price Guide Link">
                    📄 Official NDIS Price Guide
                </a>
            </div>
        </div>
    </section>

    <!-- CORE PRICING PRINCIPLES -->
    <section class="rc-principles-sec">
        <div class="rc-container">
            <div class="rc-principles-grid">
                <!-- Card 1 -->
                <div class="rc-principle-card">
                    <div class="rc-principle-icon">🏛️</div>
                    <h3 class="rc-principle-h3">100% NDIS Compliant</h3>
                    <p class="rc-principle-p">We charge strictly in accordance with the most recent NDIS Price Guide set by the NDIA government authority across Sydney.</p>
                </div>

                <!-- Card 2 -->
                <div class="rc-principle-card">
                    <div class="rc-principle-icon">🔍</div>
                    <h3 class="rc-principle-h3">Zero Hidden Fees</h3>
                    <p class="rc-principle-p">No unexpected administration markups or surprise costs. Clear, itemized invoicing for total peace of mind.</p>
                </div>

                <!-- Card 3 -->
                <div class="rc-principle-card">
                    <div class="rc-principle-icon">💡</div>
                    <h3 class="rc-principle-h3">Maximized Plan Value</h3>
                    <p class="rc-principle-p">Our competitive support rates ensure you receive maximum support hours and maximum value from your approved NDIS budget.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- SAMPLE PRICE CATEGORIES -->
    <section class="rc-price-cats-sec">
        <div class="rc-container">
            <div class="rc-sec-header">
                <span class="rc-sec-tag">Pricing Guide Overview</span>
                <h2 class="rc-sec-h2">Sample Support Price Categories (0132, 0115, 0107)</h2>
                <p class="rc-sec-p">Rates are calculated transparently based on your individual support needs, time of service, and NDIS price caps in Sydney:</p>
            </div>

            <div class="rc-price-grid">
                <!-- Category 1 -->
                <div class="rc-price-card">
                    <div>
                        <div class="rc-price-card-header">
                            <span class="rc-price-badge">0132</span>
                            <h3 class="rc-price-card-title">Support Coordination Level 1, 2 & 3</h3>
                        </div>
                        <p class="rc-price-card-desc">Comprehensive support coordination to connect you with providers and optimize your NDIS plan setup in Roselands & Sydney.</p>
                    </div>
                    <div class="rc-price-rate-box">
                        ⚙️ Standard Hourly Rate as per current NDIS Price Limits
                    </div>
                </div>

                <!-- Category 2 -->
                <div class="rc-price-card">
                    <div>
                        <div class="rc-price-card-header">
                            <span class="rc-price-badge">0107</span>
                            <h3 class="rc-price-card-title">Daily Personal Activities</h3>
                        </div>
                        <p class="rc-price-card-desc">Personal care, hygiene, dressing, and daily living assistance tailored to your personal goals in NSW.</p>
                    </div>
                    <div class="rc-price-rate-box">
                        📅 Tiered weekday, evening, weekend & public holiday rates
                    </div>
                </div>

                <!-- Category 3 -->
                <div class="rc-price-card">
                    <div>
                        <div class="rc-price-card-header">
                            <span class="rc-price-badge">0115</span>
                            <h3 class="rc-price-card-title">SIL Supports (Shared Living Accommodation)</h3>
                        </div>
                        <p class="rc-price-card-desc">Supported Independent Living with 24/7 care support in shared or individual accommodation settings in Sydney.</p>
                    </div>
                    <div class="rc-price-rate-box">
                        🏠 Charged according to agreed Roster of Care & approved NDIS funding
                    </div>
                </div>

                <!-- Category 4 -->
                <div class="rc-price-card">
                    <div>
                        <div class="rc-price-card-header">
                            <span class="rc-price-badge">0116 / 0125</span>
                            <h3 class="rc-price-card-title">Community Participation</h3>
                        </div>
                        <p class="rc-price-card-desc">Social engagement, skill building, community outings, and group participation support in Roselands.</p>
                    </div>
                    <div class="rc-price-rate-box">
                        🎉 Hourly support rate as per official NDIS price guide
                    </div>
                </div>
            </div>

            <!-- NDIS Official Price Guide Download CTA Box -->
            <div class="rc-ndis-guide-box">
                <h2 class="rc-guide-h2" style="color:#ffffff !important;">Access the Official NDIS Price Guide</h2>
                <p class="rc-guide-p" style="color:#f0abfc !important;">Participants can access the most recent NDIS Pricing Arrangements directly from the official NDIS portal. We are always happy to discuss pricing details and help you understand how your funding can be best utilized.</p>
                <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
                    <a href="https://www.ndis.gov.au/providers/pricing-arrangements" target="_blank" rel="noopener" class="rc-btn-white">
                        🌐 View Latest NDIS Price Guide Online
                    </a>
                    <a href="tel:0422069482" class="rc-btn-glass">
                        📞 Free Funding Consultation (0422 069 482)
                    </a>
                </div>
            </div>

        </div>
    </section>

    <!-- NDIS GOVERNMENT REFORM UPDATE (BLOG SECTION) -->
    <section class="rc-blog-sec">
        <div class="rc-container">
            <div class="rc-sec-header" style="margin-bottom:2.5rem;">
                <span class="rc-sec-tag">NDIS News & Updates</span>
                <h2 class="rc-sec-h2">Latest Government Announcements</h2>
            </div>

            <div class="rc-blog-card">
                <div class="rc-blog-tag-wrap">
                    <span class="rc-blog-tag">NDIS Reform Update</span>
                    <span class="rc-blog-date">March 5, 2026</span>
                </div>
                <h3 class="rc-blog-title">Important update from the Australian Government about the future of NDIS planning.</h3>
                <p class="rc-blog-excerpt">
                    A new approach, called new framework planning, will gradually roll out from mid 2026. Initially, only a small number of participants will move to the new system, with changes introduced progressively over several years. Many people won't notice any immediate difference, and current NDIS plans will remain fully in place until each participant transitions.
                </p>
                <div>
                    <a href="#" class="rc-blog-btn">Read Full Article & Updates →</a>
                </div>
            </div>
        </div>
    </section>

</main>

<?php
get_footer();

<?php
/**
 * Clean, Sleek & Professional Homepage Template for ReactCorp Disability
 * Optimized for Rank #1 Google Keywords (NDIS Provider Roselands NSW, Support Coordination Sydney, SIL Accommodation)
 */

get_header();
?>

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">

<style>
/* Clean, Sleek & Professional Design System for ReactCorp Disability */
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

/* Official Uploaded ReactCorp Logo Image */
.rc-logo-img-link {
    display: inline-flex;
    align-items: center;
    text-decoration: none;
}

.rc-logo-img {
    height: 52px;
    width: auto;
    display: block;
    border-radius: 6px;
}

/* Clean Modern Hero Section */
.rc-hero-sec {
    background: linear-gradient(135deg, #1e1b4b 0%, #4c1d95 40%, #80387d 100%);
    color: #ffffff !important;
    padding: 4.5rem 0 5.5rem 0;
    position: relative;
}

.rc-hero-grid {
    display: grid;
    grid-template-columns: 1.1fr 0.9fr;
    gap: 3.5rem;
    align-items: center;
}

.rc-hero-title, .rc-hero-sec h1 {
    font-family: 'Space Grotesk', sans-serif !important;
    font-size: 3.25rem !important;
    font-weight: 800 !important;
    line-height: 1.15 !important;
    margin-bottom: 1.25rem !important;
    letter-spacing: -0.02em !important;
    color: #ffffff !important;
}

.rc-hero-title span, .rc-hero-sec h1 span {
    color: #f0abfc !important;
}

.rc-hero-sub, .rc-hero-sec p {
    font-size: 1.15rem !important;
    color: #f1f5f9 !important;
    line-height: 1.7 !important;
    margin-bottom: 2.25rem !important;
    max-width: 580px;
}

.rc-btn-group {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.rc-btn-primary {
    background: #ffffff;
    color: #80387d !important;
    padding: 0.9rem 1.8rem;
    border-radius: 12px;
    font-weight: 800;
    font-size: 1rem;
    text-decoration: none;
    box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    transition: all 0.25s ease;
}

.rc-btn-primary:hover {
    transform: translateY(-3px);
    background: #fdf4ff;
}

.rc-btn-secondary {
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff !important;
    border: 1px solid rgba(255, 255, 255, 0.3);
    padding: 0.9rem 1.8rem;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1rem;
    text-decoration: none;
    backdrop-filter: blur(10px);
    transition: all 0.25s ease;
}

.rc-btn-secondary:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-3px);
}

/* NDIS Groups Glass Card */
.rc-groups-hero-card {
    background: rgba(15, 23, 42, 0.7);
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 20px;
    padding: 2rem;
    backdrop-filter: blur(15px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}

.rc-groups-card-header {
    margin-bottom: 1.25rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    padding-bottom: 1rem;
}

.rc-groups-card-title, .rc-groups-card-header h2 {
    font-family: 'Space Grotesk', sans-serif !important;
    font-size: 1.3rem !important;
    font-weight: 700 !important;
    color: #f0abfc !important;
    margin: 0 !important;
}

.rc-groups-card-sub {
    font-size: 0.85rem;
    color: #cbd5e1;
    margin-top: 0.25rem;
}

.rc-groups-list {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    max-height: 360px;
    overflow-y: auto;
    padding-right: 0.4rem;
}

.rc-groups-list::-webkit-scrollbar {
    width: 5px;
}
.rc-groups-list::-webkit-scrollbar-thumb {
    background: rgba(240, 171, 252, 0.4);
    border-radius: 10px;
}

.rc-group-item {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    padding: 0.65rem 0.9rem;
    font-size: 0.88rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.2s ease;
    color: #ffffff;
}

.rc-group-item:hover {
    background: rgba(240, 171, 252, 0.18);
    transform: translateX(4px);
}

.rc-group-code {
    background: #80387d;
    color: #ffffff;
    font-weight: 800;
    font-size: 0.75rem;
    padding: 0.2rem 0.5rem;
    border-radius: 6px;
}

/* Content Sections */
.rc-section {
    padding: 5.5rem 0;
}

.rc-section-header {
    text-align: center;
    max-width: 760px;
    margin: 0 auto 3.5rem auto;
}

.rc-section-tag {
    text-transform: uppercase;
    font-size: 0.85rem;
    font-weight: 800;
    letter-spacing: 1.5px;
    color: var(--rc-purple);
    margin-bottom: 0.5rem;
    display: block;
}

.rc-section-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--rc-dark);
    margin-bottom: 0.75rem;
}

.rc-section-desc {
    font-size: 1.1rem;
    color: var(--rc-slate);
}

/* Redesigned High-Impact ReactCorp Core Pillars Section */
.rc-difference-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2.25rem;
}

.rc-diff-card {
    background: #ffffff;
    border-radius: 22px;
    padding: 2.5rem 2rem;
    border: 1px solid rgba(128, 56, 125, 0.14);
    box-shadow: 0 8px 25px rgba(0,0,0,0.03);
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    display: flex;
    flex-direction: column;
}

.rc-diff-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 6px;
    background: linear-gradient(90deg, #80387d 0%, #f0abfc 100%);
    border-radius: 22px 22px 0 0;
}

.rc-diff-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(128, 56, 125, 0.15);
    border-color: #80387d;
}

.rc-diff-icon-wrap {
    width: 60px;
    height: 60px;
    background: #fdf4ff;
    border: 1px solid rgba(128, 56, 125, 0.15);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    margin-bottom: 1.5rem;
}

.rc-diff-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--rc-dark);
    margin-bottom: 1rem;
}

.rc-diff-text {
    font-size: 1rem;
    color: var(--rc-slate);
    line-height: 1.7;
    margin-bottom: 1.5rem;
    flex-grow: 1;
}

.rc-diff-tag {
    display: inline-flex;
    align-items: center;
    background: #fdf4ff;
    color: #80387d;
    font-size: 0.82rem;
    font-weight: 800;
    padding: 0.35rem 0.85rem;
    border-radius: 50px;
    width: fit-content;
}

/* Categorized 12 Registration Groups */
.rc-groups-cat-sec {
    background: #fdf4ff;
    padding: 5.5rem 0;
}

.rc-cat-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
}

.rc-cat-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 2.25rem;
    box-shadow: 0 10px 25px rgba(0,0,0,0.04);
    border: 1px solid rgba(128, 56, 125, 0.12);
}

.rc-cat-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #fdf4ff;
}

.rc-cat-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.35rem;
    font-weight: 700;
    color: #80387d;
    margin: 0;
}

.rc-cat-items {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.rc-cat-items li {
    font-size: 0.95rem;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

@media (max-width: 1024px) {
    .rc-hero-grid { grid-template-columns: 1fr; }
    .rc-difference-grid { grid-template-columns: 1fr; }
    .rc-cat-grid { grid-template-columns: 1fr; }
}
</style>

<main class="rc-page">

    <!-- HERO SECTION WITH KEYWORD ENHANCEMENTS -->
    <section class="rc-hero-sec">
        <div class="rc-container rc-hero-grid">
            
            <!-- Hero Content -->
            <div>
                <h1 class="rc-hero-title" style="color:#ffffff !important;">
                    NDIS Registered Provider<br><span style="color:#f0abfc !important;">North Kellyville & Sydney NSW.</span>
                </h1>
                <p class="rc-hero-sub" style="color:#f1f5f9 !important;">
                    At <strong>ReactCorp</strong>, your goals become our mission. Located at 20 Barabati Road, North Kellyville, we deliver reliable, flexible, and person-centred NDIS support coordination, SIL accommodation, and 24/7 crisis support across Sydney & NSW.
                </p>
                <div class="rc-btn-group">
                    <a href="tel:0422069482" class="rc-btn-primary" title="Call 24/7 Emergency NDIS Intake Sydney">
                        📞 Call 0422 069 482
                    </a>
                    <a href="https://zfrmz.com/sIh6uDqI2c9PaujmOoTR" target="_blank" rel="noopener" class="rc-btn-secondary" title="Submit Online NDIS Referral Form">
                        Quick Referral Form
                    </a>
                </div>
            </div>

            <!-- NDIS Groups Hero Card -->
            <div class="rc-groups-hero-card">
                <div class="rc-groups-card-header">
                    <h2 class="rc-groups-card-title" style="color:#f0abfc !important;">NDIS Registration Groups (0132, 0115, 0107)</h2>
                    <div class="rc-groups-card-sub" style="color:#cbd5e1 !important;">ReactCorp is approved for 12 core NDIS support classes in NSW:</div>
                </div>

                <div class="rc-groups-list">
                    <div class="rc-group-item">
                        <span class="rc-group-code">0101</span>
                        <span>Accommodation / Tenancy Assistance</span>
                    </div>
                    <div class="rc-group-item">
                        <span class="rc-group-code">0102</span>
                        <span>Assist Access / Maintain Employment</span>
                    </div>
                    <div class="rc-group-item">
                        <span class="rc-group-code">0106</span>
                        <span>Assist-Life Stage, Transition</span>
                    </div>
                    <div class="rc-group-item">
                        <span class="rc-group-code">0107</span>
                        <span>Assist-Personal Activities</span>
                    </div>
                    <div class="rc-group-item">
                        <span class="rc-group-code">0108</span>
                        <span>Assist-Travel / Transport</span>
                    </div>
                    <div class="rc-group-item">
                        <span class="rc-group-code">0115</span>
                        <span>Daily Tasks / Shared Living (SIL)</span>
                    </div>
                    <div class="rc-group-item">
                        <span class="rc-group-code">0116</span>
                        <span>Innovative Community Participation</span>
                    </div>
                    <div class="rc-group-item">
                        <span class="rc-group-code">0117</span>
                        <span>Development-Life Skills</span>
                    </div>
                    <div class="rc-group-item">
                        <span class="rc-group-code">0120</span>
                        <span>Household Tasks</span>
                    </div>
                    <div class="rc-group-item">
                        <span class="rc-group-code">0125</span>
                        <span>Participate Community</span>
                    </div>
                    <div class="rc-group-item">
                        <span class="rc-group-code">0132</span>
                        <span>Support Coordination (Level 1, 2, 3)</span>
                    </div>
                    <div class="rc-group-item">
                        <span class="rc-group-code">0136</span>
                        <span>Group / Centre Activities</span>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- CORE PILLARS SECTION -->
    <section id="about" class="rc-section">
        <div class="rc-container">
            <div class="rc-section-header">
                <span class="rc-section-tag">The ReactCorp Difference</span>
                <h2 class="rc-section-title">Built for Independence. Powered by Quality Care in Sydney.</h2>
                <p class="rc-section-desc">NDIS Price Guide compliant pricing, rapid 24/7 emergency intake, and clinically accredited professionals — putting your choices first.</p>
            </div>

            <div class="rc-difference-grid">
                <!-- Card 1 -->
                <div class="rc-diff-card">
                    <div class="rc-diff-icon-wrap">⚡</div>
                    <h3 class="rc-diff-title">24/7 Emergency NDIS Intake Sydney</h3>
                    <p class="rc-diff-text">When crisis strikes, every minute matters. Our dedicated on-call intake team in Sydney provides instant response within minutes for emergency accommodation, SIL placement, and urgent support.</p>
                    <div class="rc-diff-tag">⚡ Response in Minutes</div>
                </div>

                <!-- Card 2 -->
                <div class="rc-diff-card">
                    <div class="rc-diff-icon-wrap">🛡️</div>
                    <h3 class="rc-diff-title">Vetted & Accredited Care</h3>
                    <p class="rc-diff-text">Every support worker and coordinator is rigorously screened, clinically trained, and accountable. Registered with AASW, ACWA, ACA, and fully compliant with NDIS Quality & Safeguards.</p>
                    <div class="rc-diff-tag">🛡️ AASW & ACWA Certified</div>
                </div>

                <!-- Card 3 -->
                <div class="rc-diff-card">
                    <div class="rc-diff-icon-wrap">💎</div>
                    <h3 class="rc-diff-title">More Support Hours Per Plan</h3>
                    <p class="rc-diff-text">We structure our pricing competitively so you get maximum care hours out of your NDIS budget. More dedicated support, zero wasted funding, and 100% price guide compliance.</p>
                    <div class="rc-diff-tag">💎 Maximised Plan Budget</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CATEGORIZED 12 REGISTRATION GROUPS -->
    <section id="services" class="rc-groups-cat-sec">
        <div class="rc-container">
            <div class="rc-section-header">
                <span class="rc-section-tag">Our Scope of Registration</span>
                <h2 class="rc-section-title">NDIS Registered Services in North Kellyville & Sydney</h2>
                <p class="rc-section-desc">ReactCorp is officially registered for the following 12 support categories across Canterbury-Bankstown & NSW:</p>
            </div>

            <div class="rc-cat-grid">
                <!-- Box 1 -->
                <div class="rc-cat-card">
                    <div class="rc-cat-header">
                        <span style="font-size:1.8rem;">🏠</span>
                        <h3 class="rc-cat-title">Accommodation & Daily Living</h3>
                    </div>
                    <ul class="rc-cat-items">
                        <li><span class="rc-group-code">0101</span> Accommodation / Tenancy Assistance</li>
                        <li><span class="rc-group-code">0115</span> Daily Tasks / Shared Living (SIL)</li>
                        <li><span class="rc-group-code">0120</span> Household Tasks & Assistance</li>
                    </ul>
                </div>

                <!-- Box 2 -->
                <div class="rc-cat-card">
                    <div class="rc-cat-header">
                        <span style="font-size:1.8rem;">🤝</span>
                        <h3 class="rc-cat-title">Support Coordination & Transitions</h3>
                    </div>
                    <ul class="rc-cat-items">
                        <li><span class="rc-group-code">0132</span> Support Coordination Level 1, 2 & 3</li>
                        <li><span class="rc-group-code">0106</span> Assist-Life Stage, Transition</li>
                        <li><span class="rc-group-code">0102</span> Assist Access / Maintain Employment</li>
                    </ul>
                </div>

                <!-- Box 3 -->
                <div class="rc-cat-card">
                    <div class="rc-cat-header">
                        <span style="font-size:1.8rem;">🩺</span>
                        <h3 class="rc-cat-title">Personal Care & Mobility</h3>
                    </div>
                    <ul class="rc-cat-items">
                        <li><span class="rc-group-code">0107</span> Assist-Personal Activities</li>
                        <li><span class="rc-group-code">0108</span> Assist-Travel / Transport</li>
                        <li><span class="rc-group-code">0117</span> Development-Life Skills</li>
                    </ul>
                </div>

                <!-- Box 4 -->
                <div class="rc-cat-card">
                    <div class="rc-cat-header">
                        <span style="font-size:1.8rem;">🎉</span>
                        <h3 class="rc-cat-title">Community & Social Participation</h3>
                    </div>
                    <ul class="rc-cat-items">
                        <li><span class="rc-group-code">0116</span> Innovative Community Participation</li>
                        <li><span class="rc-group-code">0125</span> Participate Community Activities</li>
                        <li><span class="rc-group-code">0136</span> Group / Centre Activities</li>
                    </ul>
                </div>
            </div>

            <!-- Call to Action Banner -->
            <div id="contact" style="background:linear-gradient(135deg, #80387d 0%, #581c87 100%); border-radius:24px; padding:3.5rem 2.5rem; color:#ffffff; text-align:center; margin-top:4rem;">
                <h2 style="font-family:'Space Grotesk',sans-serif; font-size:2.2rem; font-weight:700; margin-bottom:1rem; color:#ffffff !important;">Contact ReactCorp 24/7 Intake Team</h2>
                <p style="font-size:1.1rem; color:#f0abfc !important; max-width:620px; margin:0 auto 2rem auto;">Our intake team in North Kellyville Sydney is available 24/7 to assist participants, families, and support coordinators.</p>
                <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
                    <a href="tel:0422069482" class="rc-btn-primary">
                        📞 Call 0422 069 482
                    </a>
                    <a href="https://zfrmz.com/sIh6uDqI2c9PaujmOoTR" target="_blank" rel="noopener" class="rc-btn-secondary">
                        Submit Referral Online
                    </a>
                </div>
            </div>

    <!-- FAQ SECTION (On-Page AEO for Featured Snippets) -->
    <section id="faq" class="rc-section" aria-label="Frequently asked questions about ReactCorp NDIS services" style="background: #ffffff; padding: 4rem 0;">
        <div class="rc-container">
            <div class="rc-section-header">
                <span class="rc-section-tag">Frequently Asked Questions</span>
                <h2 class="rc-section-title">Common Questions About ReactCorp Disability Services</h2>
                <p class="rc-section-desc">Answers to common questions about our NDIS registration, services, and coverage areas in Sydney and across Australia.</p>
            </div>
            <div style="max-width: 820px; margin: 2rem auto 0; display: flex; flex-direction: column; gap: 1rem;">
                <details style="background: #fdf4ff; border-radius: 12px; padding: 1.25rem 1.5rem; border: 1px solid rgba(128,56,125,0.15); cursor: pointer;">
                    <summary style="font-weight: 700; font-size: 1.05rem; color: #4c1d95; list-style: none; display: flex; justify-space-between; align-items: center;">What is ReactCorp Disability Services?
                        <span style="font-size: 1.3rem;">+</span>
                    </summary>
                    <p style="margin-top: 0.75rem; color: #475569; line-height: 1.7;">ReactCorp Disability Services is a <strong>Registered NDIS Service Provider in Australia</strong> (NDIS #4050064716). We provide 24/7 crisis support, support coordination (Group 0132), SIL accommodation (Group 0115), personal care (Group 0107), and community access across NSW, VIC, ACT, SA, and TAS. Call <a href="tel:0422069482" style="color: #80387d; font-weight: 700;">0422 069 482</a>.</p>
                </details>
                <details style="background: #fdf4ff; border-radius: 12px; padding: 1.25rem 1.5rem; border: 1px solid rgba(128,56,125,0.15); cursor: pointer;">
                    <summary style="font-weight: 700; font-size: 1.05rem; color: #4c1d95; list-style: none; display: flex; justify-space-between; align-items: center;">What NDIS registration groups is ReactCorp approved for?
                        <span style="font-size: 1.3rem;">+</span>
                    </summary>
                    <p style="margin-top: 0.75rem; color: #475569; line-height: 1.7;">ReactCorp is officially registered for 12 NDIS support classes: 0101 Accommodation/Tenancy, 0102 Assist Employment, 0106 Assist Life Stage Transition, 0107 Personal Activities, 0108 Travel/Transport, 0115 Daily Tasks/Shared Living (SIL), 0116 Innovative Community Participation, 0117 Life Skills Development, 0120 Household Tasks, 0125 Community Participation, 0132 Support Coordination (Level 1, 2, 3), and 0136 Group/Centre Activities.</p>
                </details>
                <details style="background: #fdf4ff; border-radius: 12px; padding: 1.25rem 1.5rem; border: 1px solid rgba(128,56,125,0.15); cursor: pointer;">
                    <summary style="font-weight: 700; font-size: 1.05rem; color: #4c1d95; list-style: none; display: flex; justify-space-between; align-items: center;">Where is ReactCorp located and which areas are covered?
                        <span style="font-size: 1.3rem;">+</span>
                    </summary>
                    <p style="margin-top: 0.75rem; color: #475569; line-height: 1.7;">ReactCorp is headquartered at 20 Barabati Road, North Kellyville NSW 2155 (Sydney, NSW). We operate across New South Wales (Sydney, Newcastle, Wollongong), Victoria (Melbourne), Australian Capital Territory (Canberra), South Australia (Adelaide), and Tasmania (Hobart).</p>
                </details>
                <details style="background: #fdf4ff; border-radius: 12px; padding: 1.25rem 1.5rem; border: 1px solid rgba(128,56,125,0.15); cursor: pointer;">
                    <summary style="font-weight: 700; font-size: 1.05rem; color: #4c1d95; list-style: none; display: flex; justify-space-between; align-items: center;">How do I submit an online NDIS referral to ReactCorp?
                        <span style="font-size: 1.3rem;">+</span>
                    </summary>
                    <p style="margin-top: 0.75rem; color: #475569; line-height: 1.7;">You can submit a participant referral through our secure <a href="https://zfrmz.com/sIh6uDqI2c9PaujmOoTR" target="_blank" rel="noopener" style="color: #80387d; font-weight: 700;">Online NDIS Referral Form</a> or call our 24/7 intake hotline at <a href="tel:0422069482" style="color: #80387d; font-weight: 700;">0422 069 482</a>. Referrals are reviewed by our intake team within 24 hours.</p>
                </details>
            </div>
        </div>
    </section>

</main>

<?php
get_footer();

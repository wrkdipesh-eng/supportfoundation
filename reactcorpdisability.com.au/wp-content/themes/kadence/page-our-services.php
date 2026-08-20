<?php
/**
 * Template Name: Our Services Page Template
 * Redesigned Professional Our Services Page for ReactCorp Disability
 * Optimized for Rank #1 Google Keywords (NDIS Support Coordination Level 1 2 3 Sydney, SIL Accommodation Roselands Canterbury)
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

/* Page Hero Banner */
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

.rc-section {
    padding: 5.5rem 0;
}

.rc-sec-header {
    text-align: center;
    max-width: 760px;
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
    font-size: 2.4rem;
    font-weight: 700;
    color: var(--rc-dark);
    margin: 0 0 0.75rem 0;
}

.rc-sec-p {
    font-size: 1.08rem;
    color: var(--rc-slate);
    margin: 0;
}

/* Specialist Services Spotlight Cards */
.rc-specialist-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2.25rem;
    margin-bottom: 5rem;
}

.rc-specialist-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 3rem 2.5rem;
    border: 1px solid rgba(128, 56, 125, 0.14);
    box-shadow: 0 10px 30px rgba(0,0,0,0.04);
    transition: all 0.35s ease;
    position: relative;
    display: flex;
    flex-direction: column;
}

.rc-specialist-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 6px;
    background: linear-gradient(90deg, #80387d 0%, #f0abfc 100%);
    border-radius: 24px 24px 0 0;
}

.rc-specialist-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 22px 45px rgba(128, 56, 125, 0.15);
    border-color: #80387d;
}

.rc-star-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: #fdf4ff;
    color: #80387d;
    font-size: 0.82rem;
    font-weight: 800;
    padding: 0.35rem 0.9rem;
    border-radius: 50px;
    width: fit-content;
    margin-bottom: 1.25rem;
}

.rc-specialist-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.7rem;
    font-weight: 700;
    color: var(--rc-dark);
    margin: 0 0 1.25rem 0;
    line-height: 1.3;
}

.rc-specialist-desc {
    font-size: 1.02rem;
    color: var(--rc-slate);
    line-height: 1.75;
    margin-bottom: 2rem;
    flex-grow: 1;
}

.rc-specialist-features {
    list-style: none;
    padding: 0;
    margin: 0 0 2rem 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.rc-specialist-features li {
    font-size: 0.95rem;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 0.65rem;
    font-weight: 600;
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

.rc-group-code {
    background: #80387d;
    color: #ffffff;
    font-weight: 800;
    font-size: 0.75rem;
    padding: 0.2rem 0.5rem;
    border-radius: 6px;
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
    .rc-specialist-grid { grid-template-columns: 1fr; }
    .rc-cat-grid { grid-template-columns: 1fr; }
}
</style>

<main class="rc-page">

    <!-- OUR SERVICES HERO BANNER WITH TARGET KEYWORDS -->
    <section class="rc-page-hero">
        <div class="rc-container">
            <span class="rc-hero-tag" style="color:#f0abfc !important;">NDIS Provider Roselands NSW</span>
            <h1 class="rc-hero-h1" style="color:#ffffff !important;">Our NDIS Registered Support Services</h1>
            <p class="rc-hero-p" style="color:#f1f5f9 !important;">
                ReactCorp is an officially registered NDIS provider in Roselands Sydney (Canterbury-Bankstown region). We specialize in <strong>NDIS Support Coordination (Level 1, 2 & 3)</strong>, <strong>Supported Independent Living (SIL) Accommodation</strong>, Personal Care, and 24/7 emergency intake.
            </p>
            <div class="rc-hero-btn-group">
                <a href="tel:0422069482" class="rc-btn-white" title="Call 24/7 Emergency NDIS Intake Sydney">
                    📞 Speak to Intake Team (0422 069 482)
                </a>
                <a href="https://zfrmz.com/sIh6uDqI2c9PaujmOoTR" target="_blank" rel="noopener" class="rc-btn-glass" title="Submit Online NDIS Referral Form">
                    📝 Quick Referral Form
                </a>
            </div>
        </div>
    </section>

    <!-- SPECIALIST SERVICES SPOTLIGHT WITH RANK #1 KEYWORDS -->
    <section class="rc-section">
        <div class="rc-container">
            <div class="rc-sec-header">
                <span class="rc-sec-tag">Specialist Focus Areas</span>
                <h2 class="rc-sec-h2">NDIS Support Coordination & SIL Accommodation Sydney</h2>
                <p class="rc-sec-p">Designed for participants in Roselands, Canterbury-Bankstown & Greater Sydney requiring expert coordination and quality living environments.</p>
            </div>

            <div class="rc-specialist-grid">
                <!-- Specialist Card 1: Support Coordination -->
                <div class="rc-specialist-card">
                    <span class="rc-star-badge">⭐ Group 0132 Provider</span>
                    <h3 class="rc-specialist-title">NDIS Support Coordination (Level 1, 2 & 3)</h3>
                    <p class="rc-specialist-desc">
                        ReactCorp specialises in <strong>Support Coordination Level 1, 2 & 3 in Sydney & Roselands</strong>. Including Level 1 (Support Connection), Level 2 (Coordination of Supports), and Level 3 (Specialist Support Coordination). Our experienced coordinators help participants understand their NDIS plans, connect with appropriate providers, and build the skills needed to manage supports independently. For participants with complex needs, our Specialist Support Coordinators provide high-level expertise to manage challenges, reduce risks, and ensure consistent service delivery. We work collaboratively with families, carers, and allied professionals across Sydney to achieve sustainable outcomes and maximize the value of your NDIS funding.
                    </p>
                    <ul class="rc-specialist-features">
                        <li>✔️ Level 1 Support Connection (0132)</li>
                        <li>✔️ Level 2 Coordination of Supports (0132)</li>
                        <li>✔️ Level 3 Specialist Support Coordination (0132)</li>
                        <li>✔️ Crisis & Complex Case Resolution Roselands</li>
                    </ul>
                    <a href="tel:0422069482" style="background:linear-gradient(135deg, #80387d 0%, #581c87 100%); color:#ffffff; padding:0.8rem 1.4rem; border-radius:50px; font-weight:800; text-decoration:none; text-align:center; display:block;" title="Connect with Roselands Support Coordinator">
                        📞 Connect with a Support Coordinator
                    </a>
                </div>

                <!-- Specialist Card 2: SIL Accommodation -->
                <div class="rc-specialist-card">
                    <span class="rc-star-badge">⭐ Group 0115 Provider</span>
                    <h3 class="rc-specialist-title">Supported Independent Living (SIL) Accommodation Sydney</h3>
                    <p class="rc-specialist-desc">
                        ReactCorp is highly experienced in <strong>Supported Independent Living (SIL) accommodation in Roselands, Canterbury & Greater Sydney</strong>. We provide safe, comfortable, and supportive living environments where participants can build independence while receiving tailored daily assistance. Our SIL homes are designed to promote dignity, inclusion, and community participation. Supports include 24/7 personal care, meal preparation, medication assistance, skill development, and overnight supervision. We match housemates carefully to encourage positive living arrangements and provide 24/7 responsive emergency intake support.
                    </p>
                    <ul class="rc-specialist-features">
                        <li>✔️ 24/7 Responsive Active & Overnight SIL Support (0115)</li>
                        <li>✔️ Tailored Daily Living & Skill Development</li>
                        <li>✔️ Compatible Housemate Matching Roselands</li>
                        <li>✔️ Dignified Shared Living Homes Sydney</li>
                    </ul>
                    <a href="tel:0422069482" style="background:linear-gradient(135deg, #80387d 0%, #581c87 100%); color:#ffffff; padding:0.8rem 1.4rem; border-radius:50px; font-weight:800; text-decoration:none; text-align:center; display:block;" title="Inquire About Roselands SIL Homes">
                        🏠 Inquire About SIL Homes in Roselands
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- CATEGORIZED 12 REGISTRATION GROUPS -->
    <section class="rc-groups-cat-sec">
        <div class="rc-container">
            <div class="rc-sec-header">
                <span class="rc-sec-tag">All 12 Approved NDIS Registration Groups</span>
                <h2 class="rc-sec-h2">NDIS Registered Services Scope Sydney</h2>
                <p class="rc-sec-p">ReactCorp is approved by the NDIS Quality & Safeguards Commission for 12 support classes:</p>
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

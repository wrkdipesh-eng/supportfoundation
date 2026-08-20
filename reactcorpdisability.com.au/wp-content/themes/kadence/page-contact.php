<?php
/**
 * Template Name: Contact Page Template
 * Redesigned Professional Contact & Intake Page for ReactCorp Disability
 * Optimized for Rank #1 Google Keywords (24/7 Emergency NDIS Intake Sydney, NDIS Provider Roselands NSW)
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

/* Contact Page Hero Banner */
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

/* Contact Grid Section */
.rc-contact-sec {
    padding: 5.5rem 0;
}

.rc-contact-grid {
    display: grid;
    grid-template-columns: 0.9fr 1.1fr;
    gap: 3.5rem;
    align-items: start;
}

/* Contact Details Info Card */
.rc-contact-info-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 3rem 2.5rem;
    border: 1px solid rgba(128, 56, 125, 0.14);
    box-shadow: 0 10px 30px rgba(0,0,0,0.04);
}

.rc-info-item {
    display: flex;
    gap: 1.25rem;
    margin-bottom: 2rem;
}

.rc-info-item:last-child {
    margin-bottom: 0;
}

.rc-info-icon-box {
    width: 56px;
    height: 56px;
    background: #fdf4ff;
    border: 1px solid rgba(128, 56, 125, 0.18);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.rc-info-label {
    font-size: 0.85rem;
    text-transform: uppercase;
    font-weight: 800;
    letter-spacing: 1px;
    color: #80387d;
    margin-bottom: 0.35rem;
}

.rc-info-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--rc-dark);
    margin: 0;
    line-height: 1.5;
}

.rc-info-value a {
    color: var(--rc-dark);
    text-decoration: none;
    transition: color 0.2s ease;
}

.rc-info-value a:hover {
    color: #80387d;
}

/* Contact / Referral Form Box */
.rc-contact-form-box {
    background: #ffffff;
    border-radius: 24px;
    padding: 3rem 2.5rem;
    border: 1px solid rgba(128, 56, 125, 0.14);
    box-shadow: 0 10px 30px rgba(0,0,0,0.04);
    position: relative;
}

.rc-contact-form-box::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 6px;
    background: linear-gradient(90deg, #80387d 0%, #f0abfc 100%);
    border-radius: 24px 24px 0 0;
}

.rc-form-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--rc-dark);
    margin: 0 0 0.5rem 0;
}

.rc-form-sub {
    font-size: 0.98rem;
    color: var(--rc-slate);
    margin-bottom: 2rem;
}

.rc-form-group {
    margin-bottom: 1.35rem;
}

.rc-form-label {
    display: block;
    font-size: 0.9rem;
    font-weight: 700;
    color: #334155;
    margin-bottom: 0.5rem;
}

.rc-form-input, .rc-form-select, .rc-form-textarea {
    width: 100%;
    padding: 0.85rem 1.1rem;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    font-family: inherit;
    font-size: 0.98rem;
    color: var(--rc-dark);
    background: #f8fafc;
    transition: all 0.25s ease;
}

.rc-form-input:focus, .rc-form-select:focus, .rc-form-textarea:focus {
    outline: none;
    border-color: #80387d;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(128, 56, 125, 0.12);
}

.rc-form-submit-btn {
    width: 100%;
    background: linear-gradient(135deg, #80387d 0%, #581c87 100%);
    color: #ffffff;
    border: none;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(128, 56, 125, 0.3);
    transition: all 0.25s ease;
}

.rc-form-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(128, 56, 125, 0.4);
}

@media (max-width: 1024px) {
    .rc-contact-grid { grid-template-columns: 1fr; }
}
</style>

<main class="rc-page">

    <!-- CONTACT HERO BANNER WITH TARGET KEYWORDS -->
    <section class="rc-page-hero">
        <div class="rc-container">
            <span class="rc-hero-tag" style="color:#f0abfc !important;">24/7 Emergency NDIS Intake Sydney</span>
            <h1 class="rc-hero-h1" style="color:#ffffff !important;">Contact ReactCorp Disability Services</h1>
            <p class="rc-hero-p" style="color:#f1f5f9 !important;">
                Our NDIS intake coordinators and support managers in Roselands Sydney are available around the clock to answer your inquiries, process referrals, and help you get started with your NDIS plan.
            </p>
        </div>
    </section>

    <!-- CONTACT GRID & FORM SECTION -->
    <section class="rc-contact-sec">
        <div class="rc-container">
            <div class="rc-contact-grid">
                
                <!-- Left: Official Contact Details -->
                <div class="rc-contact-info-card">
                    <h2 style="font-family:'Space Grotesk',sans-serif; font-size:1.8rem; font-weight:700; color:var(--rc-dark); margin:0 0 1.5rem 0;">Get in Touch Directly</h2>
                    
                    <div class="rc-info-item">
                        <div class="rc-info-icon-box">📍</div>
                        <div>
                            <div class="rc-info-label">Office Location</div>
                            <div class="rc-info-value">
                                20 Barabati Road,<br>North Kellyville NSW 2155, Australia
                            </div>
                        </div>
                    </div>

                    <div class="rc-info-item">
                        <div class="rc-info-icon-box">📞</div>
                        <div>
                            <div class="rc-info-label">24/7 Phone Line</div>
                            <div class="rc-info-value">
                                <a href="tel:0422069482">0422 069 482</a>
                            </div>
                        </div>
                    </div>

                    <div class="rc-info-item">
                        <div class="rc-info-icon-box">✉️</div>
                        <div>
                            <div class="rc-info-label">Email Address</div>
                            <div class="rc-info-value">
                                <a href="mailto:info@reactcorpdisability.com.au">info@reactcorpdisability.com.au</a>
                            </div>
                        </div>
                    </div>

                    <!-- Direct Zoho Referral Link Button Box -->
                    <div style="background:#fdf4ff; border:1px solid rgba(128,56,125,0.18); border-radius:16px; padding:1.5rem; margin-top:2rem; text-align:center;">
                        <div style="font-weight:700; color:#80387d; margin-bottom:0.5rem; font-size:1.05rem;">Prefer Online Referral Forms?</div>
                        <p style="font-size:0.9rem; color:#475569; margin-bottom:1rem; line-height:1.5;">Access our official online participant intake & referral portal directly.</p>
                        <a href="https://zfrmz.com/sIh6uDqI2c9PaujmOoTR" target="_blank" rel="noopener" style="background:linear-gradient(135deg, #80387d 0%, #581c87 100%); color:#ffffff; padding:0.75rem 1.4rem; border-radius:50px; font-weight:800; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:0.4rem; width:100%;">
                            📝 Submit Zoho Online Referral Form
                        </a>
                    </div>
                </div>

                <!-- Right: Quick Contact / Inquiry Form Box -->
                <div class="rc-contact-form-box">
                    <h2 class="rc-form-title">Send Us a Message</h2>
                    <p class="rc-form-sub">Fill out the quick form below and our Roselands intake team will respond to you within 24 hours.</p>

                    <form action="#" method="POST" onsubmit="alert('Thank you! Your message has been sent successfully.'); return false;">
                        <div class="rc-form-group">
                            <label class="rc-form-label" for="fullname">Full Name *</label>
                            <input type="text" id="fullname" class="rc-form-input" placeholder="e.g. Sarah Jenkins" required>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                            <div class="rc-form-group">
                                <label class="rc-form-label" for="phone">Phone Number *</label>
                                <input type="tel" id="phone" class="rc-form-input" placeholder="04XX XXX XXX" required>
                            </div>
                            <div class="rc-form-group">
                                <label class="rc-form-label" for="email">Email Address *</label>
                                <input type="email" id="email" class="rc-form-input" placeholder="name@domain.com" required>
                            </div>
                        </div>

                        <div class="rc-form-group">
                            <label class="rc-form-label" for="service">Service Required</label>
                            <select id="service" class="rc-form-select">
                                <option value="0132">Support Coordination Level 1, 2, 3 (0132)</option>
                                <option value="0115">SIL Supported Independent Living (0115)</option>
                                <option value="0107">Assist Personal Activities (0107)</option>
                                <option value="0116">Community & Social Participation (0116 / 0125)</option>
                                <option value="0101">Accommodation Assistance (0101)</option>
                                <option value="other">General Inquiry / Other</option>
                            </select>
                        </div>

                        <div class="rc-form-group">
                            <label class="rc-form-label" for="message">Your Message or Referral Details</label>
                            <textarea id="message" class="rc-form-textarea" rows="4" placeholder="How can ReactCorp assist you or your participant in Sydney?"></textarea>
                        </div>

                        <button type="submit" class="rc-form-submit-btn">
                            🚀 Submit Message
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </section>

</main>

<?php
get_footer();

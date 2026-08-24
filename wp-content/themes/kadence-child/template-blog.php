<?php
/**
 * Template Name: NDIS SEO Blog & Knowledge Center
 * Description: Fully On-Page SEO Optimized Knowledge Center for Support Foundation Australia.
 */

get_header();
?>

<style>
/* SF NDIS KNOWLEDGE CENTER MODERN STYLES */
.sf-blog-hero {
    background: linear-gradient(135deg, #044e3a 0%, #047857 45%, #065f46 100%);
    color: #ffffff;
    padding: 4.5rem 0 3.75rem;
    position: relative;
    overflow: hidden;
}
.sf-blog-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(167, 243, 208, 0.12) 0%, rgba(255,255,255,0) 70%);
    border-radius: 50%;
    pointer-events: none;
}
.sf-blog-hero-inner {
    max-width: 980px;
    margin: 0 auto;
    text-align: center;
    padding: 0 1.5rem;
    position: relative;
    z-index: 2;
}
.sf-blog-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    color: #a7f3d0;
    font-size: 0.85rem;
    font-weight: 700;
    padding: 6px 18px;
    border-radius: 50px;
    margin-bottom: 1.25rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.sf-blog-hero-title {
    font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
    font-size: 2.85rem;
    font-weight: 800;
    line-height: 1.25;
    margin-bottom: 1.25rem;
    color: #ffffff;
}
.sf-blog-hero-subtitle {
    font-size: 1.15rem;
    line-height: 1.7;
    color: #e2e8f0;
    max-width: 840px;
    margin: 0 auto 2rem;
}

/* INTERACTIVE SEARCH & CATEGORY BAR */
.sf-search-box-wrap {
    max-width: 680px;
    margin: 0 auto 1.5rem;
    position: relative;
}
.sf-search-input {
    width: 100%;
    padding: 16px 24px 16px 54px;
    font-size: 1.05rem;
    border-radius: 50px;
    border: 2px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.95);
    color: #0f172a;
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15);
    outline: none;
    transition: all 0.25s ease;
}
.sf-search-input:focus {
    background: #ffffff;
    border-color: #10b981;
    box-shadow: 0 12px 30px -5px rgba(16,185,129,0.3);
}
.sf-search-icon {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: #047857;
    pointer-events: none;
}

.sf-filter-pills {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
    margin-top: 1.25rem;
}
.sf-pill-btn {
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.25);
    color: #ffffff;
    padding: 8px 18px;
    border-radius: 30px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}
.sf-pill-btn:hover, .sf-pill-btn.active {
    background: #ffffff;
    color: #047857;
    border-color: #ffffff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* LAYOUT */
.sf-blog-layout {
    max-width: 1240px;
    margin: 0 auto;
    padding: 3.5rem 1.5rem;
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 3rem;
}
@media (max-width: 992px) {
    .sf-blog-layout {
        grid-template-columns: 1fr;
    }
}

/* ARTICLE CARDS */
.sf-article-pillar {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 2.75rem;
    margin-bottom: 3.25rem;
    box-shadow: 0 4px 14px -2px rgba(0,0,0,0.04);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.sf-article-pillar:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px -4px rgba(0,0,0,0.08);
}
.sf-article-header-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 1.25rem;
    padding-bottom: 0.85rem;
    border-bottom: 1px solid #f1f5f9;
}
.sf-pillar-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #d1fae5;
    color: #065f46;
    font-weight: 700;
    font-size: 0.825rem;
    padding: 6px 14px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.sf-reading-time {
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 5px;
}

.sf-pillar-title {
    font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
    font-size: 2rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.3;
    margin-bottom: 1.25rem;
}
.sf-pillar-content {
    font-size: 1.05rem;
    line-height: 1.85;
    color: #334155;
}
.sf-pillar-content h3 {
    font-family: 'Outfit', sans-serif;
    font-size: 1.45rem;
    font-weight: 700;
    color: #1e293b;
    margin: 2rem 0 0.85rem;
    padding-bottom: 0.4rem;
    border-bottom: 2px solid #f1f5f9;
}
.sf-pillar-content h4 {
    font-family: 'Outfit', sans-serif;
    font-size: 1.2rem;
    font-weight: 600;
    color: #0f172a;
    margin: 1.5rem 0 0.6rem;
}
.sf-pillar-content p {
    margin-bottom: 1.35rem;
}
.sf-pillar-content ul, .sf-pillar-content ol {
    margin: 0 0 1.5rem 1.75rem;
}
.sf-pillar-content li {
    margin-bottom: 0.5rem;
}

/* FEATURED SNIPPET Q&A BOX */
.sf-paa-box {
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-left: 5px solid #0284c7;
    padding: 1.6rem;
    border-radius: 12px;
    margin: 1.75rem 0;
}
.sf-paa-question {
    font-weight: 700;
    color: #0369a1;
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 8px;
}
.sf-paa-answer {
    color: #334155;
    margin-bottom: 0;
    line-height: 1.7;
}

/* INTERNAL LINK ANCHOR STYLES */
.sf-internal-link {
    color: #047857;
    font-weight: 700;
    text-decoration: underline;
    transition: color 0.2s ease;
}
.sf-internal-link:hover {
    color: #064e3b;
}

/* AUTHOR FOOTER CARD */
.sf-author-footer {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-top: 2rem;
    padding-top: 1.25rem;
    border-top: 1px solid #f1f5f9;
    font-size: 0.9rem;
    color: #64748b;
}
.sf-author-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #047857;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.95rem;
}

/* SIDEBAR STYLES */
.sf-sidebar-sticky {
    position: sticky;
    top: 100px;
}
.sf-sidebar-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.75rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);
}
.sf-sidebar-heading {
    font-family: 'Outfit', sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #10b981;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.sf-toc-link-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.sf-toc-link-list li {
    margin-bottom: 0.65rem;
}
.sf-toc-link-list a {
    color: #334155;
    font-weight: 600;
    font-size: 0.925rem;
    text-decoration: none;
    display: block;
    padding: 4px 8px;
    border-radius: 6px;
    transition: all 0.2s ease;
}
.sf-toc-link-list a:hover {
    color: #047857;
    background: #f0fdf4;
}

.sf-cta-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    background: #10b981;
    color: #ffffff;
    font-weight: 700;
    padding: 14px 20px;
    border-radius: 10px;
    text-decoration: none;
    margin-top: 1rem;
    text-align: center;
    transition: background 0.2s ease;
}
.sf-cta-button:hover {
    background: #059669;
    color: #ffffff;
}
.sf-cta-button-dark {
    background: #0f172a;
}
.sf-cta-button-dark:hover {
    background: #1e293b;
}

/* NO RESULTS STATE */
#sf-no-results {
    display: none;
    text-align: center;
    padding: 4rem 2rem;
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 16px;
    color: #64748b;
}
</style>

<!-- HERO -->
<section class="sf-blog-hero">
    <div class="sf-blog-hero-inner">
        <span class="sf-blog-badge">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Registered NDIS Provider #4050064716
        </span>
        <h1 class="sf-blog-hero-title">NDIS Knowledge Center & Care Guides</h1>
        <p class="sf-blog-hero-subtitle">Expert articles on Registered NDIS Provider standards, 24/7 Crisis Support, Level 2/3 Support Coordination, Emergency Housing, Nursing Care, and Healthcare Careers across Australia.</p>
        
        <!-- LIVE SEARCH BAR -->
        <div class="sf-search-box-wrap">
            <svg class="sf-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" id="sf-blog-search" class="sf-search-input" placeholder="Search NDIS topics (e.g., Crisis, Support Coordination, Housing, Nursing)..." onkeyup="sfFilterArticles()">
        </div>

        <!-- CATEGORY PILLS -->
        <div class="sf-filter-pills">
            <button class="sf-pill-btn active" onclick="sfFilterCategory('all', this)">All Articles</button>
            <button class="sf-pill-btn" onclick="sfFilterCategory('compliance', this)">NDIS Compliance</button>
            <button class="sf-pill-btn" onclick="sfFilterCategory('crisis', this)">Crisis & Housing</button>
            <button class="sf-pill-btn" onclick="sfFilterCategory('coordination', this)">Support Coordination</button>
            <button class="sf-pill-btn" onclick="sfFilterCategory('nursing', this)">Nursing Care</button>
            <button class="sf-pill-btn" onclick="sfFilterCategory('careers', this)">Careers</button>
        </div>
    </div>
</section>

<!-- LAYOUT -->
<div class="sf-blog-layout">
    <main>

        <!-- NO RESULTS PLACEHOLDER -->
        <div id="sf-no-results">
            <h3 style="font-size: 1.35rem; color: #0f172a; margin-bottom: 0.5rem;">No matching articles found</h3>
            <p>Try searching for a different NDIS topic or select "All Articles" above.</p>
        </div>

        <!-- PILLAR 1 -->
        <article class="sf-article-pillar" id="pillar-1" data-category="compliance" itemscope itemtype="https://schema.org/Article">
            <div class="sf-article-header-meta">
                <span class="sf-pillar-tag">🛡️ Registered NDIS Provider</span>
                <span class="sf-reading-time">⏱️ 5 min read</span>
            </div>
            <h2 class="sf-pillar-title" itemprop="headline">Complete Guide to Choosing a Registered NDIS Service Provider in Australia</h2>
            <div class="sf-pillar-content" itemprop="articleBody">
                <p>Finding a trusted <strong>Registered NDIS Service Provider in Australia</strong> is the single most critical decision for participants, carers, and families looking for safe, high-quality disability support. A registered provider operates under direct regulation by the <strong>NDIS Quality and Safeguards Commission</strong>, guaranteeing independent quality audits, mandatory worker screening, and strict incident reporting standards.</p>
                
                <h3>What Makes a Service Provider "Registered"?</h3>
                <p>To achieve formal registration status, an organization must complete detailed compliance audits across governance, risk management, clinical care delivery, and human rights protection. At <a href="https://www.supportfoundation.com.au/about-support-foundation/" class="sf-internal-link">Support Foundation Australia</a> (NDIS Registration #4050064716), registration represents our commitment to ethical social work standards, AASW principles, and community care excellence.</p>

                <!-- FEATURED SNIPPET Q&A BOX -->
                <div class="sf-paa-box">
                    <div class="sf-paa-question">💡 Q: Why should I choose a Registered NDIS Service Provider in Australia?</div>
                    <div class="sf-paa-answer">A: Choosing a Registered NDIS Provider ensures your supports comply with audited federal quality standards. Registered providers can deliver services to participants across all funding management types: Agency-Managed (NDIS-Managed), Plan-Managed, and Self-Managed plans.</div>
                </div>

                <h3>Plan Compatibility: Agency-Managed vs Self-Managed</h3>
                <p>Participants whose plans are managed by the NDIA (Agency-Managed) are legally restricted to receiving services from a Registered NDIS Service Provider in Australia. Plan-managed and self-managed participants also gain peace of mind knowing registered providers adhere to strict safeguards.</p>

                <p>Learn more about our full range of disability supports on our <a href="https://www.supportfoundation.com.au/#services" class="sf-internal-link">NDIS Services Page</a>.</p>
            </div>
            <div class="sf-author-footer">
                <div class="sf-author-avatar">SF</div>
                <div>Written by <strong>Support Foundation Clinical & Social Work Team</strong> · NDIS Provider #4050064716</div>
            </div>
        </article>

        <!-- PILLAR 2 -->
        <article class="sf-article-pillar" id="pillar-2" data-category="coordination" itemscope itemtype="https://schema.org/Article">
            <div class="sf-article-header-meta">
                <span class="sf-pillar-tag">🧭 Support Coordination</span>
                <span class="sf-reading-time">⏱️ 6 min read</span>
            </div>
            <h2 class="sf-pillar-title" itemprop="headline">Level 2 & Level 3 Specialist NDIS Support Coordination Sydney & NSW</h2>
            <div class="sf-pillar-content" itemprop="articleBody">
                <p>Navigating the complexity of NDIS funding requires expert guidance. Our <strong>NDIS Support Coordination Sydney & NSW</strong> team works directly with participants and families to connect them with mainstream healthcare, community services, and crisis accommodation across New South Wales.</p>

                <div class="sf-paa-box">
                    <div class="sf-paa-question">💡 Q: What is the difference between Level 2 and Level 3 NDIS Support Coordination in Sydney?</div>
                    <div class="sf-paa-answer">A: Level 2 Support Coordination helps participants build independence to manage service provider relationships. Level 3 Specialist Support Coordination is delivered by qualified social workers to manage high-risk participant crises, complex health transitions, and multi-agency care plans.</div>
                </div>

                <h3>How Our Support Coordinators Help You</h3>
                <ul>
                    <li>Optimizing participant NDIS plan funding allocations.</li>
                    <li>Resolving informal care breakdown and housing crises.</li>
                    <li>Preparing evidence for NDIS plan reviews and appeals.</li>
                </ul>
            </div>
            <div class="sf-author-footer">
                <div class="sf-author-avatar">SF</div>
                <div>Written by <strong>Senior Support Coordinators</strong> · Support Foundation Australia</div>
            </div>
        </article>

        <!-- PILLAR 3 -->
        <article class="sf-article-pillar" id="pillar-3" data-category="crisis" itemscope itemtype="https://schema.org/Article">
            <div class="sf-article-header-meta">
                <span class="sf-pillar-tag">🚨 24/7 Crisis Support</span>
                <span class="sf-reading-time">⏱️ 4 min read</span>
            </div>
            <h2 class="sf-pillar-title" itemprop="headline">24/7 Crisis Support NDIS Services for Immediate Urgent Care</h2>
            <div class="sf-pillar-content" itemprop="articleBody">
                <p>Disability care emergencies require immediate, professional action. Our <strong>24/7 Crisis Support NDIS</strong> response team operates around the clock, providing rapid intervention when care breaks down, informal carers become unwell, or urgent housing relocation is required.</p>

                <div class="sf-paa-box">
                    <div class="sf-paa-question">💡 Q: How fast can 24/7 Crisis Support NDIS be arranged?</div>
                    <div class="sf-paa-answer">A: Support Foundation operates an emergency 24/7 crisis hotline at 02-8386-1433. On-call welfare coordinators review crisis requests immediately and deploy emergency support workers or accommodation placement within hours.</div>
                </div>

                <p>Contact our emergency response team anytime on <a href="tel:0283861433" class="sf-internal-link">02-8386-1433</a> or visit our <a href="https://www.supportfoundation.com.au/contact-us-support-foundation/" class="sf-internal-link">Contact Page</a>.</p>
            </div>
            <div class="sf-author-footer">
                <div class="sf-author-avatar">SF</div>
                <div>Written by <strong>Emergency Response Unit</strong> · Support Foundation Australia</div>
            </div>
        </article>

        <!-- PILLAR 4 -->
        <article class="sf-article-pillar" id="pillar-4" data-category="crisis" itemscope itemtype="https://schema.org/Article">
            <div class="sf-article-header-meta">
                <span class="sf-pillar-tag">🏠 Emergency Housing</span>
                <span class="sf-reading-time">⏱️ 5 min read</span>
            </div>
            <h2 class="sf-pillar-title" itemprop="headline">Rapid Emergency Housing NDIS Provider & Immediate Accommodation</h2>
            <div class="sf-pillar-content" itemprop="articleBody">
                <p>Facing sudden homelessness or unsafe living conditions is a critical emergency. As an established <strong>Emergency Housing NDIS Provider</strong>, Support Foundation places participants into safe, accessible Short-Term Accommodation (STA) rapidly.</p>

                <div class="sf-paa-box">
                    <div class="sf-paa-question">💡 Q: Can NDIS fund emergency housing accommodation?</div>
                    <div class="sf-paa-answer">A: Yes. NDIS Short-Term Accommodation (STA) and Crisis Respite funding can cover up to 14 days of emergency housing, 24/7 support worker care, and daily meals for participants experiencing housing crisis.</div>
                </div>
            </div>
            <div class="sf-author-footer">
                <div class="sf-author-avatar">SF</div>
                <div>Written by <strong>Housing Placement Coordinators</strong> · Support Foundation Australia</div>
            </div>
        </article>

        <!-- PILLAR 5 -->
        <article class="sf-article-pillar" id="pillar-5" data-category="crisis" itemscope itemtype="https://schema.org/Article">
            <div class="sf-article-header-meta">
                <span class="sf-pillar-tag">🛌 Short Term Respite</span>
                <span class="sf-reading-time">⏱️ 4 min read</span>
            </div>
            <h2 class="sf-pillar-title" itemprop="headline">Short Term Accommodation STA NDIS & Respite Care Explained</h2>
            <div class="sf-pillar-content" itemprop="articleBody">
                <p><strong>Short Term Accommodation STA NDIS</strong> funding provides temporary supported housing away from home for up to 14 days at a time. STA gives informal carers an opportunity to recharge while participants build new daily living skills and community connections.</p>
            </div>
            <div class="sf-author-footer">
                <div class="sf-author-avatar">SF</div>
                <div>Written by <strong>Respite Services Team</strong> · Support Foundation Australia</div>
            </div>
        </article>

        <!-- PILLAR 6 -->
        <article class="sf-article-pillar" id="pillar-6" data-category="crisis" itemscope itemtype="https://schema.org/Article">
            <div class="sf-article-header-meta">
                <span class="sf-pillar-tag">🛡️ Trauma Support</span>
                <span class="sf-reading-time">⏱️ 5 min read</span>
            </div>
            <h2 class="sf-pillar-title" itemprop="headline">Trauma-Informed Domestic Violence Support NDIS Safety Planning</h2>
            <div class="sf-pillar-content" itemprop="articleBody">
                <p>Our dedicated <strong>Domestic Violence Support NDIS</strong> team delivers confidential safety planning, safe emergency relocation, and social work advocacy for NDIS participants escaping domestic or family violence.</p>
            </div>
            <div class="sf-author-footer">
                <div class="sf-author-avatar">SF</div>
                <div>Written by <strong>Social Work Advocates</strong> · Support Foundation Australia</div>
            </div>
        </article>

        <!-- PILLAR 7 -->
        <article class="sf-article-pillar" id="pillar-7" data-category="nursing" itemscope itemtype="https://schema.org/Article">
            <div class="sf-article-header-meta">
                <span class="sf-pillar-tag">🩺 Personal & Nursing Care</span>
                <span class="sf-reading-time">⏱️ 5 min read</span>
            </div>
            <h2 class="sf-pillar-title" itemprop="headline">In-Home Personal Care Nursing NDIS & Complex Daily Living Support</h2>
            <div class="sf-pillar-content" itemprop="articleBody">
                <p>Our <strong>Personal Care Nursing NDIS</strong> services assist participants with daily living activities, hygiene, grooming, medication administration, and complex high-intensity nursing support in the comfort of their home.</p>
            </div>
            <div class="sf-author-footer">
                <div class="sf-author-avatar">SF</div>
                <div>Written by <strong>Clinical Nursing Supervisors</strong> · Support Foundation Australia</div>
            </div>
        </article>

        <!-- PILLAR 8 -->
        <article class="sf-article-pillar" id="pillar-8" data-category="nursing" itemscope itemtype="https://schema.org/Article">
            <div class="sf-article-header-meta">
                <span class="sf-pillar-tag">🧠 Mental Health Recovery</span>
                <span class="sf-reading-time">⏱️ 4 min read</span>
            </div>
            <h2 class="sf-pillar-title" itemprop="headline">Psychosocial Recovery Coaching NDIS & Mental Health Support</h2>
            <div class="sf-pillar-content" itemprop="articleBody">
                <p>Working alongside participants with mental health conditions, a <strong>Psychosocial Recovery Coach NDIS</strong> builds resilience, capacity, and self-advocacy to foster long-term mental health recovery.</p>
            </div>
            <div class="sf-author-footer">
                <div class="sf-author-avatar">SF</div>
                <div>Written by <strong>Psychosocial Recovery Team</strong> · Support Foundation Australia</div>
            </div>
        </article>

        <!-- PILLAR 9 -->
        <article class="sf-article-pillar" id="pillar-9" data-category="compliance" itemscope itemtype="https://schema.org/Article">
            <div class="sf-article-header-meta">
                <span class="sf-pillar-tag">📍 Provider Coverage</span>
                <span class="sf-reading-time">⏱️ 5 min read</span>
            </div>
            <h2 class="sf-pillar-title" itemprop="headline">Registered NDIS Provider Melbourne & Victoria Support Network</h2>
            <div class="sf-pillar-content" itemprop="articleBody">
                <p>Victorian participants can access our registered care network as a leading <strong>NDIS Provider Melbourne & Victoria</strong>, offering support coordination, personal care, and emergency housing across Melbourne metro and regional VIC.</p>
            </div>
            <div class="sf-author-footer">
                <div class="sf-author-avatar">SF</div>
                <div>Written by <strong>VIC Regional Coordinators</strong> · Support Foundation Australia</div>
            </div>
        </article>

        <!-- PILLAR 10 -->
        <article class="sf-article-pillar" id="pillar-10" data-category="careers" itemscope itemtype="https://schema.org/Article">
            <div class="sf-article-header-meta">
                <span class="sf-pillar-tag">💼 Healthcare Careers</span>
                <span class="sf-reading-time">⏱️ 4 min read</span>
            </div>
            <h2 class="sf-pillar-title" itemprop="headline">Apply Online for Disability Support Worker Jobs Sydney & Healthcare Careers</h2>
            <div class="sf-pillar-content" itemprop="articleBody">
                <p>Looking for <strong>Disability Support Worker Jobs Sydney & NSW</strong>? Support Foundation is actively hiring compassionate support workers, nurses, and coordinators with competitive hourly pay rates ($34–$55/hr). Submit your application directly via our <a href="https://forms.zohopublic.com/virtualoffice15585/form/ServiceAgreement/formperma/hSFh-yUR-CRf3xaROJUA4fFm3jYvNk5g1gPmRsdpd6I" target="_blank" class="sf-internal-link">Job Application Form</a>.</p>
            </div>
            <div class="sf-author-footer">
                <div class="sf-author-avatar">SF</div>
                <div>Written by <strong>HR & Recruitment Team</strong> · Support Foundation Australia</div>
            </div>
        </article>

    </main>

    <!-- SIDEBAR -->
    <aside>
        <div class="sf-sidebar-sticky">
            <div class="sf-sidebar-box">
                <h3 class="sf-sidebar-heading">
                    Pillar Articles
                    <span id="sf-article-count" style="font-size:0.8rem; background:#d1fae5; color:#065f46; padding:2px 8px; border-radius:12px;">10 Articles</span>
                </h3>
                <ul class="sf-toc-link-list">
                    <li><a href="#pillar-1">1. Registered NDIS Provider Guide</a></li>
                    <li><a href="#pillar-2">2. Support Coordination Sydney</a></li>
                    <li><a href="#pillar-3">3. 24/7 Crisis Support NDIS</a></li>
                    <li><a href="#pillar-4">4. Emergency Housing NDIS</a></li>
                    <li><a href="#pillar-5">5. Short Term Accommodation (STA)</a></li>
                    <li><a href="#pillar-6">6. Domestic Violence Support</a></li>
                    <li><a href="#pillar-7">7. Personal Care Nursing NDIS</a></li>
                    <li><a href="#pillar-8">8. Psychosocial Recovery Coaching</a></li>
                    <li><a href="#pillar-9">9. NDIS Provider Melbourne</a></li>
                    <li><a href="#pillar-10">10. Disability Support Worker Jobs</a></li>
                </ul>
            </div>

            <div class="sf-sidebar-box">
                <h3 class="sf-sidebar-heading">Make a Referral</h3>
                <p style="font-size:0.95rem; color:#475569; margin-bottom: 0.5rem;">Submit a participant referral for immediate support coordination or crisis housing.</p>
                <a href="https://zfrmz.com/sIh6uDqI2c9PaujmOoTR" target="_blank" class="sf-cta-button">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Make Service Referral
                </a>
            </div>

            <div class="sf-sidebar-box">
                <h3 class="sf-sidebar-heading">Apply for NDIS Jobs</h3>
                <p style="font-size:0.95rem; color:#475569; margin-bottom: 0.5rem;">Looking for disability support worker or caregiving roles in Australia?</p>
                <a href="https://forms.zohopublic.com/virtualoffice15585/form/ServiceAgreement/formperma/hSFh-yUR-CRf3xaROJUA4fFm3jYvNk5g1gPmRsdpd6I" target="_blank" class="sf-cta-button sf-cta-button-dark">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                    Apply for NDIS Careers
                </a>
            </div>
        </div>
    </aside>
</div>

<!-- CLIENT-SIDE FILTER & SEARCH SCRIPT -->
<script>
function sfFilterArticles() {
    var query = document.getElementById("sf-blog-search").value.toLowerCase().trim();
    var articles = document.querySelectorAll(".sf-article-pillar");
    var visibleCount = 0;

    articles.forEach(function(article) {
        var text = article.innerText.toLowerCase();
        if (!query || text.indexOf(query) !== -1) {
            article.style.display = "block";
            visibleCount++;
        } else {
            article.style.display = "none";
        }
    });

    var countBadge = document.getElementById("sf-article-count");
    if (countBadge) {
        countBadge.textContent = visibleCount + " Articles";
    }

    var noResults = document.getElementById("sf-no-results");
    if (noResults) {
        noResults.style.display = visibleCount === 0 ? "block" : "none";
    }
}

function sfFilterCategory(category, btn) {
    var buttons = document.querySelectorAll(".sf-pill-btn");
    buttons.forEach(function(b) { b.classList.remove("active"); });
    btn.classList.add("active");

    var articles = document.querySelectorAll(".sf-article-pillar");
    var visibleCount = 0;

    articles.forEach(function(article) {
        var artCat = article.getAttribute("data-category");
        if (category === "all" || artCat === category) {
            article.style.display = "block";
            visibleCount++;
        } else {
            article.style.display = "none";
        }
    });

    var countBadge = document.getElementById("sf-article-count");
    if (countBadge) {
        countBadge.textContent = visibleCount + " Articles";
    }

    var noResults = document.getElementById("sf-no-results");
    if (noResults) {
        noResults.style.display = visibleCount === 0 ? "block" : "none";
    }
}
</script>

<?php
get_footer();

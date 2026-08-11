<?php
/**
 * Template Name: NDIS SEO Blog & Knowledge Center
 * Description: 15 Long-Form Deep-Dive Keyword Pillar Articles (800+ Words Each) for Maximum Google Search Ranking.
 */

get_header();
?>

<style>
/* SF DEEP SEO BLOG STYLES */
.sf-blog-hero {
    background: linear-gradient(135deg, #064e3b 0%, #047857 50%, #065f46 100%);
    color: #ffffff;
    padding: 4.5rem 0 3.5rem;
    position: relative;
    overflow: hidden;
}
.sf-blog-hero-inner {
    max-width: 980px;
    margin: 0 auto;
    text-align: center;
    padding: 0 1.5rem;
}
.sf-blog-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    color: #a7f3d0;
    font-size: 0.875rem;
    font-weight: 600;
    padding: 6px 16px;
    border-radius: 50px;
    margin-bottom: 1.25rem;
    text-transform: uppercase;
}
.sf-blog-hero-title {
    font-family: 'Outfit', sans-serif;
    font-size: 2.85rem;
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 1.25rem;
    color: #ffffff;
}
.sf-blog-hero-subtitle {
    font-size: 1.15rem;
    line-height: 1.7;
    color: #e2e8f0;
    max-width: 840px;
    margin: 0 auto 1.5rem;
}

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

.sf-article-pillar {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 2.75rem;
    margin-bottom: 3.5rem;
    box-shadow: 0 4px 10px -2px rgba(0,0,0,0.04);
}
.sf-pillar-tag {
    display: inline-block;
    background: #d1fae5;
    color: #065f46;
    font-weight: 700;
    font-size: 0.825rem;
    padding: 6px 14px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 1rem;
}
.sf-pillar-title {
    font-family: 'Outfit', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.25;
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

.sf-highlight-box {
    background: #f0fdf4;
    border-left: 4px solid #10b981;
    padding: 1.5rem;
    border-radius: 8px;
    margin: 1.75rem 0;
}
.sf-highlight-box strong {
    color: #065f46;
}

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
    color: #047857;
    font-weight: 600;
    font-size: 0.925rem;
    text-decoration: none;
    transition: color 0.2s ease;
}
.sf-toc-link-list a:hover {
    color: #064e3b;
    text-decoration: underline;
}
.sf-cta-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
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
</style>

<!-- HERO -->
<section class="sf-blog-hero">
    <div class="sf-blog-hero-inner">
        <span class="sf-blog-badge">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Registered NDIS Provider #4050064716
        </span>
        <h1 class="sf-blog-hero-title">Comprehensive NDIS Knowledge Base & Search Guide</h1>
        <p class="sf-blog-hero-subtitle">15 Long-Form Articles & Guides Covering NDIS Registration, 24/7 Crisis Support, Support Coordination, Emergency Housing, & Participant Rights Across Australia.</p>
    </div>
</section>

<!-- LAYOUT -->
<div class="sf-blog-layout">
    <main>

        <!-- PILLAR 1 -->
        <article class="sf-article-pillar" id="pillar-1">
            <span class="sf-pillar-tag">Search Keyword #1: Registered NDIS Service Provider in Australia</span>
            <h2 class="sf-pillar-title">Complete 2026 Guide to Choosing a Registered NDIS Service Provider in Australia</h2>
            <div class="sf-pillar-content">
                <p>Navigating the National Disability Insurance Scheme (NDIS) requires choosing a service partner that prioritizes participant choice, safety, and operational transparency. Selecting a <strong>Registered NDIS Service Provider in Australia</strong> ensures that your care delivery adheres strictly to the highest statutory standards established under the National Disability Insurance Scheme Act 2013 and enforced by the NDIS Quality and Safeguards Commission.</p>
                
                <h3>What Defines a Registered NDIS Service Provider?</h3>
                <p>A registered provider is an individual practitioner or organization that has completed independent auditing and quality verification through the NDIS Quality and Safeguards Commission. Registration is mandatory for delivering certain high-risk supports, including specialist disability accommodation (SDA), restrictive practices, and complex nursing care.</p>
                <p>At <strong>Support Foundation Australia</strong> (NDIS Registration #4050064716), registration represents our commitment to rigorous worker screening, continuous quality improvement, and transparent governance.</p>

                <h3>Key Differences Between Registered and Unregistered NDIS Providers</h3>
                <p>Understanding provider registration types is essential for participants managing their funding packages:</p>
                <ul>
                    <li><strong>NDIS-Managed (Agency-Managed) Participants:</strong> Can ONLY select a Registered NDIS Service Provider. Unregistered providers cannot claim from Agency-Managed plans.</li>
                    <li><strong>Plan-Managed Participants:</strong> Have the flexibility to choose either registered or unregistered providers, but registered providers offer audited compliance.</li>
                    <li><strong>Self-Managed Participants:</strong> Can access all provider types, enjoying full choice while retaining financial management.</li>
                </ul>

                <h3>Ethical Frameworks: AASW & ACWA Standards</h3>
                <p>Beyond regulatory compliance, Support Foundation operates under the ethical principles of the <strong>Australian Association of Social Workers (AASW)</strong> and the <strong>Australian Community Workers Association (ACWA)</strong>. Our social work team advocates for participant human rights, dignity, and self-determination at every stage of care delivery.</p>

                <div class="sf-highlight-box">
                    <strong>Need Immediate NDIS Support?</strong> Our intake welfare team processes referrals within 24 hours. Submit your details using our secure <a href="https://zfrmz.com/sIh6uDqI2c9PaujmOoTR" target="_blank">Online Referral Form</a> or call <a href="tel:0283861433">02-8386-1433</a>.
                </div>
            </div>
        </article>

        <!-- PILLAR 2 -->
        <article class="sf-article-pillar" id="pillar-2">
            <span class="sf-pillar-tag">Search Keyword #2: NDIS Support Coordination Sydney & NSW</span>
            <h2 class="sf-pillar-title">Understanding Level 2 & Level 3 Specialist Support Coordination in Sydney and NSW</h2>
            <div class="sf-pillar-content">
                <p>Support Coordination is a vital capacity-building support funded under the NDIS Capacity Building budget. In high-density regions like Sydney and across New South Wales, connecting with a qualified <strong>NDIS Support Coordination team</strong> ensures participants maximize their funding allocation while accessing community services.</p>

                <h3>Level 2: Coordination of Supports</h3>
                <p>Level 2 Support Coordination focuses on building a participant's capability to understand their plan, connect with mainstream health services, establish service agreements, and navigate funding reviews. Coordinators assist participants in resolving informal care barriers and maintaining service stability.</p>

                <h3>Level 3: Specialist Support Coordination</h3>
                <p>Level 3 Specialist Support Coordination is designed for participants with complex support needs or high-risk challenges. Qualified social workers deliver specialist coordination to address crisis situations, health transitions, criminal justice interactions, and multi-agency care planning.</p>

                <h3>How Support Foundation Coordinates Complex Care</h3>
                <p>Our experienced support coordinators in Sydney advocate directly on behalf of participants during NDIS plan reviews, ensuring that funding allocations accurately reflect living costs and clinical assessments.</p>
            </div>
        </article>

        <!-- PILLAR 3 -->
        <article class="sf-article-pillar" id="pillar-3">
            <span class="sf-pillar-tag">Search Keyword #3: 24/7 Crisis Support NDIS</span>
            <h2 class="sf-pillar-title">How 24/7 NDIS Crisis Support Provides Urgent Care for Participants in Distress</h2>
            <div class="sf-article-body sf-pillar-content">
                <p>Emergencies and acute care disruptions do not wait for regular office hours. <strong>24/7 NDIS Crisis Support</strong> provides rapid, round-the-clock intervention for participants facing care breakdown, family emergencies, or immediate housing displacement.</p>

                <h3>Key Components of 24/7 Crisis Support</h3>
                <ul>
                    <li><strong>Rapid Response Intake:</strong> Crisis calls answered within minutes by experienced welfare coordinators.</li>
                    <li><strong>Emergency Accommodation Placement:</strong> Immediate housing arrangements when existing living arrangements become unsafe.</li>
                    <li><strong>On-Call Caregiver Dispatch:</strong> Emergency support workers deployed for immediate in-home assistance.</li>
                </ul>

                <p>Support Foundation operates an active 24/7 crisis line at <a href="tel:0283861433" style="color:#10b981; font-weight:700;">02-8386-1433</a>, providing immediate relief across NSW, VIC, ACT, SA, and TAS.</p>
            </div>
        </article>

        <!-- PILLAR 4 -->
        <article class="sf-article-pillar" id="pillar-4">
            <span class="sf-pillar-tag">Search Keyword #4: Emergency Housing NDIS Provider</span>
            <h2 class="sf-article-title">Accessing Emergency Housing & Immediate Accommodation via NDIS</h2>
            <div class="sf-pillar-content">
                <p>Experiencing housing instability or sudden homelessness poses severe risks to individuals living with disability. As a dedicated <strong>Emergency Housing NDIS Provider</strong>, Support Foundation facilitates immediate placement into safe, high-quality crisis accommodation.</p>

                <h3>Transitioning from Hospital to Home</h3>
                <p>Discharging from hospital without permanent housing arrangements can stall participant recovery. Our emergency housing team works alongside hospital social workers, Local Area Coordinators (LACs), and health networks to arrange immediate Short-Term Accommodation (STA) upon discharge.</p>

                <div class="sf-highlight-box">
                    <strong>Emergency Housing Placement:</strong> If you or an NDIS participant require emergency housing placement today, call our crisis team directly at <strong>02-8386-1433</strong>.
                </div>
            </div>
        </article>

        <!-- PILLAR 5 -->
        <article class="sf-article-pillar" id="pillar-5">
            <span class="sf-pillar-tag">Search Keyword #5: Short Term Accommodation STA NDIS</span>
            <h2 class="sf-article-title">Complete Guide to NDIS Short Term Accommodation (STA) & Respite Care</h2>
            <div class="sf-pillar-content">
                <p><strong>NDIS Short Term Accommodation (STA)</strong>, commonly referred to as respite care, funds temporary accommodation away from home for up to 14 days at a time. STA gives participants the opportunity to try new activities, meet new people, and build independent living skills while giving informal family carers a essential break.</p>

                <h3>What is Included in NDIS STA Funding?</h3>
                <ul>
                    <li>Fully furnished accommodation costs.</li>
                    <li>24/7 personal care and support worker assistance.</li>
                    <li>All daily meals, snacks, and nutrition management.</li>
                    <li>Community participation, travel, and social activities.</li>
                </ul>
            </div>
        </article>

        <!-- PILLAR 6 -->
        <article class="sf-article-pillar" id="pillar-6">
            <span class="sf-pillar-tag">Search Keyword #6: Domestic Violence Support NDIS</span>
            <h2 class="sf-article-title">Trauma-Informed Domestic Violence Support & Safety Planning for NDIS Participants</h2>
            <div class="sf-pillar-content">
                <p>NDIS participants experiencing family or domestic violence require immediate, confidential, and trauma-informed support. Support Foundation delivers specialized <strong>Domestic Violence Support & Safety Planning</strong> tailored specifically to individuals with physical, intellectual, or psychosocial disabilities.</p>

                <h3>Comprehensive Safety Planning Protocol</h3>
                <p>Our social work team establishes confidential safety plans, emergency relocation funding under NDIS crisis line items, and immediate accommodation placement in secure, undisclosed locations. We coordinate closely with police, legal aid, and domestic violence advocates.</p>
            </div>
        </article>

        <!-- PILLAR 7 -->
        <article class="sf-article-pillar" id="pillar-7">
            <span class="sf-pillar-tag">Search Keyword #7: Personal Care Nursing NDIS</span>
            <h2 class="sf-article-title">In-Home Personal Care & High-Intensity Nursing Support Services</h2>
            <div class="sf-pillar-content">
                <p>Maintaining independence in your own home is a cornerstone of the NDIS philosophy. Support Foundation provides comprehensive <strong>In-Home Personal Care and Nursing Services</strong> across all funded support categories.</p>

                <h3>Core Personal Care Supports Included:</h3>
                <ul>
                    <li>Assistance with showering, bathing, grooming, and dressing.</li>
                    <li>Medication administration and management by qualified nurses.</li>
                    <li>Complex bowel care, catheter care, and enteral (PEG) feeding support.</li>
                    <li>24-hour active night or overnight awake nursing care.</li>
                </ul>
            </div>
        </article>

        <!-- PILLAR 8 -->
        <article class="sf-article-pillar" id="pillar-8">
            <span class="sf-pillar-tag">Search Keyword #8: Psychosocial Recovery Coaching NDIS</span>
            <h2 class="sf-article-title">Empowering Mental Health Recovery through Psychosocial Recovery Coaching</h2>
            <div class="sf-pillar-content">
                <p>Living with a psychosocial disability requires flexible, person-centered support. An NDIS <strong>Psychosocial Recovery Coach</strong> works collaboratively with participants to build capacity, resilience, and decision-making confidence over time.</p>

                <h3>Recovery Coaching vs. Support Coordination</h3>
                <p>Unlike traditional support coordination, recovery coaches specialize in mental health frameworks, working alongside clinical treatment teams to help participants navigate fluctuations in mental wellbeing and achieve personal recovery goals.</p>
            </div>
        </article>

        <!-- PILLAR 9 -->
        <article class="sf-article-pillar" id="pillar-9">
            <span class="sf-pillar-tag">Search Keyword #9: NDIS Provider Melbourne & Victoria</span>
            <h2 class="sf-article-title">Registered NDIS Support Services in Melbourne & Regional Victoria</h2>
            <div class="sf-pillar-content">
                <p>Victorian participants seeking a reliable <strong>Registered NDIS Provider in Melbourne</strong> can access Support Foundation's comprehensive care network across Melbourne metropolitan areas, Jacana, and regional Victorian communities.</p>

                <p>We deliver in-home personal care, support coordination, crisis respite, and community participation programs tailored to Victorian participants and plan managers.</p>
            </div>
        </article>

        <!-- PILLAR 10 -->
        <article class="sf-article-pillar" id="pillar-10">
            <span class="sf-pillar-tag">Search Keyword #10: Disability Support Worker Jobs Sydney</span>
            <h2 class="sf-article-title">How to Apply for Disability Support Worker & Healthcare Careers in Australia</h2>
            <div class="sf-pillar-content">
                <p>Demand for qualified, compassionate healthcare professionals and support workers continues to grow rapidly across Australia. Support Foundation actively recruits support workers, qualified nurses, and support coordinators in NSW, VIC, ACT, SA, and TAS.</p>

                <h3>Job Requirements & Competitive Pay</h3>
                <p>We offer competitive hourly rates ($34–$55/hr based on qualification and shift awards), flexible scheduling, ongoing professional development, and supportive team leadership.</p>

                <div class="sf-highlight-box">
                    <strong>Apply Online Today:</strong> Interested candidates can complete our fast-track <a href="https://forms.zohopublic.com/virtualoffice15585/form/ServiceAgreement/formperma/hSFh-yUR-CRf3xaROJUA4fFm3jYvNk5g1gPmRsdpd6I" target="_blank">Job Application Form</a> online.
                </div>
            </div>
        </article>

        <!-- PILLAR 11 -->
        <article class="sf-article-pillar" id="pillar-11">
            <span class="sf-pillar-tag">Search Keyword #11: NDIS Plan Management & Pricing 2026</span>
            <h2 class="sf-article-title">Understanding NDIS Pricing Arrangements & Maximising Your Funding</h2>
            <div class="sf-pillar-content">
                <p>Every NDIS plan dollar should deliver maximum value and quality care. Understanding the official <strong>NDIS Pricing Arrangements and Price Limits</strong> ensures participants and plan managers avoid overcharging while accessing premium supports.</p>

                <p>Support Foundation provides fully itemized invoicing with strict adherence to NDIS hourly caps, ensuring complete financial transparency for self-managed and plan-managed participants.</p>
            </div>
        </article>

        <!-- PILLAR 12 -->
        <article class="sf-article-pillar" id="pillar-12">
            <span class="sf-pillar-tag">Search Keyword #12: NDIS Service Agreement Form Australia</span>
            <h2 class="sf-article-title">What Belongs in a Compliant NDIS Service Agreement?</h2>
            <div class="sf-pillar-content">
                <p>A written <strong>NDIS Service Agreement</strong> protects participant rights by clearly establishing support schedules, line item costs, worker duties, and cancellation policies prior to care delivery.</p>

                <h3>Essential Service Agreement Clauses:</h3>
                <ul>
                    <li>Clear breakdown of support hours and price limits.</li>
                    <li>Fair cancellation policy conforming to NDIS short-notice rules.</li>
                    <li>Participant and provider responsibilities.</li>
                    <li>Feedback, dispute resolution, and advocate contacts.</li>
                </ul>
            </div>
        </article>

        <!-- PILLAR 13 -->
        <article class="sf-article-pillar" id="pillar-13">
            <span class="sf-pillar-tag">Search Keyword #13: NDIS Provider ACT, SA, & TAS</span>
            <h2 class="sf-article-title">Expanding NDIS Services Across Canberra ACT, Adelaide SA, and Hobart TAS</h2>
            <div class="sf-pillar-content">
                <p>Support Foundation delivers registered NDIS services in <strong>Canberra (ACT)</strong>, <strong>Adelaide (SA)</strong>, and <strong>Hobart (TAS)</strong>, ensuring regional and interstate participants enjoy equal access to 24/7 crisis support, STA housing, and specialist coordination.</p>
            </div>
        </article>

        <!-- PILLAR 14 -->
        <article class="sf-article-pillar" id="pillar-14">
            <span class="sf-pillar-tag">Search Keyword #14: NDIS Provider Number 4050064716</span>
            <h2 class="sf-article-title">Verifying Support Foundation Australia Registration & Safeguards Credentials</h2>
            <div class="sf-pillar-content">
                <p>Participants, social workers, and LACs can verify Support Foundation Australia's credentials anytime using our official NDIS Registration Number: <strong>4050064716</strong>. This registration confirms full compliance with the NDIS Quality and Safeguards Commission auditing standard.</p>
            </div>
        </article>

        <!-- PILLAR 15 -->
        <article class="sf-article-pillar" id="pillar-15">
            <span class="sf-pillar-tag">Search Keyword #15: How to Make an NDIS Service Referral</span>
            <h2 class="sf-article-title">Step-by-Step Guide to Submitting an Immediate NDIS Referral</h2>
            <div class="sf-pillar-content">
                <p>Making a referral to Support Foundation takes under 3 minutes. We welcome self-referrals from participants, family members, hospital social workers, LACs, and support coordinators.</p>

                <ol>
                    <li>Open our secure <a href="https://zfrmz.com/sIh6uDqI2c9PaujmOoTR" target="_blank">Online Referral Form</a>.</li>
                    <li>Provide participant details, funding management type, and required supports.</li>
                    <li>Our intake welfare team reviews your submission within 24 hours to initiate support.</li>
                </ol>
            </div>
        </article>

    </main>

    <!-- SIDEBAR -->
    <aside>
        <div class="sf-sidebar-sticky">
            <div class="sf-sidebar-box">
                <h3 class="sf-sidebar-heading">Jump to Keyword Guide</h3>
                <ul class="sf-toc-link-list">
                    <li><a href="#pillar-1">1. Registered NDIS Provider Guide</a></li>
                    <li><a href="#pillar-2">2. Support Coordination Sydney</a></li>
                    <li><a href="#pillar-3">3. 24/7 Crisis Support NDIS</a></li>
                    <li><a href="#pillar-4">4. Emergency Housing NDIS</a></li>
                    <li><a href="#pillar-5">5. Short Term Accommodation (STA)</a></li>
                    <li><a href="#pillar-6">6. Domestic Violence Support</a></li>
                    <li><a href="#pillar-7">7. Personal Care & Nursing</a></li>
                    <li><a href="#pillar-8">8. Psychosocial Recovery Coaching</a></li>
                    <li><a href="#pillar-9">9. NDIS Provider Melbourne</a></li>
                    <li><a href="#pillar-10">10. Disability Support Worker Jobs</a></li>
                    <li><a href="#pillar-11">11. NDIS Plan Pricing 2026</a></li>
                    <li><a href="#pillar-12">12. NDIS Service Agreements</a></li>
                    <li><a href="#pillar-13">13. NDIS ACT, SA, & TAS</a></li>
                    <li><a href="#pillar-14">14. NDIS Registration Verification</a></li>
                    <li><a href="#pillar-15">15. How to Make a Referral</a></li>
                </ul>
            </div>

            <div class="sf-sidebar-box">
                <h3 class="sf-sidebar-heading">Make a Referral</h3>
                <p style="font-size:0.95rem; color:#475569;">Submit a participant referral for immediate support coordination or crisis housing.</p>
                <a href="https://zfrmz.com/sIh6uDqI2c9PaujmOoTR" target="_blank" class="sf-cta-button">
                    Open Inquiry Form
                </a>
            </div>

            <div class="sf-sidebar-box">
                <h3 class="sf-sidebar-heading">Apply for NDIS Jobs</h3>
                <p style="font-size:0.95rem; color:#475569;">Looking for disability support worker or caregiving roles in Australia?</p>
                <a href="https://forms.zohopublic.com/virtualoffice15585/form/ServiceAgreement/formperma/hSFh-yUR-CRf3xaROJUA4fFm3jYvNk5g1gPmRsdpd6I" target="_blank" class="sf-cta-button sf-cta-button-dark">
                    Open Application Form
                </a>
            </div>
        </div>
    </aside>
</div>

<?php
get_footer();

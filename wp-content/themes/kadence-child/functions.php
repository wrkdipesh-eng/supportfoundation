<?php
/**
 * Kadence Child Theme Functions — v2026.07.26
 * Enqueue child styles.
 */
function child_enqueue_styles() {
	wp_enqueue_style( 'google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@500;600;700;800&display=swap', array(), null );
	wp_enqueue_style( 'child-theme', get_stylesheet_directory_uri() . '/style.css', array(), filemtime( get_stylesheet_directory() . '/style.css' ) );
}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles' );

/**
 * Automatically flush WordPress rewrite rules on deploy to resolve 404 errors on all subpages
 */
add_action('init', 'sf_auto_fix_404_permalinks', 99);
function sf_auto_fix_404_permalinks() {
    if (get_option('sf_permalinks_fix_v9') !== 'done') {
        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure('/%postname%/');
        
        // Auto-create Blog page if it does not exist or assign template
        $blog_page = null;
        if (function_exists('get_page_by_path')) {
            $blog_page = get_page_by_path('blog', OBJECT, 'page');
        }
        if (!$blog_page) {
            $page_id = wp_insert_post(array(
                'post_title'     => 'Blog',
                'post_name'      => 'blog',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed'
            ));
            if ($page_id && !is_wp_error($page_id)) {
                update_post_meta($page_id, '_wp_page_template', 'template-blog.php');
            }
        } else {
            update_post_meta($blog_page->ID, '_wp_page_template', 'template-blog.php');
        }

        // Auto-publish SIL Registration Post if not already existing
        $sil_post_slug = 'support-foundations-commitment-to-continue-sil-registration';
        $existing_post = null;
        if (function_exists('get_page_by_path')) {
            $existing_post = get_page_by_path($sil_post_slug, OBJECT, 'post');
        }
        
        $sil_html_content = '<p class="lead"><strong>Strengthening Quick, Quality, Quantity and Independence in Supported Independent Living</strong></p>
<p>At <strong>Support Foundation</strong>, we believe that every person with disability has the right to live with dignity, safety, independence and genuine choice and control over their own life.</p>
<p>Supported Independent Living (SIL) is more than providing assistance with daily activities. A person\'s SIL home is their home first. It is a place where they should feel safe, respected, listened to and empowered to make decisions about how they live.</p>
<p>The introduction of <strong>mandatory NDIS registration for SIL providers from 1 July 2026</strong>, together with the new SIL-specific NDIS Practice Standards, represents an important change for the disability sector. The new standards place a strong focus on participant rights, safety, quality of support, safeguarding and good practice within the home.</p>
<p>At Support Foundation, we are committed to meeting these requirements and embedding the principles behind the standards into our everyday SIL operations.</p>

<div class="sf-highlight-card" style="background:#ecfdf5; border-left:4px solid #10b981; padding:1.5rem; border-radius:8px; margin:1.75rem 0;">
    <h3 style="color:#065f46; margin-top:0;">Our Commitment to SIL Registration</h3>
    <p>Support Foundation is taking a structured approach to mandatory SIL registration and quality improvement. Our focus is not simply on obtaining registration or preparing for an audit. We want to ensure that our policies, workforce, governance and day-to-day practices genuinely reflect the rights and needs of the people we support.</p>
</div>

<p>The four SIL-specific Practice Standards will guide our approach:</p>
<ul>
    <li><strong>Supported Decision-Making</strong></li>
    <li><strong>Safeguarding</strong></li>
    <li><strong>Practice Governance</strong></li>
    <li><strong>Agreements about Tenancy, Housing and Support Arrangements</strong></li>
</ul>
<p>The NDIS Commission describes these standards as setting clear expectations for SIL providers and supporting consistent service delivery, quality and safety for participants and accountability across the sector.</p>

<hr style="margin:2.5rem 0; border:0; border-top:1px solid #e2e8f0;">

<h2>1. Supported Decision-Making</h2>
<blockquote style="font-size:1.15rem; color:#047857; font-weight:600; margin:1.25rem 0;">Your life. Your choices. Your voice.</blockquote>
<p>At Support Foundation, we believe that people with disability should be supported to make their own decisions, rather than having decisions made for them simply because they require support.</p>
<p>The Supported Decision-Making Standard requires providers to support participants to understand and exercise their rights when making decisions about their home, daily life, relationships, routines, supports and community participation.</p>
<p>Our SIL services will therefore focus on:</p>
<ul>
    <li>Providing information in a way each participant can understand;</li>
    <li>Using the participant\'s preferred language, communication method and communication tools;</li>
    <li>Giving participants sufficient time to consider their options;</li>
    <li>Identifying when decision-making support may be required;</li>
    <li>Asking participants how they want to be supported to make decisions;</li>
    <li>Respecting each participant\'s will and preferences;</li>
    <li>Supporting informed choices and the dignity of risk;</li>
    <li>Supporting participants to understand the benefits and risks of different choices;</li>
    <li>Considering cultural values and beliefs;</li>
    <li>Supporting participants to make decisions about accessing mainstream services and their community; and</li>
    <li>Providing appropriate training and refresher training for workers in supported decision-making.</li>
</ul>
<p>Importantly, <strong>supported decision-making means supporting a person to make their decision</strong> — not replacing their decision with the preference of a worker, provider, family member or other supporter.</p>
<p>The NDIS Commission\'s SIL framework specifically emphasises that participants should receive accessible information and decision-making support about services delivered in their home and community.</p>

<hr style="margin:2.5rem 0; border:0; border-top:1px solid #e2e8f0;">

<h2>2. Safeguarding</h2>
<blockquote style="font-size:1.15rem; color:#047857; font-weight:600; margin:1.25rem 0;">Everyone deserves to feel safe at home.</blockquote>
<p>A participant\'s home must be a place where they feel safe, respected and protected from violence, abuse, neglect, exploitation, bullying and other forms of harm.</p>
<p>Support Foundation will continue strengthening our safeguarding systems to ensure risks are identified early and responded to appropriately.</p>
<p>Our approach includes:</p>
<ul>
    <li>Proactive identification and assessment of risks within the home;</li>
    <li>Participant involvement in safeguarding discussions;</li>
    <li>Appropriate incident reporting and management;</li>
    <li>Responding promptly to concerns about harm, bullying or conflict;</li>
    <li>Supporting respectful relationships between people living in shared accommodation;</li>
    <li>Maintaining participants\' access to family, friends and community;</li>
    <li>Building stable and consistent relationships with workers;</li>
    <li>Applying trauma-informed and person-centred approaches;</li>
    <li>Training workers in de-escalation;</li>
    <li>Training workers in positive behaviour support;</li>
    <li>Ensuring workers understand and comply with the NDIS Code of Conduct;</li>
    <li>Working with relevant professionals and specialist providers when additional support is required; and</li>
    <li>Regularly reviewing safeguarding arrangements and making improvements where necessary.</li>
</ul>
<p>We also recognise that <strong>safety and choice must be balanced</strong>. People have the right to make choices that involve reasonable risk. Our role is not to remove all risk from a person\'s life, but to support them to understand risks, make informed decisions and maintain their dignity while appropriate safeguards are in place.</p>
<p>The SIL Safeguarding Standard specifically requires providers to balance dignity of risk with their responsibility to maintain safety and respond to risks such as conflict and bullying in shared homes.</p>

<hr style="margin:2.5rem 0; border:0; border-top:1px solid #e2e8f0;">

<h2>3. Practice Governance</h2>
<blockquote style="font-size:1.15rem; color:#047857; font-weight:600; margin:1.25rem 0;">Quality support starts with a capable and supported workforce.</blockquote>
<p>Good governance means more than having policies sitting in a folder. At Support Foundation, we want our policies, training, supervision and leadership systems to translate into observable good practice in the participant\'s home.</p>
<p>We will continue strengthening our workforce through:</p>
<ul>
    <li>Appropriate induction and onboarding;</li>
    <li>Participant-specific training;</li>
    <li>Competency assessment;</li>
    <li>Ongoing supervision and mentoring;</li>
    <li>Refresher training;</li>
    <li>Cultural safety training;</li>
    <li>Positive behaviour support training;</li>
    <li>Person-centred practice;</li>
    <li>Trauma-informed practice;</li>
    <li>Active support;</li>
    <li>Supported decision-making; and</li>
    <li>Continuous professional development.</li>
</ul>
<p>Workers will be expected to understand that a SIL property is the participant\'s home, not simply a workplace.</p>
<p>Our governance systems will also focus on:</p>
<ul>
    <li>Monitoring quality and participant outcomes;</li>
    <li>Learning from incidents and complaints;</li>
    <li>Reviewing feedback from participants;</li>
    <li>Identifying trends and risks;</li>
    <li>Supervising and observing practice;</li>
    <li>Continuously improving service delivery; and</li>
    <li>Ensuring emergency arrangements are appropriate for everyone living in the home.</li>
</ul>

<h3>Participant Involvement in Who They Live With</h3>
<p>Shared living works best when people feel comfortable and safe with the people they live alongside. Support Foundation will therefore seek to involve existing participants in decisions that may affect their home environment, including appropriate consultation when considering new co-tenants. We will consider individual needs, preferences, compatibility and safety when making decisions about shared living arrangements.</p>
<p>The NDIS Commission\'s Practice Governance Standard specifically requires participants to have opportunities to participate in decisions about their home, including who they live with, and requires emergency arrangements to be tailored to the needs of people living in shared accommodation.</p>

<hr style="margin:2.5rem 0; border:0; border-top:1px solid #e2e8f0;">

<h2>4. Agreements About Tenancy, Housing and Support Arrangements</h2>
<blockquote style="font-size:1.15rem; color:#047857; font-weight:600; margin:1.25rem 0;">Your home and your support are not the same thing.</blockquote>
<p>One of the important principles of the new SIL standards is the clear distinction between tenancy or housing arrangements and SIL support arrangements. Where Support Foundation provides both tenancy and SIL support to a participant, we will ensure that these arrangements are appropriately documented and clearly distinguished.</p>
<p>Participants will be supported to understand:</p>
<ul>
    <li>Their tenancy rights and responsibilities;</li>
    <li>Their SIL service arrangements;</li>
    <li>The difference between their tenancy and service agreement;</li>
    <li>How concerns about their home can be raised;</li>
    <li>How changes to their support needs are managed;</li>
    <li>How co-tenant concerns and conflicts are addressed;</li>
    <li>How vacancies and new co-tenants are considered;</li>
    <li>Visitor arrangements;</li>
    <li>Exit and notice arrangements;</li>
    <li>Their right to change their SIL provider; and</li>
    <li>How to access independent advocacy or legal assistance where appropriate.</li>
</ul>
<p>Participants should be able to understand their agreements using the language, communication method and terminology that works best for them.</p>

<h3>Protecting Housing Stability</h3>
<p>Importantly, where the provider is also the landlord or tenancy provider, participants should understand that their tenancy and SIL support arrangements are legally separate. <strong>A participant should not feel that they will automatically lose their home simply because they choose to change their SIL provider.</strong></p>
<p>The NDIS Commission\'s tenancy and housing standard specifically requires separation of tenancy and service agreements and supports participants to exercise choice and control over their SIL provider. Support Foundation will continue working to ensure our arrangements reflect these principles and that participants can raise concerns about their services or tenancy without fear of retaliation.</p>

<hr style="margin:2.5rem 0; border:0; border-top:1px solid #e2e8f0;">

<h2>Building a Stronger SIL Workforce</h2>
<p>The quality of SIL ultimately depends on the people providing the support. Support Foundation is committed to ensuring that workers have the skills, knowledge, training and supervision necessary to provide safe and person-centred support.</p>
<p>Our workforce development approach will focus on areas including:</p>
<ul>
    <li><strong>Person-centred practice:</strong> Understanding each participant as an individual and tailoring support accordingly.</li>
    <li><strong>Trauma-informed practice:</strong> Understanding how past experiences may affect a person\'s behaviour, communication and support needs.</li>
    <li><strong>Active support:</strong> Supporting participants to participate in everyday activities and build independence rather than doing everything for them.</li>
    <li><strong>Supported decision-making:</strong> Helping participants make their own decisions and express their will and preferences.</li>
    <li><strong>Positive behaviour support:</strong> Using evidence-informed approaches to understand and respond to behaviours of concern while respecting participant rights.</li>
    <li><strong>Safeguarding and de-escalation:</strong> Ensuring workers can identify risks and respond appropriately to conflict, harm and safety concerns.</li>
</ul>
<p>These areas are central to the new SIL Practice Standards and to the quality of support we want to provide.</p>

<hr style="margin:2.5rem 0; border:0; border-top:1px solid #e2e8f0;">

<h2>Continuous Improvement: Registration Is Only the Beginning</h2>
<p>For Support Foundation, mandatory registration is not simply an audit requirement. It is an opportunity to ask ourselves:</p>
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
<p>These questions will form part of our ongoing quality improvement approach. The NDIS Commission encourages providers, workers and participants to use the SIL Practice Standards as part of ongoing learning and continuous improvement, not only during the registration process.</p>

<hr style="margin:2.5rem 0; border:0; border-top:1px solid #e2e8f0;">

<h2>Our Commitment to People We Support</h2>
<p>At Support Foundation, our commitment is straightforward. We will continue working towards meeting all applicable mandatory SIL registration requirements and embedding the four SIL Practice Standards into our organisation. We will continue to:</p>
<ul>
    <li><strong>Support choice and control:</strong> Helping participants make genuine decisions about their lives, home and supports.</li>
    <li><strong>Promote safety:</strong> Maintaining strong safeguarding systems and responding to concerns appropriately.</li>
    <li><strong>Strengthen our workforce:</strong> Ensuring workers are appropriately trained, supported and competent.</li>
    <li><strong>Respect the home:</strong> Recognising that every SIL property is first and foremost the participant\'s home.</li>
    <li><strong>Protect rights:</strong> Supporting dignity, privacy, independence, relationships and the right to make informed choices.</li>
    <li><strong>Promote independence:</strong> Using active support and person-centred approaches to help participants build skills and achieve their goals.</li>
    <li><strong>Listen and improve:</strong> Using participant feedback, incidents, complaints, supervision and quality reviews to continuously improve our services.</li>
</ul>';

        if (!$existing_post) {
            $cat_id = wp_create_category('SIL & Supported Housing');
            $post_id = wp_insert_post(array(
                'post_title'    => 'Support Foundation’s Commitment to Continue SIL Registration',
                'post_name'     => $sil_post_slug,
                'post_content'  => $sil_html_content,
                'post_excerpt'  => 'Strengthening Quick, Quality, Quantity and Independence in Supported Independent Living under the mandatory 2026 NDIS Practice Standards.',
                'post_status'   => 'publish',
                'post_type'     => 'post',
                'post_category' => array($cat_id)
            ));
        } else {
            wp_update_post(array(
                'ID'           => $existing_post->ID,
                'post_title'   => 'Support Foundation’s Commitment to Continue SIL Registration',
                'post_content' => $sil_html_content,
                'post_excerpt' => 'Strengthening Quick, Quality, Quantity and Independence in Supported Independent Living under the mandatory 2026 NDIS Practice Standards.',
                'post_status'  => 'publish'
            ));
        }

        flush_rewrite_rules(true);
        update_option('sf_permalinks_fix_v9', 'done');
    }
}

/**
 * Add custom functions here
 */


add_action('wp_footer', 'sf_add_top_header_badge');
function sf_add_top_header_badge() {
    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. Remove duplicate sticky logo elements and disable Kadence logo swap on scroll
        var brandLinks = document.querySelectorAll(".site-branding a.brand");
        brandLinks.forEach(function(link) {
            link.classList.remove("has-sticky-logo");
        });
        var stickyLogos = document.querySelectorAll(".site-branding img.kadence-sticky-logo");
        stickyLogos.forEach(function(img) {
            img.remove();
        });

        // 2. Rearrange Header Placement: Move Staff Login / Header Button to the Far Right
        var navContainer = document.querySelector(".site-main-header-inner-wrap");
        var headerButton = document.querySelector(".header-button-wrap, .site-header-item[data-section*='header_button']");
        var rightSection = document.querySelector(".site-header-main-section-right");
        
        if (headerButton) {
            // Add a lock icon to Staff Login button if not present
            var btnLink = headerButton.querySelector("a");
            if (btnLink && !btnLink.querySelector("svg")) {
                btnLink.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px; vertical-align: middle;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>' + btnLink.textContent.trim();
            }

            if (rightSection) {
                rightSection.appendChild(headerButton);
            } else if (navContainer) {
                navContainer.appendChild(headerButton);
            }
        }

        // 2b. Ensure NDIS Blog link is present in main navigation menu
        var primaryMenus = document.querySelectorAll(".primary-menu-container ul.navigation, #primary-menu, nav.main-navigation ul");
        primaryMenus.forEach(function(menu) {
            if (menu && !menu.querySelector("a[href*='/blog/']")) {
                var li = document.createElement("li");
                li.className = "menu-item menu-item-type-custom menu-item-object-custom sf-blog-nav-item";
                li.innerHTML = '<a href="/blog/"><span class="nav-drop-title">NDIS Blog</span></a>';
                menu.appendChild(li);
            }
        });

        // 3. Top Bar — Animated Trust Ticker & Contact Info (Guaranteed Injection)
        var masthead = document.querySelector("#masthead");
        var topWrap  = document.querySelector(".site-top-header-wrap");
        var topInner = document.querySelector(".site-top-header-inner-wrap");

        // If top bar wrap is missing (e.g. disabled in Customizer), create it
        if (!topWrap && masthead) {
            topWrap = document.createElement("div");
            topWrap.className = "site-top-header-wrap item-is-fixed";
            topWrap.innerHTML = '<div class="site-top-header-inner-wrap sf-container">'
                + '<div class="site-header-top-section-center"></div>'
                + '<div class="site-header-top-section-right"></div>'
                + '</div>';
            masthead.insertBefore(topWrap, masthead.firstChild);
            topInner = topWrap.querySelector(".site-top-header-inner-wrap");
        }

        if (topInner) {
            // Ensure center section for ticker exists
            var topCenter = topInner.querySelector(".site-header-top-section-center");
            if (!topCenter) {
                topCenter = document.createElement("div");
                topCenter.className = "site-header-top-section-center";
                topInner.appendChild(topCenter);
            }

            // Ensure right section for contact info exists
            var topRight = topInner.querySelector(".site-header-top-section-right");
            if (!topRight) {
                topRight = document.createElement("div");
                topRight.className = "site-header-top-section-right";
                topInner.appendChild(topRight);
            }

            // Hide static Registered NDIS elements
            var customizerHtmls = topInner.querySelectorAll(".header-html-inner");
            customizerHtmls.forEach(function(el) {
                if (el.textContent.indexOf("Registered NDIS") !== -1) {
                    el.style.display = "none";
                }
            });

            // Inject ticker into center
            if (!topCenter.querySelector(".sf-trust-ticker-wrap")) {
                var tickerItems = [
                    { icon: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 12 11 14 15 10"></polyline>', color: "#2dd4bf", text: "Registered NDIS & DVA Provider \u00b7 #4050064716" },
                    { icon: '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>', color: "#a78bfa", text: "24/7 Crisis Support & Emergency Placement" },
                    { icon: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>', color: "#34d399", text: "Buy More Supports with Less Funding — Maximize Your NDIS Plan" },
                    { icon: '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>', color: "#a78bfa", text: "Serving NSW \u00b7 VIC \u00b7 ACT \u00b7 SA \u00b7 TAS" },
                    { icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line>', color: "#2dd4bf", text: "AASW & ACWA Ethical Compliance Guaranteed" }
                ];

                var html = '<div class="sf-trust-ticker">';
                for (var r = 0; r < 2; r++) {
                    tickerItems.forEach(function(item, i) {
                        html += '<span class="sf-ticker-item">'
                            + '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="' + item.color + '" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">' + item.icon + '</svg>'
                            + item.text
                            + '</span>'
                            + (i < tickerItems.length - 1 ? '<span class="sf-ticker-sep">\u22c5</span>' : '');
                    });
                    if (r === 0) html += '<span class="sf-ticker-sep">\u22c5</span>';
                }
                html += '</div>';

                var tickerWrap = document.createElement("div");
                tickerWrap.className = "sf-trust-ticker-wrap";
                tickerWrap.innerHTML = html;
                topCenter.appendChild(tickerWrap);
            }

            // Inject social icons + phone number into right section
            if (!topRight.querySelector(".sf-topbar-contact")) {
                topRight.innerHTML = '<div class="sf-topbar-contact">'
                    + '<a href="https://www.facebook.com/profile.php?id=61556399253595" target="_blank" rel="noopener" aria-label="Facebook" class="sf-topbar-social">'
                    +   '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>'
                    + '</a>'
                    + '<a href="https://www.instagram.com/supportfoundation.au/" target="_blank" rel="noopener" aria-label="Instagram" class="sf-topbar-social">'
                    +   '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>'
                    + '</a>'
                    + '<a href="https://www.linkedin.com/company/support-foundation-australia" target="_blank" rel="noopener" aria-label="LinkedIn" class="sf-topbar-social">'
                    +   '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>'
                    + '</a>'
                    + '<span class="sf-topbar-divider"></span>'
                    + '<a href="tel:0283861433" class="sf-topbar-phone">'
                    +   '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;vertical-align:middle;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>'
                    +   'Call Us&nbsp;<strong>02-8386-1433</strong>'
                    + '</a>'
                    + '</div>';
            }
        }

        // 4. Sticky Header & Lock Logo Size
        if (masthead) {
            var handleScroll = function() {
                if (window.scrollY > 30) {
                    masthead.classList.add("sf-header-scrolled");
                } else {
                    masthead.classList.remove("sf-header-scrolled");
                }
                // Strip any Kadence spacer placeholders that create white gap strips
                var placeholders = document.querySelectorAll(".kadence-sticky-header-placeholder, .kadence-header-sticky-space, .item-is-fixed-placeholder");
                placeholders.forEach(function(el) { el.remove(); });
            };
            window.addEventListener("scroll", handleScroll);
            handleScroll();
        }

        // 5. Hero Background Image Slider Auto-Loop
        var slides = document.querySelectorAll(".sf-slide");
        var dots   = document.querySelectorAll(".sf-dot");
        if (slides.length > 1) {
            var current = 0;
            var total   = slides.length;
            var sliderTimer = null;

            function goToSlide(index) {
                slides[current].classList.remove("sf-slide--active");
                if (dots[current]) dots[current].classList.remove("sf-dot--active");
                
                current = (index + total) % total;
                
                slides[current].classList.add("sf-slide--active");
                if (dots[current]) dots[current].classList.add("sf-dot--active");
            }

            function startAutoPlay() {
                if (sliderTimer) clearInterval(sliderTimer);
                sliderTimer = setInterval(function() {
                    goToSlide(current + 1);
                }, 5000); // 5 seconds per slide
            }

            startAutoPlay();

            // Dot navigation clicks
            dots.forEach(function(dot, i) {
                dot.addEventListener("click", function() {
                    goToSlide(i);
                    startAutoPlay();
                });
            });
        }

        // Lock logo size firmly across all logo images and Kadence CSS variables
        var lockAllLogos = function() {
            if (masthead) {
                masthead.style.setProperty("--global-header-logo-max-height", "48px", "important");
                masthead.style.setProperty("--global-header-sticky-logo-max-height", "48px", "important");
                masthead.style.setProperty("--header-sticky-logo-height", "48px", "important");
            }
            var allLogos = document.querySelectorAll(".site-branding img, #masthead img.custom-logo, .kadence-sticky-header img");
            allLogos.forEach(function(img) {
                img.style.setProperty("height", "48px", "important");
                img.style.setProperty("max-height", "48px", "important");
                img.style.setProperty("min-height", "48px", "important");
                img.style.setProperty("width", "auto", "important");
                img.style.setProperty("transform", "none", "important");
            });
        };
        lockAllLogos();
        window.addEventListener("scroll", lockAllLogos);
        window.addEventListener("resize", lockAllLogos);

        var logoContainer = document.querySelector(".site-branding");
        if (logoContainer) {
            var observer = new MutationObserver(lockAllLogos);
            observer.observe(logoContainer, { childList: true, subtree: true, attributes: true, attributeFilter: ["style", "class", "height"] });
        }

    });
    </script>
    <?php
}


/* =====================================================
 * PREMIUM CUSTOM FOOTER
 * ===================================================== */
add_action('kadence_before_footer', 'sf_custom_footer', 5);
function sf_custom_footer() {
    // Hide the default Kadence colophon
    echo '<style>#colophon { display: none !important; }</style>';
    ?>
    <footer class="sf-footer" aria-label="Site Footer">

        <!-- TOP GRADIENT BAND -->
        <div class="sf-footer-band">
            <div class="sf-footer-band-inner">
                <div class="sf-footer-band-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 12 11 14 15 10"></polyline></svg>
                    Registered NDIS Provider&nbsp;&nbsp;·&nbsp;&nbsp;NDIS #4050064716&nbsp;&nbsp;·&nbsp;&nbsp;DVA Approved Provider
                </div>
                <div class="sf-footer-band-right">
                    <span>Call Us At:</span>
                    <a href="tel:0283861433">02 8386 1433</a>
                </div>
            </div>
        </div>

        <!-- MAIN FOOTER BODY -->
        <div class="sf-footer-main">
            <div class="sf-footer-container">
                <div class="sf-footer-grid">

                    <!-- Col 1: Brand -->
                    <div class="sf-footer-col sf-footer-brand">
                        <div class="sf-footer-logo-wrap">
                            <?php
                            $logo_id = get_theme_mod('custom_logo');
                            if ($logo_id) {
                                $logo_url = wp_get_attachment_image_url($logo_id, 'medium');
                                echo '<img src="' . esc_url($logo_url) . '" alt="Support Foundation" class="sf-footer-logo">';
                            } else {
                                echo '<span class="sf-footer-logo-text">Support Foundation</span>';
                            }
                            ?>
                        </div>
                        <p class="sf-footer-tagline">Quality Support<br><strong>By Your Reach</strong></p>
                        <p class="sf-footer-about">Person-centered NDIS &amp; DVA support services across Australia. Rapid crisis response, plan funding optimization to buy more supports, and ethical welfare practice you can trust.</p>
                        <div class="sf-footer-3q">
                            <span class="sf-3q-chip sf-3q-quick">⚡ Quick</span>
                            <span class="sf-3q-chip sf-3q-quality">🛡 Quality</span>
                            <span class="sf-3q-chip sf-3q-quantity">👥 Quantity</span>
                        </div>
                    </div>

                    <!-- Col 2: Quick Links -->
                    <div class="sf-footer-col">
                        <h4 class="sf-footer-heading">Quick Links</h4>
                        <ul class="sf-footer-links">
                            <li><a href="/">Home</a></li>
                            <li><a href="/about-support-foundation/">About Us</a></li>
                            <li><a href="/#services">Our Services</a></li>
                            <li><a href="/blog/">NDIS Blog &amp; Guides</a></li>
                            <li><a href="/#locations">Coverage Areas</a></li>
                            <li><a href="/#referrals">Make a Referral</a></li>
                            <li><a href="/contact-us-support-foundation/">Contact Us</a></li>
                        </ul>
                    </div>

                    <!-- Col 3: Services -->
                    <div class="sf-footer-col">
                        <h4 class="sf-footer-heading">NDIS Services</h4>
                        <ul class="sf-footer-links">
                            <li><a href="/#services">Support Coordination</a></li>
                            <li><a href="/#services">Case Management</a></li>
                            <li><a href="/#services">Crisis Accommodation</a></li>
                            <li><a href="/#services">DV Support &amp; Safety</a></li>
                            <li><a href="/#services">Personal &amp; Nursing Care</a></li>
                            <li><a href="/#services">Capacity Building</a></li>
                        </ul>
                    </div>

                    <!-- Col 4: Contact -->
                    <div class="sf-footer-col">
                        <h4 class="sf-footer-heading">Get In Touch</h4>
                        <ul class="sf-footer-contact-list">
                            <li>
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                <span>20 Barabati Road, North Kellyville NSW 2155<br><small>Head Office (Sydney)</small></span>
                            </li>
                            <li>
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.18 6.18l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                <a href="tel:0283861433">02 8386 1433</a>
                            </li>
                            <li>
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                                <a href="mailto:info@supportfoundation.com.au">info@supportfoundation.com.au</a>
                            </li>
                            <li>
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                <span>Available 24/7<br><small>Call Us At: 02 8386 1433</small></span>
                            </li>
                        </ul>

                        <!-- Social Icons -->
                        <div class="sf-footer-socials">
                            <a href="https://www.facebook.com/supportfoundation13" target="_blank" rel="noopener" aria-label="Facebook" class="sf-social-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                            </a>
                            <a href="https://www.linkedin.com/company/support-foundation-au" target="_blank" rel="noopener" aria-label="LinkedIn" class="sf-social-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
                            </a>
                            <a href="https://zfrmz.com/sIh6uDqI2c9PaujmOoTR" target="_blank" rel="noopener" aria-label="Make a Referral" class="sf-social-btn sf-social-btn--cta">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                Referral Form
                            </a>
                        </div>
                    </div>

                </div><!-- .sf-footer-grid -->
            </div>
        </div>

        <!-- BOTTOM COPYRIGHT BAR -->
        <div class="sf-footer-bottom">
            <div class="sf-footer-container sf-footer-bottom-inner">
                <p class="sf-footer-copy">
                    &copy; <?php echo date('Y'); ?> Support Foundation Australia Pty Ltd &nbsp;|&nbsp;
                    NDIS Registered Provider
                </p>
                <nav class="sf-footer-legal" aria-label="Legal links">
                    <a href="/privacy-policy/">Privacy Policy</a>
                    <span>·</span>
                    <a href="/terms-of-service/">Terms of Service</a>
                    <span>·</span>
                    <a href="/complaints/">Complaints</a>
                </nav>
            </div>
        </div>

    </footer>
    <?php
}

/* ==========================================================================
 * COMPLETE SEO / GEO / AEO ENGINE
 * SEO  = Search Engine Optimization (Google, Bing)
 * GEO  = Generative Engine Optimization (AI Overviews, Perplexity, ChatGPT)
 * AEO  = Answer Engine Optimization (Featured Snippets, Voice Search)
 * ========================================================================== */

/* --- 1. PERFORMANCE HINTS (preconnect, dns-prefetch, preload) --- */
add_action('wp_head', 'sf_performance_hints', 0);
function sf_performance_hints() { ?>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="//www.google-analytics.com">
    <link rel="dns-prefetch" href="//www.googletagmanager.com">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <meta http-equiv="x-dns-prefetch-control" content="on">
<?php }

/* --- 2. SEO META TAGS, OPEN GRAPH, TWITTER CARDS, GEO TAGS --- */
add_action('wp_head', 'sf_seo_meta_engine', 1);
function sf_seo_meta_engine() {
    $site_url    = home_url('/');
    $current_url = is_front_page() ? $site_url : get_permalink();
    $page_title  = is_front_page()
        ? 'Support Foundation — Registered NDIS Service Provider in Australia | 24/7 Crisis Support'
        : ((is_page('blog') || is_home())
            ? 'NDIS Guide & Blog — Registered NDIS Service Provider in Australia | Support Foundation'
            : ((is_page('career') || is_page('careers'))
                ? 'NDIS Support Worker & Healthcare Careers — Support Foundation Australia'
                : wp_get_document_title()));

    $meta_desc   = is_front_page()
        ? 'Support Foundation is a Registered NDIS Service Provider in Australia (#4050064716). As a trusted NDIS Service Provider in Australia, we offer 24/7 crisis support, support coordination, emergency accommodation & personal care across NSW, VIC, ACT, SA & TAS. Call 02-8386-1433.'
        : ((is_page('blog') || is_home())
            ? 'Comprehensive NDIS knowledge base & blog covering Registered NDIS Service Provider guides, 24/7 crisis support, support coordination, emergency housing & disability care across Australia.'
            : ((is_page('career') || is_page('careers'))
                ? 'Apply online for NDIS disability support worker jobs, support coordinator careers, and healthcare caregiver roles across NSW, VIC, ACT, SA, and TAS. Competitive pay $34-$55/hr.'
                : (get_the_excerpt() ? get_the_excerpt() : 'Support Foundation — Registered NDIS Service Provider in Australia offering disability support, crisis accommodation & support coordination.')));
    $logo_url    = 'https://www.supportfoundation.com.au/wp-content/uploads/2024/02/cropped-support-foundation-logo.png';
    $keywords    = 'Registered NDIS Service Provider, NDIS Service Provider in Australia, Registered NDIS Service Provider in Australia, NDIS provider Sydney, NDIS support coordination, crisis accommodation Australia, DVA service provider, emergency housing NDIS, personal care NDIS, NDIS provider NSW, Support Foundation Australia, disability support services Australia';
    ?>
    <!-- SEO META TAGS -->
    <meta name="description" content="<?php echo esc_attr($meta_desc); ?>">
    <meta name="keywords" content="<?php echo esc_attr($keywords); ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="author" content="Support Foundation Australia">
    <meta name="publisher" content="Support Foundation Australia Pty Ltd">
    <link rel="canonical" href="<?php echo esc_url($current_url); ?>">

    <!-- GEO-TARGETING META TAGS -->
    <meta name="geo.region" content="AU-NSW">
    <meta name="geo.placename" content="North Kellyville, Sydney, NSW, Australia">
    <meta name="geo.position" content="-33.9318;151.0825">
    <meta name="ICBM" content="-33.9318, 151.0825">
    <meta name="DC.title" content="<?php echo esc_attr($page_title); ?>">
    <meta name="DC.creator" content="Support Foundation Australia">
    <meta name="DC.language" content="en-AU">
    <meta name="DC.coverage" content="Australia">
    <meta name="DC.rights" content="Copyright Support Foundation Australia Pty Ltd">
    <meta name="language" content="English">
    <meta name="rating" content="General">
    <meta name="distribution" content="Global">
    <meta name="revisit-after" content="3 days">

    <!-- OPEN GRAPH META TAGS -->
    <meta property="og:locale" content="en_AU">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo esc_attr($page_title); ?>">
    <meta property="og:description" content="<?php echo esc_attr($meta_desc); ?>">
    <meta property="og:url" content="<?php echo esc_url($current_url); ?>">
    <meta property="og:site_name" content="Support Foundation Australia">
    <meta property="og:image" content="<?php echo esc_url($logo_url); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:type" content="image/png">

    <!-- TWITTER CARDS -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo esc_attr($page_title); ?>">
    <meta name="twitter:description" content="<?php echo esc_attr($meta_desc); ?>">
    <meta name="twitter:image" content="<?php echo esc_url($logo_url); ?>">
    <?php
}

/* --- 3. SCHEMA.ORG JSON-LD STRUCTURED DATA (SEO + GEO + AEO) --- */
add_action('wp_head', 'sf_structured_data_engine', 2);
function sf_structured_data_engine() {
    $site_url = home_url('/');
    $logo_url = 'https://www.supportfoundation.com.au/wp-content/uploads/2024/02/cropped-support-foundation-logo.png';
    ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "WebSite",
          "@id": "<?php echo esc_url($site_url); ?>#website",
          "url": "<?php echo esc_url($site_url); ?>",
          "name": "Support Foundation Australia",
          "description": "Registered NDIS Service Provider in Australia offering 24/7 crisis support, support coordination & emergency accommodation",
          "publisher": { "@id": "<?php echo esc_url($site_url); ?>#organization" },
          "inLanguage": "en-AU",
          "potentialAction": {
            "@type": "SearchAction",
            "target": {
              "@type": "EntryPoint",
              "urlTemplate": "<?php echo esc_url($site_url); ?>?s={search_term_string}"
            },
            "query-input": "required name=search_term_string"
          }
        },
        {
          "@type": ["MedicalBusiness", "GovernmentService", "LocalBusiness"],
          "@id": "<?php echo esc_url($site_url); ?>#organization",
          "name": "Support Foundation Australia",
          "legalName": "Support Foundation Australia Pty Ltd",
          "url": "<?php echo esc_url($site_url); ?>",
          "logo": {
            "@type": "ImageObject",
            "url": "<?php echo esc_url($logo_url); ?>",
            "width": 300,
            "height": 100
          },
          "image": "<?php echo esc_url($logo_url); ?>",
          "telephone": "+61-2-8386-1433",
          "email": "info@supportfoundation.com.au",
          "identifier": {
            "@type": "PropertyValue",
            "name": "NDIS Provider Number",
            "value": "4050064716"
          },
          "priceRange": "NDIS Funded",
          "currenciesAccepted": "AUD",
          "paymentAccepted": "NDIS Plan Managed, Self-Managed, Agency Managed",
          "address": {
            "@type": "PostalAddress",
            "streetAddress": "20 Barabati Road",
            "addressLocality": "North Kellyville",
            "addressRegion": "NSW",
            "postalCode": "2155",
            "addressCountry": {
              "@type": "Country",
              "name": "AU"
            }
          },
          "geo": {
            "@type": "GeoCoordinates",
            "latitude": -33.9189,
            "longitude": 151.0886
          },
          "hasMap": "https://www.google.com/maps?q=20+Barabati+Road+North+Kellyville+NSW+2155+Australia",
          "areaServed": [
            { "@type": "State", "name": "New South Wales", "sameAs": "https://en.wikipedia.org/wiki/New_South_Wales" },
            { "@type": "State", "name": "Victoria", "sameAs": "https://en.wikipedia.org/wiki/Victoria_(state)" },
            { "@type": "State", "name": "Australian Capital Territory" },
            { "@type": "State", "name": "South Australia" },
            { "@type": "State", "name": "Tasmania" }
          ],
          "openingHoursSpecification": [
            {
              "@type": "OpeningHoursSpecification",
              "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"],
              "opens": "09:00",
              "closes": "17:00",
              "validFrom": "2024-01-01"
            },
            {
              "@type": "OpeningHoursSpecification",
              "description": "Call Us At",
              "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
              "opens": "00:00",
              "closes": "23:59"
            }
          ],
          "contactPoint": [
            {
              "@type": "ContactPoint",
              "telephone": "+61-2-8386-1433",
              "contactType": "customer service",
              "areaServed": "AU",
              "availableLanguage": ["English","Nepali","Hindi"],
              "hoursAvailable": {
                "@type": "OpeningHoursSpecification",
                "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"],
                "opens": "09:00",
                "closes": "17:00"
              }
            },
            {
              "@type": "ContactPoint",
              "telephone": "+61-2-8386-1433",
              "contactType": "emergency",
              "areaServed": "AU",
              "availableLanguage": "English",
              "hoursAvailable": {
                "@type": "OpeningHoursSpecification",
                "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
                "opens": "00:00",
                "closes": "23:59"
              }
            }
          ],
          "sameAs": [
            "https://www.facebook.com/profile.php?id=61556399253595",
            "https://www.instagram.com/supportfoundation.au/",
            "https://www.linkedin.com/company/support-foundation-australia",
            "https://telegra.ph/Support-Foundation-Australia--Registered-NDIS-Service-Provider-07-25"
          ],
          "knowsAbout": [
            "NDIS Support Coordination",
            "Crisis Accommodation",
            "Disability Services",
            "Domestic Violence Support",
            "Personal Care",
            "Case Management",
            "Emergency Housing"
          ],
          "slogan": "Quick Response. Quality You Can Trust. Quantity & Value."
        },
        {
          "@type": "Service",
          "@id": "<?php echo esc_url($site_url); ?>#support-coordination",
          "name": "NDIS Support Coordination",
          "provider": { "@id": "<?php echo esc_url($site_url); ?>#organization" },
          "serviceType": "Support Coordination",
          "description": "Level 2 and Level 3 Specialist Support Coordination helping NDIS participants connect with services, manage crises, and build independence.",
          "areaServed": "AU",
          "audience": { "@type": "Audience", "audienceType": "NDIS Participants" }
        },
        {
          "@type": "Service",
          "@id": "<?php echo esc_url($site_url); ?>#crisis-accommodation",
          "name": "24/7 Crisis Support & Emergency Housing",
          "provider": { "@id": "<?php echo esc_url($site_url); ?>#organization" },
          "serviceType": "Crisis Accommodation",
          "description": "Round-the-clock emergency accommodation placement for NDIS participants experiencing housing crises, domestic violence, or unsafe living conditions.",
          "areaServed": "AU",
          "availableChannel": {
            "@type": "ServiceChannel",
            "servicePhone": { "@type": "ContactPoint", "telephone": "+61-2-8386-1433" },
            "availableLanguage": "English"
          }
        },
        {
          "@type": "Service",
          "@id": "<?php echo esc_url($site_url); ?>#personal-care",
          "name": "Personal Care & Nursing Care",
          "provider": { "@id": "<?php echo esc_url($site_url); ?>#organization" },
          "serviceType": "Personal Care",
          "description": "Assistance with daily living activities including showering, dressing, medication management, meal preparation, and 24-hour support.",
          "areaServed": "AU"
        },
        {
          "@type": "Service",
          "@id": "<?php echo esc_url($site_url); ?>#dv-support",
          "name": "Domestic Violence Support & Safety Planning",
          "provider": { "@id": "<?php echo esc_url($site_url); ?>#organization" },
          "serviceType": "Domestic Violence Support",
          "description": "Safety planning, crisis accommodation, specialist support coordination, and relocation assistance for NDIS participants affected by domestic and family violence.",
          "areaServed": "AU"
        },
        {
          "@type": "Service",
          "@id": "<?php echo esc_url($site_url); ?>#capacity-building",
          "name": "Capacity Building & Community Participation",
          "provider": { "@id": "<?php echo esc_url($site_url); ?>#organization" },
          "serviceType": "Capacity Building",
          "description": "Skills development, social participation, employment readiness, and community access programs to build independence and confidence.",
          "areaServed": "AU"
        },
        {
          "@type": "JobPosting",
          "@id": "<?php echo esc_url($site_url); ?>#job-application",
          "title": "NDIS Disability Support Worker & Healthcare Caregiver",
          "description": "Support Foundation Australia is hiring NDIS Disability Support Workers, Support Coordinators, and Healthcare Caregivers across NSW, VIC, ACT, SA, and TAS. Apply online via our Job Application Form.",
          "identifier": {
            "@type": "PropertyValue",
            "name": "Support Foundation Australia",
            "value": "SF-NDIS-JOBS-2026"
          },
          "datePosted": "2026-08-11",
          "validThrough": "2027-12-31",
          "employmentType": ["FULL_TIME", "PART_TIME", "CASUAL"],
          "hiringOrganization": { "@id": "<?php echo esc_url($site_url); ?>#organization" },
          "jobLocation": [
            {
              "@type": "Place",
              "address": {
                "@type": "PostalAddress",
                "addressLocality": "North Kellyville",
                "addressRegion": "NSW",
                "postalCode": "2155",
                "addressCountry": "AU"
              }
            }
          ],
          "baseSalary": {
            "@type": "MonetaryAmount",
            "currency": "AUD",
            "value": {
              "@type": "QuantitativeValue",
              "unitText": "HOUR",
              "minValue": 34,
              "maxValue": 55
            }
          },
          "directApply": true,
          "url": "https://forms.zohopublic.com/virtualoffice15585/form/ServiceAgreement/formperma/hSFh-yUR-CRf3xaROJUA4fFm3jYvNk5g1gPmRsdpd6I"
        }
      ]
    }
    </script>
    <?php

    /* --- AEO: FAQ Schema for Featured Snippets & Voice Search --- */
    if ( is_front_page() ) { ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "What is Support Foundation Australia?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Support Foundation Australia is a Registered NDIS Service Provider in Australia (NDIS #4050064716). As a trusted NDIS Service Provider in Australia, we offer 24/7 crisis support, support coordination, emergency accommodation, personal care, and domestic violence support across NSW, VIC, ACT, SA, and TAS. Call 02-8386-1433."
          }
        },
        {
          "@type": "Question",
          "name": "What services does Support Foundation provide?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "As a Registered NDIS Service Provider in Australia, Support Foundation provides: (1) Support Coordination & Specialist Support Coordination, (2) Case Management & Plan Management, (3) 24/7 Crisis Support & Emergency Housing, (4) Short-Term Accommodation (STA), (5) Domestic Violence Support & Safety Planning, (6) Personal Care & Nursing Care, (7) Community Participation & Capacity Building, and (8) Psychosocial Recovery Coaching."
          }
        },
        {
          "@type": "Question",
          "name": "What areas does Support Foundation cover?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Support Foundation operates across five Australian states and territories: New South Wales (NSW), Victoria (VIC), Australian Capital Territory (ACT), South Australia (SA), and Tasmania (TAS). Head office is at 20 Barabati Road, North Kellyville NSW 2155."
          }
        },
        {
          "@type": "Question",
          "name": "How do I make a referral to Support Foundation?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "You can make a referral by: (1) Calling 02-8386-1433, (2) Emailing info@supportfoundation.com.au, or (3) Completing the online referral form at https://zfrmz.com/sIh6uDqI2c9PaujmOoTR. Support Foundation accepts referrals from participants, families, hospitals, LACs, and other service providers."
          }
        },
        {
          "@type": "Question",
          "name": "Is Support Foundation available 24/7?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Yes. Support Foundation operates 24 hours a day, 7 days a week, 365 days a year. Emergency accommodation placement and support response are available around the clock."
          }
        },
        {
          "@type": "Question",
          "name": "What is the NDIS provider number for Support Foundation?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Support Foundation Australia's NDIS Provider Registration Number is 4050064716. This can be verified through the NDIS Quality and Safeguards Commission."
          }
        }
      ]
    }
    </script>

    <!-- AEO: Speakable Schema for Voice Search (Google Assistant) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebPage",
      "name": "Support Foundation Australia — NDIS Service Provider",
      "speakable": {
        "@type": "SpeakableSpecification",
        "cssSelector": [".sf-hero-title", ".sf-hero-description", ".sf-card-title", ".sf-card-desc"]
      },
      "url": "<?php echo esc_url($site_url); ?>"
    }
    </script>

    <!-- SEO: BreadcrumbList for Homepage -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        { "@type": "ListItem", "position": 1, "name": "Home", "item": "<?php echo esc_url($site_url); ?>" }
      ]
    }
    </script>
    <?php } else { ?>
    <!-- SEO: BreadcrumbList for Subpages -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        { "@type": "ListItem", "position": 1, "name": "Home", "item": "<?php echo esc_url($site_url); ?>" },
        { "@type": "ListItem", "position": 2, "name": "<?php echo esc_attr(get_the_title()); ?>", "item": "<?php echo esc_url(get_permalink()); ?>" }
      ]
    }
    </script>
    <?php }
}

/* --- 4. GEO: Multi-Location Service Area Pages Schema --- */
add_action('wp_head', 'sf_geo_multi_location_schema', 3);
function sf_geo_multi_location_schema() {
    if ( ! is_front_page() ) return;
    $site_url = home_url('/');
    $locations = array(
        array('name' => 'Sydney',    'region' => 'NSW', 'lat' => -33.8688, 'lng' => 151.2093),
        array('name' => 'Melbourne', 'region' => 'VIC', 'lat' => -37.8136, 'lng' => 144.9631),
        array('name' => 'Canberra',  'region' => 'ACT', 'lat' => -35.2802, 'lng' => 149.1310),
        array('name' => 'Adelaide',  'region' => 'SA',  'lat' => -34.9285, 'lng' => 138.6007),
        array('name' => 'Hobart',    'region' => 'TAS', 'lat' => -42.8821, 'lng' => 147.3272),
    );
    ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "@id": "<?php echo esc_url($site_url); ?>#geo-locations",
      "name": "Support Foundation Australia",
      "department": [
        <?php foreach ($locations as $i => $loc) : ?>
        {
          "@type": "LocalBusiness",
          "name": "Support Foundation — <?php echo $loc['name']; ?>",
          "address": {
            "@type": "PostalAddress",
            "addressLocality": "<?php echo $loc['name']; ?>",
            "addressRegion": "<?php echo $loc['region']; ?>",
            "addressCountry": "AU"
          },
          "geo": {
            "@type": "GeoCoordinates",
            "latitude": <?php echo $loc['lat']; ?>,
            "longitude": <?php echo $loc['lng']; ?>
          },
          "telephone": "+61-2-8386-1433",
          "url": "<?php echo esc_url($site_url); ?>"
        }<?php echo ($i < count($locations) - 1) ? ',' : ''; ?>
        <?php endforeach; ?>
      ]
    }
    </script>
    <?php
}

/* --- 5. AEO: Add Semantic HTML Microdata to Existing Content --- */
add_filter('the_content', 'sf_aeo_enhance_content', 20);
function sf_aeo_enhance_content($content) {
    // Add itemscope to service-related content blocks
    $content = str_replace(
        '<div class="sf-services-section"',
        '<div class="sf-services-section" itemscope itemtype="https://schema.org/ItemList"',
        $content
    );
    return $content;
}

/* --- 6. SEO: Optimized Title Tag Structure --- */
add_filter('pre_get_document_title', 'sf_seo_title_tag', 99);
function sf_seo_title_tag($title) {
    if (is_front_page()) {
        return 'Support Foundation — Registered NDIS Service Provider in Australia | 24/7 Crisis Support';
    }
    return $title;
}

/* --- 7. SEO: Add hreflang for Australian English --- */
add_action('wp_head', 'sf_hreflang_tags', 4);
function sf_hreflang_tags() {
    $current_url = is_front_page() ? home_url('/') : get_permalink();
    ?>
    <link rel="alternate" hreflang="en-au" href="<?php echo esc_url($current_url); ?>">
    <link rel="alternate" hreflang="en" href="<?php echo esc_url($current_url); ?>">
    <link rel="alternate" hreflang="x-default" href="<?php echo esc_url($current_url); ?>">
    <?php
}

/* --- 8. SEO BLOG: Template Routing & Title Tag --- */
add_filter('template_include', 'sf_blog_template_routing', 99);
function sf_blog_template_routing($template) {
    if (is_single()) {
        $single_template = get_stylesheet_directory() . '/single.php';
        if (file_exists($single_template)) {
            return $single_template;
        }
        return $template;
    }
    
    $request_uri = isset($_SERVER['REQUEST_URI']) ? trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') : '';
    if (is_page('blog') || is_post_type_archive('post') || is_home() || $request_uri === 'blog' || preg_match('#^blog(/page/\d+)?/?$#', $request_uri)) {
        $blog_template = get_stylesheet_directory() . '/template-blog.php';
        if (file_exists($blog_template)) {
            status_header(200);
            return $blog_template;
        }
    }
    return $template;
}

add_filter('pre_get_document_title', 'sf_blog_seo_title_tag', 100);
function sf_blog_seo_title_tag($title) {
    if (is_page('blog') || is_home()) {
        return 'NDIS Guide & Blog — Registered NDIS Service Provider in Australia | Support Foundation';
    }
    return $title;
}



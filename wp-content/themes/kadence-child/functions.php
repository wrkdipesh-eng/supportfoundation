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
    if (get_option('sf_permalinks_fix_v3') !== 'done') {
        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure('/%postname%/');
        flush_rewrite_rules(true);
        update_option('sf_permalinks_fix_v3', 'done');
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
                    { icon: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>', color: "#34d399", text: "$0 Out-of-Pocket Costs for All NDIS Participants" },
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
                    <span>24/7 Crisis Line:</span>
                    <a href="tel:1800000000">1800 000 000</a>
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
                        <p class="sf-footer-tagline">Empowering Lives,<br><strong>Restoring Autonomy.</strong></p>
                        <p class="sf-footer-about">Person-centered NDIS &amp; DVA support services across Australia. Rapid crisis response, $0 out-of-pocket coordination, and ethical welfare practice you can trust.</p>
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
                                <span>Roselands NSW 2196<br><small>Head Office (Sydney)</small></span>
                            </li>
                            <li>
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.18 6.18l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                <a href="tel:1800000000">1800 000 000</a>
                            </li>
                            <li>
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                                <a href="mailto:info@supportfoundation.com.au">info@supportfoundation.com.au</a>
                            </li>
                            <li>
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                <span>Mon–Fri: 9am–5pm AEDT<br><small>Crisis line: 24 / 7</small></span>
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
        ? 'Support Foundation — Registered NDIS & DVA Service Provider | Sydney NSW'
        : wp_get_document_title();
    $meta_desc   = is_front_page()
        ? 'Support Foundation is an Australian Registered NDIS & DVA Service Provider (#4050064716) offering 24/7 crisis support, support coordination, emergency accommodation, and personal care across NSW, VIC, ACT, SA, and TAS. Call 02-8386-1433.'
        : get_the_excerpt() ?: 'Support Foundation Australia — Registered NDIS Provider offering disability support services across Australia.';
    $logo_url    = 'https://www.supportfoundation.com.au/wp-content/uploads/2024/02/cropped-support-foundation-logo.png';
    $keywords    = 'NDIS provider Sydney, NDIS support coordination, crisis accommodation Australia, disability support services, DVA service provider, emergency housing NDIS, personal care NDIS, case management disability, Support Foundation Australia, NDIS provider NSW VIC ACT SA TAS';
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
    <meta name="geo.placename" content="Roselands, Sydney, NSW, Australia">
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
          "description": "Registered NDIS & DVA Service Provider offering 24/7 crisis support across Australia",
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
            "streetAddress": "Roselands",
            "addressLocality": "Sydney",
            "addressRegion": "NSW",
            "postalCode": "2196",
            "addressCountry": {
              "@type": "Country",
              "name": "AU"
            }
          },
          "geo": {
            "@type": "GeoCoordinates",
            "latitude": -33.9318,
            "longitude": 151.0825
          },
          "hasMap": "https://www.google.com/maps?q=Roselands+NSW+2196+Australia",
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
              "description": "24/7 Crisis Line",
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
            "text": "Support Foundation Australia is a Registered NDIS and DVA Service Provider (NDIS #4050064716) offering 24/7 crisis support, support coordination, emergency accommodation, personal care, and domestic violence support across NSW, VIC, ACT, SA, and TAS. Call 02-8386-1433."
          }
        },
        {
          "@type": "Question",
          "name": "What services does Support Foundation provide?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Support Foundation provides: (1) Support Coordination & Specialist Support Coordination, (2) Case Management & Plan Management, (3) 24/7 Crisis Support & Emergency Housing, (4) Short-Term Accommodation (STA), (5) Domestic Violence Support & Safety Planning, (6) Personal Care & Nursing Care, (7) Community Participation & Capacity Building, and (8) Psychosocial Recovery Coaching."
          }
        },
        {
          "@type": "Question",
          "name": "What areas does Support Foundation cover?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Support Foundation operates across five Australian states and territories: New South Wales (NSW), Victoria (VIC), Australian Capital Territory (ACT), South Australia (SA), and Tasmania (TAS). Head office is in Roselands, Sydney NSW 2196."
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
            "text": "Yes. Support Foundation's crisis support line operates 24 hours a day, 7 days a week, 365 days a year. Regular office hours are Monday to Friday, 9am to 5pm AEDT. Emergency accommodation placement and crisis response are available around the clock."
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
        return 'Support Foundation Australia — Registered NDIS & DVA Provider | 24/7 Crisis Support Sydney';
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


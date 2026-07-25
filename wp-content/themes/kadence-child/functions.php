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

        // 3. Top Bar \u2014 Animated Trust Ticker (replaces static NDIS badge)
        var topHeader = document.querySelector(".site-top-header-inner-wrap");
        if (topHeader) {
            // Hide any existing static Registered NDIS text elements
            var customizerHtmls = topHeader.querySelectorAll(".header-html-inner");
            customizerHtmls.forEach(function(el) {
                if (el.textContent.indexOf("Registered NDIS") !== -1) {
                    el.style.display = "none";
                }
            });

            // Inject animated ticker if not already there
            if (!topHeader.querySelector(".sf-trust-ticker-wrap")) {
                var tickerItems = [
                    { icon: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 12 11 14 15 10"></polyline>', color: "#2dd4bf", text: "Registered NDIS & DVA Provider \u00b7 #4050064716" },
                    { icon: '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>', color: "#a78bfa", text: "24/7 Crisis Support & Emergency Placement" },
                    { icon: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>', color: "#34d399", text: "$0 Out-of-Pocket Costs for All NDIS Participants" },
                    { icon: '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>', color: "#a78bfa", text: "Serving NSW \u00b7 VIC \u00b7 ACT \u00b7 SA \u00b7 TAS" },
                    { icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line>', color: "#2dd4bf", text: "AASW & ACWA Ethical Compliance Guaranteed" }
                ];

                function makeTickerHTML() {
                    // Build 2 copies for seamless infinite loop
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
                    return html;
                }

                var tickerWrap = document.createElement("div");
                tickerWrap.className = "sf-trust-ticker-wrap";
                tickerWrap.innerHTML = makeTickerHTML();

                // Insert at the start of topHeader so it takes center position
                var firstChild = topHeader.querySelector(".site-header-top-section-center");
                if (firstChild) {
                    firstChild.insertBefore(tickerWrap, firstChild.firstChild);
                } else {
                    topHeader.insertBefore(tickerWrap, topHeader.firstChild);
                }
            }
        }

        // 4. Sticky Header & Logo Shrink Effect on Scroll
        var masthead = document.querySelector("#masthead");
        if (masthead) {
            var handleScroll = function() {
                if (window.scrollY > 30) {
                    masthead.classList.add("sf-header-scrolled");
                } else {
                    masthead.classList.remove("sf-header-scrolled");
                }
            };
            window.addEventListener("scroll", handleScroll);
            handleScroll(); // Initial check
        }

        // 5. Hero Background Slider
        var slides = document.querySelectorAll(".sf-slide");
        var dots   = document.querySelectorAll(".sf-dot");
        if (slides.length > 1) {
            var current = 0;
            var total   = slides.length;

            function goToSlide(index) {
                slides[current].classList.remove("sf-slide--active");
                dots[current].classList.remove("sf-dot--active");
                current = (index + total) % total;
                slides[current].classList.add("sf-slide--active");
                dots[current].classList.add("sf-dot--active");
            }

            // Auto-play every 8 seconds
            var sliderTimer = setInterval(function() {
                goToSlide(current + 1);
            }, 8000);

            // Dot click navigation
            dots.forEach(function(dot, i) {
                dot.addEventListener("click", function() {
                    clearInterval(sliderTimer);
                    goToSlide(i);
                    sliderTimer = setInterval(function() { goToSlide(current + 1); }, 8000);
                });
            });
        }

        // 6. Force top bar to single-line — hide any non-ticker items
        var topCenter = document.querySelector(".site-header-top-section-center");
        if (topCenter) {
            var topItems = topCenter.querySelectorAll(".site-header-item");
            topItems.forEach(function(item) {
                if (!item.querySelector(".sf-trust-ticker-wrap")) {
                    item.style.display = "none";
                }
            });
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

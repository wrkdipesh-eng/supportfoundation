<?php
/**
 * Enqueue child styles.
 */
function child_enqueue_styles() {
	wp_enqueue_style( 'google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@500;600;700;800&display=swap', array(), null );
	wp_enqueue_style( 'child-theme', get_stylesheet_directory_uri() . '/style.css', array(), 100 );
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
    });
    </script>
    <?php
}




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
        // 1. Remove duplicate sticky logo elements inside site branding
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

        // 3. Top Bar Restructuring
        var topHeader = document.querySelector(".site-top-header-inner-wrap");
        if (topHeader) {
            var customizerHtmls = topHeader.querySelectorAll(".header-html-inner");
            customizerHtmls.forEach(function(el) {
                if (el.textContent.indexOf("Registered NDIS") !== -1 && !el.querySelector(".sf-top-nav-badge")) {
                    el.style.display = "none";
                }
            });

            var existingBadge = topHeader.querySelector(".sf-top-nav-badge");
            if (!existingBadge) {
                var badge = document.createElement("div");
                badge.className = "sf-top-nav-badge";
                badge.innerHTML = '<span class="sf-badge-dot"></span><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 5px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>Registered NDIS & DVA Provider';
                
                var social = topHeader.querySelector(".site-header-item[data-section='kadence_customizer_header_social']");
                if (social) {
                    social.parentNode.insertBefore(badge, social.nextSibling);
                } else {
                    topHeader.appendChild(badge);
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




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
        // 1. Top Bar Restructuring
        var topHeader = document.querySelector(".site-top-header-inner-wrap");
        if (topHeader) {
            // Remove any plain text NDIS nodes in customizer HTML slots to avoid duplicate text
            var customizerHtmls = topHeader.querySelectorAll(".header-html-inner");
            customizerHtmls.forEach(function(el) {
                if (el.textContent.indexOf("Registered NDIS") !== -1 && !el.querySelector(".sf-top-nav-badge")) {
                    el.style.display = "none";
                }
            });

            // Create or update top nav badge
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

        // 2. Sticky Header Scroll Enhancement
        var masthead = document.querySelector("#masthead");
        if (masthead) {
            window.addEventListener("scroll", function() {
                if (window.scrollY > 30) {
                    masthead.classList.add("sf-header-scrolled");
                } else {
                    masthead.classList.remove("sf-header-scrolled");
                }
            });
        }
    });
    </script>
    <?php
}



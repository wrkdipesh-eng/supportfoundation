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
        var topHeader = document.querySelector(".site-top-header-inner-wrap");
        if (topHeader) {
            // Create the badge container
            var badge = document.createElement("div");
            badge.className = "sf-top-nav-badge";
            badge.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 5px; vertical-align: middle;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>Registered NDIS Service Provider';
            
            // Insert it between the social icons and the phone html
            var social = topHeader.querySelector(".site-header-item[data-section='kadence_customizer_header_social']");
            if (social) {
                social.parentNode.insertBefore(badge, social.nextSibling);
            } else {
                topHeader.appendChild(badge);
            }
        }
    });
    </script>
    <?php
}


<?php
/**
 * Kadence functions and definitions for ReactCorp Disability
 */

define( 'KADENCE_VERSION', '1.5.2' );
define( 'KADENCE_MINIMUM_WP_VERSION', '6.0' );
define( 'KADENCE_MINIMUM_PHP_VERSION', '7.4' );

// Bail if requirements are not met.
if ( version_compare( $GLOBALS['wp_version'], KADENCE_MINIMUM_WP_VERSION, '<' ) || version_compare( phpversion(), KADENCE_MINIMUM_PHP_VERSION, '<' ) ) {
	require get_template_directory() . '/inc/back-compat.php';
	return;
}
// Include WordPress shims.
require get_template_directory() . '/inc/wordpress-shims.php';

// Load the `kadence()` entry point function.
require get_template_directory() . '/inc/class-theme.php';

// Load the `kadence()` entry point function.
require get_template_directory() . '/inc/functions.php';

// Initialize the theme.
call_user_func( 'Kadence\kadence' );

// Custom Clean Footer Component for ReactCorp Disability
if (!function_exists('rc_custom_footer')) {
    function rc_custom_footer() {
        echo '<style>#colophon { display: none !important; }</style>';
        ?>
        <footer class="rc-footer" style="background:#0f172a; color:#ffffff; padding: 4.5rem 0 2rem 0; font-family:'Plus Jakarta Sans',sans-serif;" aria-label="Site Footer">
            <div style="max-width:1240px; margin:0 auto; padding:0 1.5rem;">
                <div style="display: grid; grid-template-columns: 1.3fr 0.9fr 1.1fr 1.4fr; gap: 2rem; margin-bottom: 3rem;">
                    <div>
                        <a href="<?php echo get_site_url(); ?>" style="display:inline-flex; align-items:center; margin-bottom:1rem; text-decoration:none;">
                            <img src="<?php echo get_site_url(); ?>/wp-content/uploads/2026/03/cropped-logo-1.jpeg" alt="ReactCorp Logo" style="height:52px; width:auto; border-radius:6px;">
                        </a>
                        <p style="color:#94a3b8; font-size:0.92rem; line-height:1.6; margin:0;">A proud NDIS registered provider committed to delivering reliable, flexible, and person-centred supports.</p>
                    </div>
                    <div>
                        <h4 style="font-family:'Space Grotesk',sans-serif; font-size:1.1rem; font-weight:700; color:#ffffff; margin-bottom:1rem;">Navigation</h4>
                        <ul style="list-style:none; padding:0; margin:0; line-height:2.2; font-size:0.9rem;">
                            <li><a href="<?php echo get_site_url(); ?>/our-services/" style="color:#cbd5e1; text-decoration:none;">Our Services</a></li>
                            <li><a href="<?php echo get_site_url(); ?>/price/" style="color:#cbd5e1; text-decoration:none;">Price</a></li>
                            <li><a href="<?php echo get_site_url(); ?>/contact/" style="color:#f0abfc; text-decoration:none; font-weight:700;">Contact</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 style="font-family:'Space Grotesk',sans-serif; font-size:1.1rem; font-weight:700; color:#ffffff; margin-bottom:1rem;">Core Services</h4>
                        <ul style="list-style:none; padding:0; margin:0; line-height:2.2; font-size:0.9rem; color:#cbd5e1;">
                            <li>0132 Support Coordination</li>
                            <li>0101 Accommodation Assistance</li>
                            <li>0107 Personal Activities</li>
                            <li>0116 Community Participation</li>
                        </ul>
                    </div>
                    <div>
                        <h4 style="font-family:'Space Grotesk',sans-serif; font-size:1.1rem; font-weight:700; color:#ffffff; margin-bottom:1rem;">Contact Us</h4>
                        <ul style="list-style:none; padding:0; margin:0; font-size:0.9rem; color:#cbd5e1; display:flex; flex-direction:column; gap:0.75rem;">
                            <li style="display:flex; align-items:flex-start; gap:0.5rem; line-height:1.4;">
                                <span>📍</span>
                                <span>20 Barabati Road,<br>North Kellyville NSW 2155, Australia</span>
                            </li>
                            <li style="display:flex; align-items:center; gap:0.5rem;">
                                <span>📞</span>
                                <a href="tel:0422069482" style="color:#f0abfc; font-weight:700; text-decoration:none;">0422 069 482</a>
                            </li>
                            <li style="display:flex; align-items:center; gap:0.5rem;">
                                <span>✉️</span>
                                <a href="mailto:info@reactcorpdisability.com.au" style="color:#cbd5e1; text-decoration:none; word-break:break-all;">info@reactcorpdisability.com.au</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div style="border-top:1px solid rgba(255,255,255,0.1); padding-top:1.5rem; text-align:center; color:#94a3b8; font-size:0.85rem;">
                    &copy; <?php echo date('Y'); ?> ReactCorp Disability Services. All rights reserved. Registered NDIS Provider.
                </div>
            </div>
        </footer>
        <?php
    }
    add_action('wp_footer', 'rc_custom_footer', 10);
}

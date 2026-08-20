<?php
/**
 * Kadence Child Theme functions and definitions for ReactCorp Disability Services
 * SEO + GEO + AEO Engine for Top Google Search Keywords
 */

if (!defined('ABSPATH')) {
    exit;
}

// 1. Enqueue Google Fonts and Child Stylesheets
function rc_enqueue_child_assets() {
    wp_enqueue_style(
        'google-fonts-rc',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap',
        array(),
        null
    );

    wp_enqueue_style(
        'kadence-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('kadence-global'),
        '1.2.0'
    );
}
add_action('wp_enqueue_scripts', 'rc_enqueue_child_assets', 20);

// 2. Automatically flush WordPress rewrite rules on deploy
add_action('init', 'rc_auto_fix_404_permalinks', 99);
function rc_auto_fix_404_permalinks() {
    if (get_option('rc_permalinks_fix_v5') !== 'done') {
        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure('/%postname%/');
        
        // Auto-create Blog page if missing
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
        }

        flush_rewrite_rules(true);
        update_option('rc_permalinks_fix_v5', 'done');
    }
}

// 3. Inject Top Trust Bar Script into Header
function rc_inject_topbar_script() {
    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var topCenter = document.querySelector("#top-header .site-header-row-layout-center");
        
        if (topCenter) {
            if (!topCenter.querySelector(".rc-topbar-wrap")) {
                var html = '<div class="rc-topbar-wrap" style="background:#581c87; color:#ffffff; padding:0.45rem 0; font-size:0.875rem; border-bottom:1px solid rgba(255,255,255,0.15); font-family:\'Inter\',sans-serif;">'
                    + '<div class="rc-container" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">'
                    + '<span>🛡 Registered NDIS Service Provider &nbsp;·&nbsp; #4050064716</span>'
                    + '<span style="margin-left:auto;">Call Us At: <a href="tel:0422069482" style="color:#f0abfc; font-weight:700; text-decoration:none;">0422 069 482</a></span>'
                    + '</div>'
                    + '</div>';
                topCenter.insertAdjacentHTML('beforeend', html);
            }
        }
    });
    </script>
    <?php
}
add_action('wp_footer', 'rc_inject_topbar_script');

// 4. Custom Footer Component
function rc_custom_footer() {
    echo '<style>#colophon { display: none !important; }</style>';
    ?>
    <footer class="sf-footer" style="background:#0f172a; color:#f8fafc; padding: 4.5rem 0 2rem 0; font-family:'Inter',sans-serif;" aria-label="Site Footer">
        <div class="rc-container">
            <div style="display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr; gap: 2.5rem; margin-bottom: 3rem;">
                
                <!-- Brand Info -->
                <div>
                    <h3 style="font-family:'Outfit',sans-serif; font-size: 1.5rem; font-weight: 800; color:#f0abfc; margin-bottom: 0.75rem;">ReactCorp Disability</h3>
                    <p style="color:#94a3b8; font-size: 0.95rem; line-height: 1.65; margin-bottom: 1.25rem;">A proud Registered NDIS Service Provider in Australia (#4050064716) committed to delivering reliable, flexible, and person-centred supports across NSW, VIC, ACT, SA, and TAS. At ReactCorp, your goals become our mission.</p>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <span style="background:rgba(240,171,252,0.15); color:#f0abfc; font-size: 0.75rem; font-weight:700; padding:0.25rem 0.6rem; border-radius:4px;">⚡ Quick Response</span>
                        <span style="background:rgba(45,212,191,0.15); color:#2dd4bf; font-size: 0.75rem; font-weight:700; padding:0.25rem 0.6rem; border-radius:4px;">🛡 NDIS Registered</span>
                        <span style="background:rgba(56,189,248,0.15); color:#38bdf8; font-size: 0.75rem; font-weight:700; padding:0.25rem 0.6rem; border-radius:4px;">👥 24/7 Support</span>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div>
                    <h4 style="font-family:'Outfit',sans-serif; font-size: 1.1rem; font-weight:700; color:#ffffff; margin-bottom: 1rem;">Quick Links</h4>
                    <ul style="list-style:none; line-height:2.2; font-size:0.9rem;">
                        <li><a href="/" style="color:#cbd5e1; text-decoration:none;">Home</a></li>
                        <li><a href="/our-services/" style="color:#cbd5e1; text-decoration:none;">Our Services</a></li>
                        <li><a href="/blog/" style="color:#cbd5e1; text-decoration:none;">NDIS Blog & Guides</a></li>
                        <li><a href="/contact-us/" style="color:#cbd5e1; text-decoration:none;">Contact Us</a></li>
                        <li><a href="https://zfrmz.com/sIh6uDqI2c9PaujmOoTR" target="_blank" rel="noopener" style="color:#f0abfc; text-decoration:none; font-weight:600;">Online Referral Form</a></li>
                    </ul>
                </div>

                <!-- Registration Groups -->
                <div>
                    <h4 style="font-family:'Outfit',sans-serif; font-size: 1.1rem; font-weight:700; color:#ffffff; margin-bottom: 1rem;">NDIS Key Groups</h4>
                    <ul style="list-style:none; line-height:2.2; font-size:0.9rem;">
                        <li><span style="color:#94a3b8;">Group 0132</span> Support Coordination</li>
                        <li><span style="color:#94a3b8;">Group 0101</span> Tenancy & Housing</li>
                        <li><span style="color:#94a3b8;">Group 0107</span> Personal Care & Hygiene</li>
                        <li><span style="color:#94a3b8;">Group 0116</span> Community Access</li>
                    </ul>
                </div>

                <!-- Contact & Address -->
                <div>
                    <h4 style="font-family:'Outfit',sans-serif; font-size: 1.1rem; font-weight:700; color:#ffffff; margin-bottom: 1rem;">Contact Us</h4>
                    <p style="color:#cbd5e1; font-size: 0.9rem; line-height: 1.6; margin-bottom: 0.75rem;">
                        📍 20 Barabati Road, North Kellyville NSW 2155<br>
                        <small style="color:#94a3b8;">Sydney, Australia</small>
                    </p>
                    <p style="color:#cbd5e1; font-size: 0.9rem; margin-bottom: 0.5rem;">
                        📞 <a href="tel:0422069482" style="color:#f0abfc; font-weight:700; text-decoration:none;">0422 069 482</a>
                    </p>
                    <p style="color:#cbd5e1; font-size: 0.9rem;">
                        ✉️ <a href="mailto:info@reactcorpdisability.com.au" style="color:#cbd5e1; text-decoration:none;">info@reactcorpdisability.com.au</a>
                    </p>
                </div>

            </div>

            <!-- Bottom Copyright Bar -->
            <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; font-size: 0.85rem; color:#94a3b8;">
                <p>&copy; <?php echo date('Y'); ?> ReactCorp Disability Services (NDIS #4050064716). All rights reserved.</p>
                <p>Quality Support By Your Reach</p>
            </div>
        </div>
    </footer>
    <?php
}
add_action('wp_footer', 'rc_custom_footer', 10);

// 5. SEO META ENGINE (Title Tags, Meta Descriptions, OpenGraph, Canonical, GEO Meta)
add_action('wp_head', 'rc_seo_meta_engine', 0);
function rc_seo_meta_engine() {
    $site_url    = home_url('/');
    $current_url = is_front_page() ? $site_url : get_permalink();
    $request_uri = isset($_SERVER['REQUEST_URI']) ? trim($_SERVER['REQUEST_URI'], '/') : '';

    if (is_front_page()) {
        $page_title = 'ReactCorp Disability Services — Registered NDIS Service Provider in Australia';
        $meta_desc  = 'ReactCorp Disability Services is a Registered NDIS Service Provider in Australia (#4050064716). We deliver 24/7 crisis support, support coordination, SIL accommodation, and personal care across NSW, VIC, ACT, SA & TAS. Call 0422 069 482.';
    } elseif (is_page('blog') || $request_uri === 'blog' || strpos($request_uri, 'blog') === 0) {
        $page_title = 'NDIS Blog & Knowledge Center — ReactCorp Disability Services Australia';
        $meta_desc  = 'Comprehensive NDIS blog & search guide by ReactCorp Disability Services covering Registered NDIS Provider guides, 24/7 crisis housing, support coordination, and disability care rights.';
    } elseif (is_page('our-services') || strpos($request_uri, 'services') !== false) {
        $page_title = 'NDIS Services — Support Coordination, SIL Accommodation & Personal Care | ReactCorp';
        $meta_desc  = 'Explore ReactCorp Disability NDIS services: Group 0132 Support Coordination, SIL Shared Housing, In-Home Personal Care, Community Access, and 24/7 Crisis Respite across Australia.';
    } else {
        $page_title = wp_get_document_title();
        $meta_desc  = 'ReactCorp Disability Services — Registered NDIS Service Provider offering support coordination, SIL accommodation, personal care & 24/7 crisis support.';
    }

    $logo_url = 'https://reactcorpdisability.com.au/wp-content/uploads/2024/02/cropped-reactcorp-logo.png';
    $keywords = 'ReactCorp Disability Services, Registered NDIS Service Provider Australia, NDIS Provider Sydney NSW, NDIS Support Coordination Group 0132, SIL Accommodation, Personal Care Nursing NDIS, 24/7 Crisis Support NDIS, NDIS Provider Registration Number 4050064716';
    ?>
    <!-- SEO META TAGS -->
    <meta name="description" content="<?php echo esc_attr($meta_desc); ?>">
    <meta name="keywords" content="<?php echo esc_attr($keywords); ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="author" content="ReactCorp Disability Services">
    <meta name="publisher" content="ReactCorp Disability Services Pty Ltd">
    <link rel="canonical" href="<?php echo esc_url($current_url); ?>">

    <!-- GEO-TARGETING META TAGS -->
    <meta name="geo.region" content="AU-NSW">
    <meta name="geo.placename" content="North Kellyville, Sydney, NSW, Australia">
    <meta name="geo.position" content="-33.9318;151.0825">
    <meta name="ICBM" content="-33.9318, 151.0825">
    <meta name="DC.title" content="<?php echo esc_attr($page_title); ?>">
    <meta name="DC.creator" content="ReactCorp Disability Services">
    <meta name="DC.language" content="en-AU">
    <meta name="DC.coverage" content="Australia">
    <meta name="language" content="English">

    <!-- OPEN GRAPH META TAGS -->
    <meta property="og:locale" content="en_AU">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo esc_attr($page_title); ?>">
    <meta property="og:description" content="<?php echo esc_attr($meta_desc); ?>">
    <meta property="og:url" content="<?php echo esc_url($current_url); ?>">
    <meta property="og:site_name" content="ReactCorp Disability Services">
    <meta property="og:image" content="<?php echo esc_url($logo_url); ?>">

    <!-- TWITTER CARDS -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo esc_attr($page_title); ?>">
    <meta name="twitter:description" content="<?php echo esc_attr($meta_desc); ?>">

    <!-- HREFLANG -->
    <link rel="alternate" hreflang="en-au" href="<?php echo esc_url($current_url); ?>">
    <link rel="alternate" hreflang="en" href="<?php echo esc_url($current_url); ?>">
    <link rel="alternate" hreflang="x-default" href="<?php echo esc_url($current_url); ?>">
    <?php
}

// 6. SCHEMA.ORG JSON-LD STRUCTURED DATA GRAPH
add_action('wp_head', 'rc_structured_data_engine', 2);
function rc_structured_data_engine() {
    $site_url = home_url('/');
    ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "WebSite",
          "@id": "https://reactcorpdisability.com.au/#website",
          "url": "https://reactcorpdisability.com.au/",
          "name": "ReactCorp Disability Services",
          "description": "Registered NDIS Service Provider in Australia delivering 24/7 crisis support, support coordination & SIL accommodation",
          "inLanguage": "en-AU"
        },
        {
          "@type": ["MedicalBusiness", "LocalBusiness"],
          "@id": "https://reactcorpdisability.com.au/#organization",
          "name": "ReactCorp Disability Services",
          "legalName": "ReactCorp Disability Services Pty Ltd",
          "url": "https://reactcorpdisability.com.au/",
          "telephone": "+61-422-069-482",
          "email": "info@reactcorpdisability.com.au",
          "identifier": {
            "@type": "PropertyValue",
            "name": "NDIS Provider Registration Number",
            "value": "4050064716"
          },
          "address": {
            "@type": "PostalAddress",
            "streetAddress": "20 Barabati Road",
            "addressLocality": "North Kellyville",
            "addressRegion": "NSW",
            "postalCode": "2155",
            "addressCountry": "AU"
          },
          "geo": {
            "@type": "GeoCoordinates",
            "latitude": -33.9189,
            "longitude": 151.0886
          },
          "areaServed": ["New South Wales", "Victoria", "Australian Capital Territory", "South Australia", "Tasmania"]
        },
        {
          "@type": "Service",
          "@id": "https://reactcorpdisability.com.au/#support-coordination",
          "name": "NDIS Support Coordination (Group 0132)",
          "provider": { "@id": "https://reactcorpdisability.com.au/#organization" },
          "serviceType": "Support Coordination",
          "description": "Level 2 Support Coordination and Level 3 Specialist Support Coordination to navigate NDIS plans and healthcare connections."
        },
        {
          "@type": "Service",
          "@id": "https://reactcorpdisability.com.au/#sil-accommodation",
          "name": "Supported Independent Living (SIL) & Crisis Housing",
          "provider": { "@id": "https://reactcorpdisability.com.au/#organization" },
          "serviceType": "SIL Accommodation",
          "description": "24/7 supported independent living accommodation, emergency respite, and transitional group housing."
        }
      ]
    }
    </script>
    <?php
}

// 7. BLOG TEMPLATE ROUTING & TITLE OVERRIDE
add_filter('template_include', 'rc_blog_template_routing', 99);
function rc_blog_template_routing($template) {
    $request_uri = isset($_SERVER['REQUEST_URI']) ? trim($_SERVER['REQUEST_URI'], '/') : '';
    if (is_page('blog') || is_post_type_archive('post') || is_home() || $request_uri === 'blog' || strpos($request_uri, 'blog') === 0) {
        $blog_template = get_stylesheet_directory() . '/template-blog.php';
        if (file_exists($blog_template)) {
            status_header(200);
            return $blog_template;
        }
    }
    return $template;
}

add_filter('pre_get_document_title', 'rc_seo_title_override', 100);
function rc_seo_title_override($title) {
    if (is_front_page()) {
        return 'ReactCorp Disability Services — Registered NDIS Service Provider in Australia';
    }
    $request_uri = isset($_SERVER['REQUEST_URI']) ? trim($_SERVER['REQUEST_URI'], '/') : '';
    if (is_page('blog') || $request_uri === 'blog' || strpos($request_uri, 'blog') === 0) {
        return 'NDIS Blog & Knowledge Center — ReactCorp Disability Services Australia';
    }
    return $title;
}

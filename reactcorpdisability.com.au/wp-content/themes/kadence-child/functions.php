<?php
/**
 * Kadence Child Theme functions and definitions for ReactCorp Disability Services
 * Dynamic NDIS Blog Hub + Permalinks + Menu Injection + SEO Engine
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
        '1.3.0'
    );
}
add_action('wp_enqueue_scripts', 'rc_enqueue_child_assets', 20);

// 2. Automatically flush WordPress rewrite rules, ensure categories & publish initial blog posts
add_action('init', 'rc_auto_fix_404_permalinks', 99);
function rc_auto_fix_404_permalinks() {
    if (get_option('rc_permalinks_fix_v8') !== 'done') {
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
        } else {
            update_post_meta($blog_page->ID, '_wp_page_template', 'template-blog.php');
        }

        // Auto-create default NDIS Categories
        $default_cats = array(
            'sil-housing'  => 'SIL & Housing',
            'compliance'   => 'NDIS Compliance',
            'crisis'       => '24/7 Crisis & STA',
            'coordination' => 'Support Coordination',
            'nursing'      => 'Nursing & In-Home',
            'careers'      => 'Careers'
        );
        $cat_ids = array();
        foreach ($default_cats as $slug => $name) {
            $term = get_term_by('slug', $slug, 'category');
            if (!$term) {
                $new_term = wp_insert_term($name, 'category', array('slug' => $slug));
                if (!is_wp_error($new_term)) {
                    $cat_ids[$slug] = $new_term['term_id'];
                }
            } else {
                $cat_ids[$slug] = $term->term_id;
            }
        }

        // Auto-publish Article 1: SIL Registration Commitment
        $sil_slug = 'reactcorp-commitment-to-continue-sil-registration';
        $existing_sil = function_exists('get_page_by_path') ? get_page_by_path($sil_slug, OBJECT, 'post') : null;
        if (!$existing_sil) {
            $sil_html = '<p class="lead"><strong>Strengthening Quick, Quality, Quantity and Independence in Supported Independent Living</strong></p>
<p>At <strong>ReactCorp Disability Services</strong>, we believe that every person with disability has the right to live with dignity, safety, independence and genuine choice and control over their own life.</p>
<p>Supported Independent Living (SIL) is more than providing assistance with daily activities. A person\'s SIL home is their home first. It is a place where they should feel safe, respected, listened to and empowered to make decisions about how they live.</p>
<p>The introduction of <strong>mandatory NDIS registration for SIL providers from 1 July 2026</strong>, together with the new SIL-specific NDIS Practice Standards, represents an important change for the disability sector. The new standards place a strong focus on participant rights, safety, quality of support, safeguarding and good practice within the home.</p>
<p>At ReactCorp, we are committed to meeting these requirements and embedding the principles behind the standards into our everyday SIL operations.</p>
<h2>1. Supported Decision-Making</h2>
<p>Participants make decisions about their own daily routines, menus, housemates, and supports rather than having decisions made for them.</p>
<h2>2. Safeguarding & Zero Tolerance</h2>
<p>Proactive identification and prevention of abuse, neglect, exploitation, or discrimination across all residential settings.</p>
<h2>3. Practice Governance</h2>
<p>Robust clinical oversight, comprehensive worker screening, and continuous quality audits overseen by dedicated Practice Leaders.</p>
<h2>4. Agreements about Tenancy & Housing</h2>
<p>Clear legal separation between tenancy agreements and support provision to protect participant housing rights.</p>';

            $post_id = wp_insert_post(array(
                'post_title'    => 'ReactCorp’s Commitment to Continue SIL Registration & NDIS Practice Standards',
                'post_name'     => $sil_slug,
                'post_content'  => $sil_html,
                'post_status'   => 'publish',
                'post_type'     => 'post',
                'post_author'   => 1,
                'post_category' => isset($cat_ids['sil-housing']) ? array($cat_ids['sil-housing']) : array()
            ));
        }

        // Auto-publish Article 2: Complete Guide to SIL
        $sil_guide_slug = 'the-complete-participant-guide-to-supported-independent-living-sil';
        $existing_guide = function_exists('get_page_by_path') ? get_page_by_path($sil_guide_slug, OBJECT, 'post') : null;
        if (!$existing_guide) {
            $guide_html = '<p class="lead">Supported Independent Living (SIL) is an NDIS support that provides assistance with daily tasks so participants can live as independently as possible in a home environment.</p>
<h2>What Does SIL Funding Cover vs Out-of-Pocket Living Costs?</h2>
<p>It is important to understand the boundary between what the NDIS funds under SIL and personal daily living costs:</p>
<ul>
    <li><strong>Covered by NDIS:</strong> 24/7 personal care, showering, dressing, meal preparation assistance, medication prompts, and overnight active or sleepover support.</li>
    <li><strong>Paid by Participant:</strong> Rent/board, groceries, utilities (gas, electricity, internet), personal hygiene supplies, and leisure activities.</li>
</ul>
<h2>Understanding the Roster of Care (RoC)</h2>
<p>The Roster of Care is a detailed weekly schedule that models the required support ratios (1:1, 1:2, or 1:3). ReactCorp works with you and your Support Coordinator to build a realistic RoC that ensures all safety and independence needs are fully funded.</p>';

            $post_id2 = wp_insert_post(array(
                'post_title'    => 'The Complete Participant Guide to Supported Independent Living (SIL) Under the NDIS',
                'post_name'     => $sil_guide_slug,
                'post_content'  => $guide_html,
                'post_status'   => 'publish',
                'post_type'     => 'post',
                'post_author'   => 1,
                'post_category' => isset($cat_ids['sil-housing']) ? array($cat_ids['sil-housing']) : array()
            ));
        }

        flush_rewrite_rules(true);
        update_option('rc_permalinks_fix_v8', 'done');
    }
}

// 3. Inject Top Trust Bar & Top-Level Navbar Updates in Header
function rc_inject_topbar_script() {
    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. Top Bar
        var topCenter = document.querySelector("#top-header .site-header-row-layout-center");
        if (topCenter && !topCenter.querySelector(".rc-topbar-wrap")) {
            var html = '<div class="rc-topbar-wrap" style="background:#581c87; color:#ffffff; padding:0.45rem 0; font-size:0.875rem; border-bottom:1px solid rgba(255,255,255,0.15); font-family:\'Inter\',sans-serif;">'
                + '<div class="rc-container" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem; max-width:1200px; margin:0 auto; padding:0 1.5rem;">'
                + '<span>🛡 Registered NDIS Service Provider &nbsp;·&nbsp; #4050064716</span>'
                + '<span style="margin-left:auto;">Emergency Care 24/7: <a href="tel:0422069482" style="color:#f0abfc; font-weight:700; text-decoration:none;">0422 069 482</a></span>'
                + '</div>'
                + '</div>';
            topCenter.insertAdjacentHTML('beforeend', html);
        }

        // 2. Ensure "Updates & Blog" link is present as a TOP-LEVEL item in main navigation menu & mobile drawer
        var allNavMenus = document.querySelectorAll(".primary-menu-container > ul, #primary-menu, nav.main-navigation > ul, .header-menu-container > ul, .mobile-navigation ul, #mobile-menu");
        var isBlogPage = window.location.pathname.indexOf("/blog") !== -1;

        allNavMenus.forEach(function(menu) {
            var hasTopLevelBlog = false;
            var directLis = Array.from(menu.children);
            
            directLis.forEach(function(item) {
                var directLink = item.querySelector(":scope > a");
                if (directLink && directLink.getAttribute("href") && directLink.getAttribute("href").indexOf("/blog") !== -1) {
                    hasTopLevelBlog = true;
                    directLink.innerHTML = '<span class="nav-drop-title">Updates & Blog</span>';
                }
            });

            if (!hasTopLevelBlog) {
                var li = document.createElement("li");
                li.className = "menu-item menu-item-type-custom menu-item-object-custom rc-blog-nav-item" + (isBlogPage ? " current-menu-item" : "");
                li.innerHTML = '<a href="/blog/"><span class="nav-drop-title">Updates & Blog</span></a>';
                
                // Insert before Contact Us or append
                var contactItem = directLis.find(function(el) {
                    return el.textContent && (el.textContent.indexOf("Contact") !== -1 || el.textContent.indexOf("Get In Touch") !== -1);
                });

                if (contactItem) {
                    menu.insertBefore(li, contactItem);
                } else {
                    menu.appendChild(li);
                }
            }
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'rc_inject_topbar_script');

// 4. Navigation Menu Server-Side Filter: Ensure "Updates & Blog" in all WP Menus
add_filter('wp_nav_menu_items', 'rc_add_updates_nav_menu_link', 99, 2);
function rc_add_updates_nav_menu_link($items, $args) {
    if (strpos($items, 'Updates & Blog') === false && strpos($items, 'Updates &amp; Blog') === false) {
        $is_active = (is_page('blog') || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/blog') !== false)) ? ' current-menu-item' : '';
        $new_item = '<li class="menu-item menu-item-type-custom rc-blog-nav-item' . $is_active . '"><a href="' . esc_url(home_url('/blog/')) . '"><span class="nav-drop-title-wrap"><span class="nav-drop-title">Updates &amp; Blog</span></span></a></li>';
        
        if (stripos($items, 'Contact') !== false) {
            $pattern = '/(<li\s+[^>]*id=["\']menu-item-[^>]*>(?:\s*<a[^>]*>(?:(?!<\/li>).)*?Contact))/is';
            if (preg_match($pattern, $items)) {
                $items = preg_replace($pattern, $new_item . '$1', $items, 1);
            } else {
                $items .= $new_item;
            }
        } else {
            $items .= $new_item;
        }
    }
    return $items;
}

// 5. Custom Footer Component
function rc_custom_footer() {
    echo '<style>#colophon { display: none !important; }</style>';
    ?>
    <footer class="sf-footer" style="background:#0f172a; color:#f8fafc; padding: 4.5rem 0 2rem 0; font-family:'Inter',sans-serif;" aria-label="Site Footer">
        <div class="rc-container" style="max-width:1200px; margin:0 auto; padding:0 1.5rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 2.5rem; margin-bottom: 3rem;">
                
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
                    <ul style="list-style:none; padding:0; line-height:2.2; font-size:0.9rem;">
                        <li><a href="/" style="color:#cbd5e1; text-decoration:none;">Home</a></li>
                        <li><a href="/our-services/" style="color:#cbd5e1; text-decoration:none;">Our Services</a></li>
                        <li><a href="/blog/" style="color:#cbd5e1; text-decoration:none;">NDIS Blog & Guides</a></li>
                        <li><a href="/contact/" style="color:#cbd5e1; text-decoration:none;">Contact Us</a></li>
                        <li><a href="https://zfrmz.com/sIh6uDqI2c9PaujmOoTR" target="_blank" rel="noopener" style="color:#f0abfc; text-decoration:none; font-weight:600;">Online Referral Form</a></li>
                    </ul>
                </div>

                <!-- Registration Groups -->
                <div>
                    <h4 style="font-family:'Outfit',sans-serif; font-size: 1.1rem; font-weight:700; color:#ffffff; margin-bottom: 1rem;">NDIS Key Groups</h4>
                    <ul style="list-style:none; padding:0; line-height:2.2; font-size:0.9rem;">
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

// 6. SEO META ENGINE (Title Tags, Meta Descriptions, OpenGraph, Canonical, GEO Meta)
add_action('wp_head', 'rc_seo_meta_engine', 0);
function rc_seo_meta_engine() {
    $site_url    = home_url('/');
    $current_url = is_front_page() ? $site_url : get_permalink();
    $request_uri = isset($_SERVER['REQUEST_URI']) ? trim($_SERVER['REQUEST_URI'], '/') : '';

    if (is_front_page()) {
        $page_title = 'NDIS Registered Provider Sydney NSW — ReactCorp Disability Services';
        $meta_desc  = 'ReactCorp is an NDIS Registered Provider in Sydney NSW (#4050064716). 24/7 crisis support, support coordination (Level 1, 2, 3), SIL accommodation, and personal care across NSW, VIC, ACT, SA & TAS. Call 0422 069 482.';
    } elseif (is_page('blog') || $request_uri === 'blog' || strpos($request_uri, 'blog') === 0) {
        $page_title = 'NDIS Blog & Knowledge Center — ReactCorp Disability Services Australia';
        $meta_desc  = 'Comprehensive NDIS blog & search guide by ReactCorp Disability Services covering Registered NDIS Provider guides, 24/7 crisis housing, support coordination, and disability care rights.';
    } elseif (is_page('our-services') || strpos($request_uri, 'services') !== false) {
        $page_title = 'NDIS Services — Support Coordination, SIL Accommodation & Personal Care | ReactCorp';
        $meta_desc  = 'Explore ReactCorp Disability NDIS services: Group 0132 Support Coordination, SIL Shared Housing, In-Home Personal Care, Community Access, and 24/7 Crisis Respite across Australia.';
    } elseif (is_page('contact-us') || is_page('contact') || strpos($request_uri, 'contact') !== false) {
        $page_title = 'Contact ReactCorp — 24/7 Emergency NDIS Intake North Kellyville Sydney';
        $meta_desc  = 'Contact ReactCorp Disability Services for 24/7 NDIS emergency intake in North Kellyville Sydney. Call 0422 069 482, email info@reactcorpdisability.com.au, or submit an online referral form.';
    } elseif (is_page('price') || strpos($request_uri, 'price') !== false) {
        $page_title = 'NDIS Price Guide & Plan Support — ReactCorp Disability Services Sydney';
        $meta_desc  = 'ReactCorp Disability is 100% NDIS Price Guide compliant. Transparent pricing for Support Coordination (0132), SIL Accommodation (0115), Personal Care & Community Participation in North Kellyville Sydney.';
    } else {
        $page_title = wp_get_document_title();
        $meta_desc  = 'ReactCorp Disability Services — Registered NDIS Service Provider offering support coordination, SIL accommodation, personal care & 24/7 crisis support.';
    }

    $logo_url = 'https://reactcorpdisability.com.au/wp-content/uploads/2024/02/cropped-reactcorp-logo.png';
    $keywords = 'NDIS registered provider Sydney NSW, NDIS provider Sydney, registered NDIS provider NSW, ReactCorp Disability Services, NDIS Support Coordination Group 0132 Sydney, SIL Accommodation Sydney NSW, Personal Care NDIS Sydney, 24/7 Crisis Support NDIS Sydney, NDIS Provider Registration Number 4050064716';
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

    <!-- OG Image Dimensions -->
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:type" content="image/png">

    <!-- HREFLANG -->
    <link rel="alternate" hreflang="en-au" href="<?php echo esc_url($current_url); ?>">
    <link rel="alternate" hreflang="en" href="<?php echo esc_url($current_url); ?>">
    <link rel="alternate" hreflang="x-default" href="<?php echo esc_url($current_url); ?>">
    <?php
}

// 7. SCHEMA.ORG JSON-LD STRUCTURED DATA GRAPH
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
          "description": "NDIS Registered Provider in Sydney NSW delivering 24/7 crisis support, support coordination & SIL accommodation",
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

// 8. BLOG TEMPLATE ROUTING & TITLE OVERRIDE
add_filter('template_include', 'rc_blog_template_routing', 99);
function rc_blog_template_routing($template) {
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

add_filter('pre_get_document_title', 'rc_seo_title_override', 100);
function rc_seo_title_override($title) {
    if (is_front_page()) {
        return 'NDIS Registered Provider Sydney NSW — ReactCorp Disability Services';
    }
    $request_uri = isset($_SERVER['REQUEST_URI']) ? trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') : '';
    if (is_page('blog') || is_home() || $request_uri === 'blog' || strpos($request_uri, 'blog') === 0) {
        return 'NDIS Blog & Knowledge Center — ReactCorp Disability Services Australia';
    }
    if (is_page('contact-us') || is_page('contact') || strpos($request_uri, 'contact') !== false) {
        return 'Contact ReactCorp — 24/7 Emergency NDIS Intake North Kellyville Sydney';
    }
    if (is_page('price') || strpos($request_uri, 'price') !== false) {
        return 'NDIS Price Guide & Plan Support — ReactCorp Disability Services Sydney';
    }
    if (is_page('our-services') || strpos($request_uri, 'services') !== false) {
        return 'NDIS Services — Support Coordination, SIL Accommodation & Personal Care | ReactCorp';
    }
    return $title;
}

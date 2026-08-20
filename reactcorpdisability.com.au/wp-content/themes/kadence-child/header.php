<?php
/**
 * The header for our theme
 * Custom Professional Navbar & Rank #1 SEO Engine for ReactCorp Disability Services
 */

namespace Kadence;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!doctype html>
<html <?php language_attributes(); ?> class="no-js" <?php kadence()->print_microdata( 'html' ); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
	<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
	
	<!-- High-Intent Rank #1 SEO Meta Tags -->
	<meta name="description" content="ReactCorp is an NDIS Registered Provider in North Kellyville, Sydney NSW. Offering Support Coordination (Level 1, 2, 3), SIL Accommodation (0115), Personal Care & 24/7 Crisis Support. Call 0422 069 482.">
	<meta name="keywords" content="NDIS provider North Kellyville NSW, Support coordination North Kellyville Sydney, SIL accommodation North Kellyville, NDIS registered provider Sydney, 24/7 emergency NDIS intake Sydney, NDIS support coordination Level 1 2 3 Sydney, Supported Independent Living SIL Sydney, NDIS price guide compliant provider Sydney, NDIS 0132 Support Coordination Sydney, NDIS 0115 SIL accommodation Sydney">
	<meta name="author" content="ReactCorp Disability Services">
	
	<!-- Open Graph / Social Media Meta -->
	<meta property="og:locale" content="en_AU">
	<meta property="og:type" content="website">
	<meta property="og:title" content="NDIS Registered Provider North Kellyville Sydney | ReactCorp Disability Services">
	<meta property="og:description" content="Official NDIS Registered Provider in North Kellyville, Sydney. Support Coordination (0132), SIL Accommodation (0115), Personal Care & 24/7 Crisis Support.">
	<meta property="og:url" content="https://reactcorpdisability.com.au/">
	<meta property="og:site_name" content="ReactCorp Disability Services">
	<meta property="og:image" content="<?php echo get_site_url(); ?>/wp-content/uploads/2026/03/cropped-logo-1.jpeg">
	
	<!-- Twitter Card Meta -->
	<meta name="twitter:card" content="summary_large_image">
	<meta name="twitter:title" content="NDIS Registered Provider North Kellyville Sydney | ReactCorp Disability Services">
	<meta name="twitter:description" content="NDIS Registered Provider delivering 24/7 Crisis Response, Support Coordination, SIL Accommodation, and Personal Care in North Kellyville & Sydney NSW.">
	<meta name="twitter:image" content="<?php echo get_site_url(); ?>/wp-content/uploads/2026/03/cropped-logo-1.jpeg">

	<!-- Geo & Local SEO Tags -->
	<meta name="geo.region" content="AU-NSW">
	<meta name="geo.placename" content="North Kellyville, Sydney">
	<meta name="geo.position" content="-33.9261;151.0714">
	<meta name="ICBM" content="-33.9261, 151.0714">

	<!-- Fonts Preconnect -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
	
	<?php wp_head(); ?>

	<!-- Schema.org JSON-LD Local Business & NDIS Provider Structured Data -->
	<script type="application/ld+json">
	{
	  "@context": "https://schema.org",
	  "@type": "MedicalBusiness",
	  "name": "ReactCorp Disability Services",
	  "alternateName": "ReactCorp NDIS Provider North Kellyville",
	  "image": "<?php echo get_site_url(); ?>/wp-content/uploads/2026/03/cropped-logo-1.jpeg",
	  "@id": "https://reactcorpdisability.com.au/#organization",
	  "url": "https://reactcorpdisability.com.au/",
	  "telephone": "+61-422-069-482",
	  "email": "info@reactcorpdisability.com.au",
	  "priceRange": "$$",
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
	    "latitude": -33.9261,
	    "longitude": 151.0714
	  },
	  "openingHoursSpecification": {
	    "@type": "OpeningHoursSpecification",
	    "dayOfWeek": [
	      "Monday",
	      "Tuesday",
	      "Wednesday",
	      "Thursday",
	      "Friday",
	      "Saturday",
	      "Sunday"
	    ],
	    "opens": "00:00",
	    "closes": "23:59"
	  },
	  "areaServed": [
	    {
	      "@type": "City",
	      "name": "North Kellyville"
	    },
	    {
	      "@type": "AdministrativeArea",
	      "name": "Canterbury-Bankstown"
	    },
	    {
	      "@type": "City",
	      "name": "Sydney"
	    },
	    {
	      "@type": "AdministrativeArea",
	      "name": "New South Wales"
	    }
	  ],
	  "knowsAbout": [
	    "NDIS Provider North Kellyville NSW",
	    "Support Coordination North Kellyville Sydney",
	    "SIL Accommodation North Kellyville",
	    "NDIS Support Coordination Level 1 2 3 Sydney",
	    "Supported Independent Living SIL Sydney",
	    "NDIS Price Guide Compliant Provider Sydney",
	    "NDIS 0132 Support Coordination Sydney",
	    "NDIS 0115 SIL Accommodation Sydney",
	    "24/7 Emergency NDIS Intake Sydney"
	  ]
	}
	</script>

	<!-- BreadcrumbList Schema -->
	<script type="application/ld+json">
	{
	  "@context": "https://schema.org",
	  "@type": "BreadcrumbList",
	  "itemListElement": [{
	    "@type": "ListItem",
	    "position": 1,
	    "name": "Home",
	    "item": "https://reactcorpdisability.com.au/"
	  },{
	    "@type": "ListItem",
	    "position": 2,
	    "name": "Our Services",
	    "item": "https://reactcorpdisability.com.au/our-services/"
	  },{
	    "@type": "ListItem",
	    "position": 3,
	    "name": "Price",
	    "item": "https://reactcorpdisability.com.au/price/"
	  },{
	    "@type": "ListItem",
	    "position": 4,
	    "name": "Contact",
	    "item": "https://reactcorpdisability.com.au/contact/"
	  }]
	}
	</script>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<style>
/* Hide default theme headers to prevent duplicates */
#site-header, .site-header, #masthead, .kadence-header-wrap { display: none !important; }

/* Custom Redesigned Professional Navbar */
.rc-navbar {
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    position: sticky;
    top: 0;
    z-index: 1000;
    padding: 0.85rem 0;
    border-bottom: 1px solid rgba(128, 56, 125, 0.08);
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.rc-nav-wrap {
    max-width: 1240px;
    margin: 0 auto;
    padding: 0 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.rc-logo-img-link {
    display: inline-flex;
    align-items: center;
    text-decoration: none;
}

.rc-logo-img {
    height: 52px;
    width: auto;
    display: block;
    border-radius: 6px;
}

.rc-nav-center-right {
    display: flex;
    align-items: center;
    gap: 2.5rem;
}

.rc-nav-menu {
    display: flex;
    align-items: center;
    gap: 2.25rem;
    list-style: none;
    margin: 0;
    padding: 0;
}

.rc-nav-menu a {
    color: #334155;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.98rem;
    position: relative;
    padding: 0.25rem 0;
    transition: color 0.25s ease;
}

.rc-nav-menu a::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: #80387d;
    border-radius: 2px;
    transition: width 0.25s ease;
}

.rc-nav-menu a:hover {
    color: #80387d;
}

.rc-nav-menu a:hover::after {
    width: 100%;
}

.rc-nav-call-btn {
    background: linear-gradient(135deg, #80387d 0%, #581c87 100%);
    color: #ffffff !important;
    padding: 0.7rem 1.4rem;
    border-radius: 50px;
    font-weight: 800;
    font-size: 0.95rem;
    text-decoration: none;
    box-shadow: 0 6px 16px rgba(128, 56, 125, 0.28);
    transition: all 0.25s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

.rc-nav-call-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 22px rgba(128, 56, 125, 0.38);
}

@media (max-width: 1024px) {
    .rc-nav-wrap { flex-direction: column; gap: 1rem; text-align: center; }
    .rc-nav-center-right { flex-direction: column; gap: 1rem; }
}
</style>

<!-- Semantic Header Element -->
<header class="rc-navbar" role="banner">
    <div class="rc-nav-wrap">
        
        <!-- Left: Uploaded ReactCorp Logo Image with Descriptive Alt Tag -->
        <a href="<?php echo get_site_url(); ?>/" class="rc-logo-img-link" title="ReactCorp Disability Services Homepage">
            <img src="<?php echo get_site_url(); ?>/wp-content/uploads/2026/03/cropped-logo-1.jpeg" alt="ReactCorp Registered NDIS Provider Roselands Sydney Logo" class="rc-logo-img" width="180" height="52">
        </a>

        <!-- Right: Semantic Navigation Menu -->
        <nav class="rc-nav-center-right" role="navigation" aria-label="Main Navigation">
            <ul class="rc-nav-menu">
                <li><a href="<?php echo get_site_url(); ?>/our-services/">Our Services</a></li>
                <li><a href="<?php echo get_site_url(); ?>/price/">Price</a></li>
                <li><a href="<?php echo get_site_url(); ?>/contact/">Contact</a></li>
            </ul>

            <a href="tel:0422069482" class="rc-nav-call-btn" title="Call ReactCorp 24/7 NDIS Emergency Intake Sydney">
                📞 0422 069 482
            </a>
        </nav>

    </div>
</header>

<div id="wrapper" class="site wp-site-blocks">
    <main id="inner-wrap" class="wrap kt-clear" role="main">

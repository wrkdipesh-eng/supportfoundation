<?php
/**
 * The template for displaying all single posts for ReactCorp Disability Services.
 *
 * @package kadence-child
 */

get_header();
?>

<style>
/* --- REACTCORP SINGLE POST READER STYLES --- */
:root {
    --rc-purple: #581c87;
    --rc-purple-light: #7e22ce;
    --rc-fuchsia: #c084fc;
    --rc-teal: #0d9488;
    --rc-dark: #0f172a;
    --rc-slate-900: #0f172a;
    --rc-slate-800: #1e293b;
    --rc-slate-700: #334155;
    --rc-slate-600: #475569;
    --rc-slate-500: #64748b;
    --rc-slate-100: #f1f5f9;
    --rc-slate-50: #f8fafc;
}

.rc-single-post-wrapper {
    background-color: #f8fafc;
    color: var(--rc-slate-800);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    padding-bottom: 5rem;
    min-height: 80vh;
}

/* Post Hero */
.rc-single-hero {
    background: linear-gradient(135deg, #2e1065 0%, #581c87 50%, #7e22ce 100%);
    color: #ffffff;
    padding: 4.5rem 1.5rem 3.5rem;
    position: relative;
}

.rc-single-hero-inner {
    max-width: 900px;
    margin: 0 auto;
}

.rc-breadcrumb-trail {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.875rem;
    color: #f0abfc;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
}

.rc-breadcrumb-trail a {
    color: #ffffff;
    text-decoration: none;
    transition: color 0.2s ease;
}

.rc-breadcrumb-trail a:hover {
    color: #f0abfc;
    text-decoration: underline;
}

.rc-single-post-title {
    font-family: 'Outfit', sans-serif;
    font-size: clamp(2rem, 4vw, 3.25rem);
    font-weight: 800;
    line-height: 1.2;
    color: #ffffff;
    margin-bottom: 1.5rem;
    letter-spacing: -0.5px;
}

.rc-single-post-meta {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    font-size: 0.9rem;
    color: #e9d5ff;
}

.rc-single-meta-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* Post Layout */
.rc-single-layout {
    max-width: 1240px;
    margin: -2rem auto 0;
    padding: 0 1.5rem;
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 2.5rem;
    position: relative;
    z-index: 5;
}

@media (max-width: 992px) {
    .rc-single-layout {
        grid-template-columns: 1fr;
        margin-top: 2rem;
    }
}

/* Main Article Card */
.rc-single-article-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 3rem;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
    border: 1px solid #e2e8f0;
}

@media (max-width: 768px) {
    .rc-single-article-card {
        padding: 1.75rem;
    }
}

/* Featured Image */
.rc-single-featured-image {
    width: 100%;
    max-height: 480px;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 2.5rem;
    border: 1px solid #e2e8f0;
}

.rc-single-featured-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* Content Typography */
.rc-single-content {
    font-size: 1.1rem;
    line-height: 1.85;
    color: var(--rc-slate-700);
}

.rc-single-content p {
    margin-bottom: 1.65rem;
}

.rc-single-content h2 {
    font-family: 'Outfit', sans-serif;
    font-size: 1.85rem;
    font-weight: 700;
    color: var(--rc-purple);
    margin: 2.5rem 0 1.25rem;
    line-height: 1.3;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #f3e8ff;
}

.rc-single-content h3 {
    font-family: 'Outfit', sans-serif;
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--rc-slate-900);
    margin: 2rem 0 1rem;
}

.rc-single-content ul, .rc-single-content ol {
    margin: 1.5rem 0 1.75rem;
    padding-left: 1.75rem;
}

.rc-single-content li {
    margin-bottom: 0.65rem;
}

.rc-single-content blockquote {
    background: #faf5ff;
    border-left: 4px solid var(--rc-purple-light);
    border-radius: 0 12px 12px 0;
    padding: 1.5rem 2rem;
    margin: 2rem 0;
    font-size: 1.15rem;
    font-style: italic;
    color: #4c1d95;
    font-weight: 500;
}

.rc-single-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 2rem 0;
    font-size: 0.95rem;
}

.rc-single-content table th, .rc-single-content table td {
    padding: 12px 16px;
    border: 1px solid #e2e8f0;
    text-align: left;
}

.rc-single-content table th {
    background: #f5f3ff;
    color: var(--rc-purple);
    font-weight: 700;
}

/* Author Box */
.rc-author-box {
    margin-top: 3.5rem;
    padding-top: 2rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 20px;
    background: #faf5ff;
    padding: 1.5rem 2rem;
    border-radius: 12px;
}

.rc-author-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--rc-purple) 0%, var(--rc-purple-light) 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1.35rem;
    flex-shrink: 0;
}

.rc-author-info h4 {
    font-family: 'Outfit', sans-serif;
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--rc-purple);
    margin: 0 0 4px;
}

.rc-author-info p {
    font-size: 0.9rem;
    color: var(--rc-slate-600);
    margin: 0;
    line-height: 1.5;
}

/* Sidebar Widgets */
.rc-sidebar-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 15px -2px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    margin-bottom: 2rem;
}

.rc-sidebar-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--rc-slate-900);
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.rc-hotline-card {
    background: linear-gradient(135deg, #2e1065 0%, #581c87 100%);
    color: #ffffff;
    border: none;
}

.rc-hotline-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: #ffffff;
    color: var(--rc-purple);
    font-weight: 800;
    font-size: 1.1rem;
    padding: 14px 20px;
    border-radius: 50px;
    text-decoration: none;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
    margin-top: 1.25rem;
    transition: all 0.2s ease;
}

.rc-hotline-btn:hover {
    transform: translateY(-2px);
    background: #fdf4ff;
    color: var(--rc-purple-light);
}

.rc-referral-btn {
    display: block;
    width: 100%;
    text-align: center;
    background: linear-gradient(135deg, var(--rc-purple) 0%, var(--rc-purple-light) 100%);
    color: #ffffff;
    font-weight: 700;
    font-size: 0.95rem;
    padding: 12px 18px;
    border-radius: 8px;
    text-decoration: none;
    margin-top: 1rem;
    transition: all 0.2s ease;
}

.rc-referral-btn:hover {
    opacity: 0.95;
    color: #ffffff;
}
</style>

<div class="rc-single-post-wrapper">

    <?php while (have_posts()) : the_post(); 
        $categories = get_the_category();
        $cat_name = !empty($categories) ? $categories[0]->name : 'NDIS Guide';
        $word_count = str_word_count(strip_tags(get_the_content()));
        $read_time = ceil($word_count / 200);
        if ($read_time < 1) $read_time = 3;
    ?>
    
    <!-- Hero Banner -->
    <header class="rc-single-hero">
        <div class="rc-single-hero-inner">
            <nav class="rc-breadcrumb-trail" aria-label="Breadcrumb">
                <a href="<?php echo home_url('/'); ?>">Home</a>
                <span>›</span>
                <a href="<?php echo home_url('/blog/'); ?>">NDIS Blog</a>
                <span>›</span>
                <span><?php echo esc_html($cat_name); ?></span>
            </nav>
            <h1 class="rc-single-post-title"><?php the_title(); ?></h1>
            <div class="rc-single-post-meta">
                <span class="rc-single-meta-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <?php echo get_the_date('M j, Y'); ?>
                </span>
                <span class="rc-single-meta-item">⏱️ <?php echo $read_time; ?> min read</span>
                <span class="rc-single-meta-item">🛡️ Registered NDIS Provider #4050064716</span>
            </div>
        </div>
    </header>

    <!-- Main Content Layout -->
    <div class="rc-single-layout">
        
        <main>
            <article class="rc-single-article-card">
                <?php if (has_post_thumbnail()) : ?>
                    <div class="rc-single-featured-image">
                        <?php the_post_thumbnail('large', array('alt' => get_the_title())); ?>
                    </div>
                <?php endif; ?>

                <div class="rc-single-content">
                    <?php the_content(); ?>
                </div>

                <!-- Author Box -->
                <div class="rc-author-box">
                    <div class="rc-author-avatar">RC</div>
                    <div class="rc-author-info">
                        <h4>ReactCorp Disability Services Editorial Team</h4>
                        <p>Published by qualified NDIS Support Coordinators, Practice Leaders, and Registered Disability Practitioners committed to participant autonomy, safety, and empowerment across Australia.</p>
                    </div>
                </div>
            </article>
        </main>

        <!-- Sidebar -->
        <aside>
            <!-- 24/7 Crisis Hotline -->
            <div class="rc-sidebar-card rc-hotline-card">
                <h3 class="rc-sidebar-title" style="color: #ffffff;">
                    <span>🚨</span> Need Urgent NDIS Care?
                </h3>
                <p style="font-size: 0.925rem; line-height: 1.6; color: #e9d5ff;">Our dedicated emergency response team is available 24 hours a day, 7 days a week for immediate crisis accommodation, respite, and urgent care.</p>
                <a href="tel:0422069482" class="rc-hotline-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    0422 069 482
                </a>
                <div style="text-align:center; margin-top: 0.75rem; font-size: 0.85rem; color: #f0abfc;">
                    Landline: <a href="tel:0283861433" style="color: #ffffff; text-decoration: underline;">02 8386 1433</a>
                </div>
            </div>

            <!-- Online Referral Widget -->
            <div class="rc-sidebar-card">
                <h3 class="rc-sidebar-title">
                    <span>📋</span> Make an NDIS Referral
                </h3>
                <p style="font-size: 0.925rem; line-height: 1.6; color: var(--rc-slate-600);">We accept referrals from participants, families, support coordinators, and healthcare professionals.</p>
                <a href="https://zfrmz.com/sIh6uDqI2c9PaujmOoTR" target="_blank" rel="noopener noreferrer" class="rc-referral-btn">
                    Open Online Referral Form →
                </a>
            </div>

            <!-- Back to All Guides -->
            <div class="rc-sidebar-card" style="text-align: center;">
                <h4 style="font-family:'Outfit',sans-serif; font-size:1.1rem; font-weight:700; color:var(--rc-purple); margin-bottom:0.5rem;">Explore All Guides</h4>
                <p style="font-size:0.875rem; color:var(--rc-slate-600); margin-bottom:1rem;">Browse our complete knowledge library of NDIS regulations and participant resources.</p>
                <a href="<?php echo home_url('/blog/'); ?>" style="display:inline-block; padding:8px 20px; border:2px solid var(--rc-purple); border-radius:30px; color:var(--rc-purple); font-weight:700; font-size:0.9rem; text-decoration:none;">View Blog Hub →</a>
            </div>
        </aside>

    </div>

    <?php endwhile; ?>

</div>

<?php get_footer(); ?>

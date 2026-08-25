<?php
/**
 * Single Post Template for Support Foundation Australia
 * Description: Clean, modern reader layout for individual blog articles.
 */

get_header();

while (have_posts()) : the_post();
    $categories = get_the_category();
    $cat_name = !empty($categories) ? esc_html($categories[0]->name) : 'NDIS Guide';
    $post_content = get_the_content();
    $read_time = max(1, round(str_word_count(strip_tags($post_content)) / 200));
?>

<style>
.sf-single-post-wrap {
    background-color: #f8fafc;
    color: #334155;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    min-height: 100vh;
    padding-bottom: 5rem;
}

/* Post Hero */
.sf-single-hero {
    background: linear-gradient(135deg, #022c22 0%, #064e3b 45%, #047857 100%);
    color: #ffffff;
    padding: 4.5rem 1.5rem 3.5rem;
}

.sf-single-hero-inner {
    max-width: 860px;
    margin: 0 auto;
}

.sf-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #a7f3d0;
    margin-bottom: 1.25rem;
}

.sf-breadcrumb a {
    color: #a7f3d0;
    text-decoration: none;
}

.sf-breadcrumb a:hover {
    text-decoration: underline;
}

.sf-single-title {
    font-family: 'Outfit', sans-serif;
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 800;
    line-height: 1.2;
    color: #ffffff;
    margin-bottom: 1.5rem;
}

.sf-single-meta {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    font-size: 0.9rem;
    color: #e2e8f0;
}

.sf-single-tag {
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff;
    padding: 4px 12px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 0.8rem;
    text-transform: uppercase;
}

/* Main Post Layout */
.sf-single-layout {
    max-width: 1240px;
    margin: 0 auto;
    padding: 3rem 1.5rem;
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 3rem;
}

@media (max-width: 992px) {
    .sf-single-layout {
        grid-template-columns: 1fr;
    }
}

.sf-single-article {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 3rem 2.5rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
}

@media (max-width: 640px) {
    .sf-single-article {
        padding: 2rem 1.25rem;
    }
}

.sf-featured-image-wrap {
    margin-bottom: 2.5rem;
    border-radius: 14px;
    overflow: hidden;
    max-height: 480px;
}

.sf-featured-image-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.sf-post-content {
    font-size: 1.1rem;
    line-height: 1.85;
    color: #334155;
}

.sf-post-content h2 {
    font-family: 'Outfit', sans-serif;
    font-size: 1.85rem;
    font-weight: 800;
    color: #0f172a;
    margin: 2.25rem 0 1rem;
    line-height: 1.3;
}

.sf-post-content h3 {
    font-family: 'Outfit', sans-serif;
    font-size: 1.45rem;
    font-weight: 700;
    color: #1e293b;
    margin: 1.75rem 0 0.75rem;
}

.sf-post-content p {
    margin-bottom: 1.5rem;
}

.sf-post-content ul, .sf-post-content ol {
    margin: 0 0 1.75rem 1.75rem;
}

.sf-post-content li {
    margin-bottom: 0.5rem;
}

.sf-post-content blockquote {
    background: #ecfdf5;
    border-left: 4px solid #10b981;
    margin: 2rem 0;
    padding: 1.5rem 2rem;
    border-radius: 0 12px 12px 0;
    font-style: italic;
    color: #064e3b;
}

.sf-post-footer-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 2rem;
    margin-top: 3rem;
    border-top: 1px solid #e2e8f0;
    flex-wrap: wrap;
    gap: 16px;
}

.sf-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #ecfdf5;
    color: #047857;
    font-weight: 700;
    padding: 10px 20px;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.2s ease;
}

.sf-back-btn:hover {
    background: #047857;
    color: #ffffff;
}

/* Sidebar */
.sf-single-sidebar {
    position: sticky;
    top: 100px;
}

.sf-single-sidebox {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 1.75rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.02);
}

.sf-sidebox-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #10b981;
}

.sf-side-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    background: #047857;
    color: #ffffff;
    font-weight: 700;
    padding: 12px 16px;
    border-radius: 10px;
    text-decoration: none;
    margin-top: 1rem;
    box-sizing: border-box;
}

.sf-side-btn:hover {
    background: #064e3b;
    color: #ffffff;
}
</style>

<div class="sf-single-post-wrap">

    <!-- HERO -->
    <header class="sf-single-hero">
        <div class="sf-single-hero-inner">
            <div class="sf-breadcrumb">
                <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
                <span>›</span>
                <a href="<?php echo esc_url(home_url('/blog/')); ?>">NDIS Blog & Guides</a>
                <span>›</span>
                <span><?php echo esc_html($cat_name); ?></span>
            </div>
            
            <h1 class="sf-single-title"><?php the_title(); ?></h1>
            
            <div class="sf-single-meta">
                <span class="sf-single-tag"><?php echo esc_html($cat_name); ?></span>
                <span>📅 <?php echo get_the_date('F j, Y'); ?></span>
                <span>⏱️ <?php echo $read_time; ?> min read</span>
                <span>✍️ <?php the_author(); ?></span>
            </div>
        </div>
    </header>

    <!-- CONTENT LAYOUT -->
    <div class="sf-single-layout">
        <main>
            <article class="sf-single-article">
                <?php if (has_post_thumbnail()) : ?>
                    <div class="sf-featured-image-wrap">
                        <?php the_post_thumbnail('large'); ?>
                    </div>
                <?php endif; ?>

                <div class="sf-post-content">
                    <?php the_content(); ?>
                </div>

                <div class="sf-post-footer-nav">
                    <a href="<?php echo esc_url(home_url('/blog/')); ?>" class="sf-back-btn">
                        ← Back to NDIS Blog
                    </a>
                    
                    <a href="https://zfrmz.com/sIh6uDqI2c9PaujmOoTR" target="_blank" rel="noopener" class="sf-back-btn" style="background: #047857; color: #ffffff;">
                        Make a Service Referral →
                    </a>
                </div>
            </article>
        </main>

        <!-- SIDEBAR -->
        <aside>
            <div class="sf-single-sidebar">
                
                <!-- Referral Box -->
                <div class="sf-single-sidebox">
                    <h3 class="sf-sidebox-title">Need Disability Support?</h3>
                    <p style="font-size: 0.95rem; line-height: 1.6; color: #64748b;">
                        Support Foundation offers SIL Housing, Support Coordination, 24/7 Crisis Respite, and In-Home Nursing across Australia.
                    </p>
                    <a href="https://zfrmz.com/sIh6uDqI2c9PaujmOoTR" target="_blank" rel="noopener" class="sf-side-btn">
                        Make a Referral
                    </a>
                </div>

                <!-- 24/7 Crisis Line -->
                <div class="sf-single-sidebox" style="background: linear-gradient(135deg, #022c22, #064e3b); color: #ffffff;">
                    <h3 class="sf-sidebox-title" style="color: #ffffff; border-color: #34d399;">24/7 Emergency Line</h3>
                    <p style="font-size: 0.9rem; line-height: 1.6; color: #d1fae5;">
                        Urgent care breakdown or immediate emergency housing needed?
                    </p>
                    <a href="tel:0283861433" class="sf-side-btn" style="background: #10b981; color: #ffffff;">
                        📞 02 8386 1433
                    </a>
                </div>

            </div>
        </aside>
    </div>

</div>

<?php
endwhile;
get_footer();

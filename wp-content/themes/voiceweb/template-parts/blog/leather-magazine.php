<?php
/** The leather journal uses the main WordPress query so pagination stays crawlable. */
defined( 'ABSPATH' ) || exit;
global $wp_query;
$page = max( 1, (int) get_query_var( 'paged' ) );
$cards = $wp_query->posts;
?>
<section class="vw-magazine">
	<div class="container">
		<header class="vw-magazine-hero">
			<span class="vw-magazine-eyebrow">همراهی با چرم طبیعی</span>
			<h1>مجلهٔ آوان چرم<?php if ( $page > 1 ) { echo '؛ صفحهٔ ' . esc_html( cn_localize_digits( $page ) ); } ?></h1>
			<p>انتخاب یک همراه چرمی از شناخت نیازتان شروع می‌شود. اینجا دربارهٔ خرید کیف و کیف پول، مراقبت از چرم طبیعی و انتخاب هدیه می‌خوانید؛ راهنماهایی کاربردی برای تصمیمی که با سبک زندگی شما هماهنگ باشد.</p>
			<a class="vw-magazine-hero__link" href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>">دیدن محصولات آوان چرم <span aria-hidden="true">←</span></a>
		</header>
		<section class="vw-magazine-articles" aria-labelledby="vw-magazine-articles-title">
			<h2 id="vw-magazine-articles-title" class="sec-title">راهنماهای خرید و نگهداری چرم</h2>
			<?php if ( $cards ) { ?>
				<div class="vw-magazine-grid">
					<?php foreach ( $cards as $article ) { cn_card( 'grid', array( 'post_id' => $article->ID, 'words' => 28, 'read_more' => true, 'sizes' => '(max-width: 767px) 100vw, (max-width: 991px) 50vw, 33vw' ) ); } ?>
				</div>
				<?php
				$pagination = paginate_links( array( 'current' => $page, 'total' => (int) $wp_query->max_num_pages, 'type' => 'list', 'prev_text' => 'صفحهٔ قبل', 'next_text' => 'صفحهٔ بعد' ) );
				if ( $pagination ) { echo '<nav class="vw-magazine-pagination" aria-label="صفحه‌بندی مجله">' . wp_kses_post( $pagination ) . '</nav>'; }
				?>
			<?php } else { ?>
				<p>راهنمای تازه‌ای در این صفحه منتشر نشده است.</p>
			<?php } ?>
		</section>
		<?php voiceweb_leather_discovery(); ?>
	</div>
</section>

<?php
defined( 'ABSPATH' ) || exit;
$rows = $args['categories'];
if ( ! $rows || ! voiceweb_front_enabled( 'categories' ) ) { return; }
?>
<section class="vw-store-section" id="home-categories" aria-labelledby="home-categories-title">
	<div class="vw-store-heading"><h2 id="home-categories-title"><?php echo esc_html( voiceweb_front_value( 'categories_title', 'دسته‌بندی محصولات' ) ); ?></h2><?php voiceweb_front_link( array( 'url' => $args['shop'], 'title' => 'مشاهدهٔ همه' ) ); ?></div>
	<div class="vw-store-categories">
		<?php foreach ( $rows as $row ) : ?>
		<a class="vw-store-category" href="<?php echo esc_url( $row['url'] ); ?>">
			<span class="vw-store-category__image"><?php voiceweb_front_image( absint( $row['image'] ?? 0 ), '', false, false, '(max-width: 767px) 120px, 190px' ); ?></span>
			<strong><?php echo esc_html( $row['label'] ); ?></strong><span><?php echo esc_html( $row['description'] ?? '' ); ?></span><i><?php echo voiceweb_front_icon( 'arrow' ); ?></i>
		</a>
		<?php endforeach; ?>
	</div>
</section>

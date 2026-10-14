<?php
defined( 'ABSPATH' ) || exit;
$rows = (array) voiceweb_front_value( 'faq', array() ); if ( ! $rows ) { return; }
?>
<section class="vw-store-faq" aria-labelledby="home-faq-title"><div><span class="vw-store-eyebrow">GOOD TO KNOW</span><h2 id="home-faq-title"><?php echo esc_html( voiceweb_front_value( 'faq_title', 'پیش از انتخاب' ) ); ?></h2><p>برای انتخابی مطمئن‌تر، از جزئیات شروع کنیم.</p></div><div><?php foreach ( $rows as $row ) : ?><details><summary><?php echo esc_html( $row['question'] ?? '' ); ?><span><?php echo voiceweb_front_icon( 'plus' ); ?></span></summary><div><?php echo wpautop( esc_html( $row['answer'] ?? '' ) ); ?></div></details><?php endforeach; ?></div></section>

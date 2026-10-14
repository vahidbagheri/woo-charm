<?php
/**
 * Theme-owned ACF field groups.
 *
 * This file is the source of truth for the VoiceWeb theme's ACF schema. Updating
 * these arrays updates the live schema on the next request; no database import
 * or migration is required.
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'CN_TERM_ICON_FIELD' ) ) {
	define( 'CN_TERM_ICON_FIELD', 'cn_term_icon' );
}

function cn_register_acf_field_groups() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
	'key' => 'group_home_blog',
	'title' => 'Blog homepage — Sections',
	'fields' => array(
		array(
			'key' => 'field_home_sections',
			'label' => 'Homepage sections',
			'name' => 'home_sections',
			'aria-label' => '',
			'type' => 'flexible_content',
			'instructions' => 'Choose a layout, set its content, and drag sections to reorder them. Post cards are populated automatically from the selected categories.',
			'required' => 0,
			'conditional_logic' => false,
			'wrapper' => array(
				'width' => '',
				'class' => 'cn-blog-sections',
				'id' => '',
			),
			'button_label' => 'Add section',
			'min' => '',
			'max' => '',
			'layouts' => array(
				'layout_featured' => array(
					'key' => 'layout_featured',
					'name' => 'featured',
					'label' => '1) Image-overlay post cards (two-row grid)',
					'display' => 'block',
					'sub_fields' => array(
						array(
							'key' => 'field_feat_category',
							'label' => 'Category',
							'name' => 'category',
							'aria-label' => '',
							'type' => 'taxonomy',
							'instructions' => 'Empty = latest posts from all categories.',
							'required' => false,
							'conditional_logic' => false,
							'wrapper' => array(
								'width' => '75',
								'class' => '',
								'id' => '',
							),
							'taxonomy' => 'category',
							'field_type' => 'select',
							'add_term' => 0,
							'save_terms' => 0,
							'load_terms' => 0,
							'return_format' => 'id',
							'multiple' => 0,
							'allow_null' => 1,
							'bidirectional_target' => array(
							),
						),
						array(
							'key' => 'field_feat_count',
							'label' => 'Post count',
							'name' => 'count',
							'aria-label' => '',
							'type' => 'number',
							'instructions' => '',
							'required' => false,
							'conditional_logic' => false,
							'wrapper' => array(
								'width' => '25',
								'class' => '',
								'id' => '',
							),
							'default_value' => 5,
							'min' => 3,
							'max' => 8,
							'step' => '',
							'placeholder' => '',
							'prepend' => '',
							'append' => '',
						),
					),
					'min' => '',
					'max' => '',
				),
				'layout_guide' => array(
					'key' => 'layout_guide',
					'name' => 'guide',
					'label' => '2) Vertical post cards (four-column grid)',
					'display' => 'block',
					'sub_fields' => array(
						array(
							'key' => 'field_guide_category',
							'label' => 'Category',
							'name' => 'category',
							'aria-label' => '',
							'type' => 'taxonomy',
							'instructions' => '',
							'required' => 1,
							'conditional_logic' => false,
							'wrapper' => array(
								'width' => '50',
								'class' => '',
								'id' => '',
							),
							'taxonomy' => 'category',
							'field_type' => 'select',
							'add_term' => 0,
							'save_terms' => 0,
							'load_terms' => 0,
							'return_format' => 'id',
							'multiple' => 0,
							'allow_null' => 0,
							'bidirectional_target' => array(
							),
						),
						array(
							'key' => 'field_guide_count',
							'label' => 'Post count',
							'name' => 'count',
							'aria-label' => '',
							'type' => 'number',
							'instructions' => '',
							'required' => false,
							'conditional_logic' => false,
							'wrapper' => array(
								'width' => '50',
								'class' => '',
								'id' => '',
							),
							'default_value' => 4,
							'min' => 2,
							'max' => 12,
							'step' => '',
							'placeholder' => '',
							'prepend' => '',
							'append' => '',
						),
						array(
							'key' => 'field_guide_heading',
							'label' => 'Heading (optional)',
							'name' => 'heading',
							'aria-label' => '',
							'type' => 'text',
							'instructions' => 'Empty = category name.',
							'required' => false,
							'conditional_logic' => false,
							'wrapper' => array(
								'width' => '50',
								'class' => '',
								'id' => '',
							),
							'default_value' => '',
							'maxlength' => '',
							'placeholder' => '',
							'prepend' => '',
							'append' => '',
						),
						array(
							'key' => 'field_guide_more',
							'label' => '“View more” link (optional)',
							'name' => 'more_link',
							'aria-label' => '',
							'type' => 'link',
							'instructions' => 'Empty = the category archive page.',
							'required' => false,
							'conditional_logic' => false,
							'wrapper' => array(
								'width' => '50',
								'class' => '',
								'id' => '',
							),
							'return_format' => 'array',
						),
					),
					'min' => '',
					'max' => '',
				),
				'layout_hero' => array(
					'key' => 'layout_hero',
					'name' => 'hero',
					'label' => '3) Full-width image banner',
					'display' => 'block',
					'sub_fields' => array(
						array(
							'key' => 'field_hero_image',
							'label' => 'Banner image',
							'name' => 'image',
							'aria-label' => '',
							'type' => 'image',
							'instructions' => '',
							'required' => 1,
							'conditional_logic' => false,
							'wrapper' => array(
								'width' => '50',
								'class' => '',
								'id' => '',
							),
							'return_format' => 'id',
							'preview_size' => 'large',
							'library' => 'all',
							'min_width' => 0,
							'min_height' => 0,
							'min_size' => 0,
							'max_width' => 0,
							'max_height' => 0,
							'max_size' => 0,
							'mime_types' => '',
						),
						array(
							'key' => 'field_hero_link',
							'label' => 'Link (optional)',
							'name' => 'link',
							'aria-label' => '',
							'type' => 'link',
							'instructions' => '',
							'required' => false,
							'conditional_logic' => false,
							'wrapper' => array(
								'width' => '50',
								'class' => '',
								'id' => '',
							),
							'return_format' => 'array',
						),
					),
					'min' => '',
					'max' => '',
				),
				'layout_categories' => array(
					'key' => 'layout_categories',
					'name' => 'categories',
					'label' => '4) Category cards (horizontal slider)',
					'display' => 'block',
					'sub_fields' => array(
						array(
							'key' => 'field_cats_heading',
							'label' => 'Heading (optional)',
							'name' => 'heading',
							'aria-label' => '',
							'type' => 'text',
							'instructions' => '',
							'required' => false,
							'conditional_logic' => false,
							'wrapper' => array(
								'width' => '50',
								'class' => '',
								'id' => '',
							),
							'default_value' => 'Categories',
							'maxlength' => '',
							'placeholder' => '',
							'prepend' => '',
							'append' => '',
						),
						array(
							'key' => 'field_cats_terms',
							'label' => 'Categories',
							'name' => 'categories',
							'aria-label' => '',
							'type' => 'taxonomy',
							'instructions' => 'Select the categories to display. Each card uses the latest post in that category for its image.',
							'required' => 1,
							'conditional_logic' => false,
							'wrapper' => array(
								'width' => '50',
								'class' => '',
								'id' => '',
							),
							'taxonomy' => 'category',
							'field_type' => 'multi_select',
							'add_term' => 0,
							'save_terms' => 0,
							'load_terms' => 0,
							'return_format' => 'id',
							'multiple' => 1,
							'allow_null' => 0,
							'bidirectional_target' => array(
							),
						),
						array(
							'key' => 'field_cats_more',
							'label' => '“View more” link (optional)',
							'name' => 'more_link',
							'aria-label' => '',
							'type' => 'link',
							'instructions' => '',
							'required' => false,
							'conditional_logic' => false,
							'wrapper' => array(
								'width' => '100',
								'class' => '',
								'id' => '',
							),
							'return_format' => 'array',
						),
					),
					'min' => '',
					'max' => '',
				),
				'layout_travelogue' => array(
					'key' => 'layout_travelogue',
					'name' => 'travelogue',
					'label' => '5) Horizontal post cards (two-column grid)',
					'display' => 'block',
					'sub_fields' => array(
						array(
							'key' => 'field_trv_category',
							'label' => 'Category',
							'name' => 'category',
							'aria-label' => '',
							'type' => 'taxonomy',
							'instructions' => '',
							'required' => 1,
							'conditional_logic' => false,
							'wrapper' => array(
								'width' => '50',
								'class' => '',
								'id' => '',
							),
							'taxonomy' => 'category',
							'field_type' => 'select',
							'add_term' => 0,
							'save_terms' => 0,
							'load_terms' => 0,
							'return_format' => 'id',
							'multiple' => 0,
							'allow_null' => 0,
							'bidirectional_target' => array(
							),
						),
						array(
							'key' => 'field_trv_count',
							'label' => 'Post count',
							'name' => 'count',
							'aria-label' => '',
							'type' => 'number',
							'instructions' => '',
							'required' => false,
							'conditional_logic' => false,
							'wrapper' => array(
								'width' => '50',
								'class' => '',
								'id' => '',
							),
							'default_value' => 4,
							'min' => 2,
							'max' => 12,
							'step' => '',
							'placeholder' => '',
							'prepend' => '',
							'append' => '',
						),
						array(
							'key' => 'field_trv_heading',
							'label' => 'Heading (optional)',
							'name' => 'heading',
							'aria-label' => '',
							'type' => 'text',
							'instructions' => 'Empty = category name.',
							'required' => false,
							'conditional_logic' => false,
							'wrapper' => array(
								'width' => '50',
								'class' => '',
								'id' => '',
							),
							'default_value' => '',
							'maxlength' => '',
							'placeholder' => '',
							'prepend' => '',
							'append' => '',
						),
						array(
							'key' => 'field_trv_more',
							'label' => '“View more” link (optional)',
							'name' => 'more_link',
							'aria-label' => '',
							'type' => 'link',
							'instructions' => 'Empty = the category archive page.',
							'required' => false,
							'conditional_logic' => false,
							'wrapper' => array(
								'width' => '50',
								'class' => '',
								'id' => '',
							),
							'return_format' => 'array',
						),
					),
					'min' => '',
					'max' => '',
				),
				'layout_news' => array(
					'key' => 'layout_news',
					'name' => 'news',
					'label' => '6) Post cards with a latest-posts sidebar',
					'display' => 'block',
					'sub_fields' => array(
						array(
							'key' => 'field_news_category',
							'label' => 'Main posts category',
							'name' => 'news_category',
							'aria-label' => '',
							'type' => 'taxonomy',
							'instructions' => '',
							'required' => false,
							'conditional_logic' => false,
							'wrapper' => array(
								'width' => '50',
								'class' => '',
								'id' => '',
							),
							'taxonomy' => 'category',
							'field_type' => 'select',
							'add_term' => 0,
							'save_terms' => 0,
							'load_terms' => 0,
							'return_format' => 'id',
							'multiple' => 0,
							'allow_null' => 1,
							'bidirectional_target' => array(
							),
						),
						array(
							'key' => 'field_news_count',
							'label' => 'Main posts count',
							'name' => 'news_count',
							'aria-label' => '',
							'type' => 'number',
							'instructions' => '',
							'required' => false,
							'conditional_logic' => false,
							'wrapper' => array(
								'width' => '50',
								'class' => '',
								'id' => '',
							),
							'default_value' => 4,
							'min' => 2,
							'max' => 12,
							'step' => '',
							'placeholder' => '',
							'prepend' => '',
							'append' => '',
						),
						array(
							'key' => 'field_news_heading',
							'label' => 'Main posts heading (optional)',
							'name' => 'news_heading',
							'aria-label' => '',
							'type' => 'text',
							'instructions' => 'Empty = category name or the default section heading.',
							'required' => false,
							'conditional_logic' => false,
							'wrapper' => array(
								'width' => '50',
								'class' => '',
								'id' => '',
							),
							'default_value' => '',
							'maxlength' => '',
							'placeholder' => '',
							'prepend' => '',
							'append' => '',
						),
						array(
							'key' => 'field_news_more',
							'label' => 'Main posts “View more” link (optional)',
							'name' => 'news_more_link',
							'aria-label' => '',
							'type' => 'link',
							'instructions' => '',
							'required' => false,
							'conditional_logic' => false,
							'wrapper' => array(
								'width' => '50',
								'class' => '',
								'id' => '',
							),
							'return_format' => 'array',
						),
						array(
							'key' => 'field_latest_category',
							'label' => 'Latest posts category (optional)',
							'name' => 'latest_category',
							'aria-label' => '',
							'type' => 'taxonomy',
							'instructions' => 'Empty = latest posts from all categories.',
							'required' => false,
							'conditional_logic' => false,
							'wrapper' => array(
								'width' => '50',
								'class' => '',
								'id' => '',
							),
							'taxonomy' => 'category',
							'field_type' => 'select',
							'add_term' => 0,
							'save_terms' => 0,
							'load_terms' => 0,
							'return_format' => 'id',
							'multiple' => 0,
							'allow_null' => 1,
							'bidirectional_target' => array(
							),
						),
						array(
							'key' => 'field_latest_count',
							'label' => 'Latest posts count',
							'name' => 'latest_count',
							'aria-label' => '',
							'type' => 'number',
							'instructions' => '',
							'required' => false,
							'conditional_logic' => false,
							'wrapper' => array(
								'width' => '25',
								'class' => '',
								'id' => '',
							),
							'default_value' => 7,
							'min' => 2,
							'max' => 12,
							'step' => '',
							'placeholder' => '',
							'prepend' => '',
							'append' => '',
						),
						array(
							'key' => 'field_latest_heading',
							'label' => 'Latest posts heading (optional)',
							'name' => 'latest_heading',
							'aria-label' => '',
							'type' => 'text',
							'instructions' => '',
							'required' => false,
							'conditional_logic' => false,
							'wrapper' => array(
								'width' => '25',
								'class' => '',
								'id' => '',
							),
							'default_value' => 'Latest posts',
							'maxlength' => '',
							'placeholder' => '',
							'prepend' => '',
							'append' => '',
						),
					),
					'min' => '',
					'max' => '',
				),
			),
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'page_type',
				'operator' => '==',
				'value' => 'posts_page',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => array(
		0 => 'the_content',
	),
	'active' => true,
	'description' => 'Reorderable homepage sections; each section displays posts from one blog category.',
	'show_in_rest' => 0,
	) );

	acf_add_local_field_group( array(
	'key' => 'group_cn_site_header_footer',
	'title' => 'Site settings — Header and footer',
	'fields' => array(
		array(
			'key' => 'field_cn_header_tab',
			'label' => 'Header',
			'name' => '',
			'aria-label' => '',
			'type' => 'tab',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => false,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'placement' => 'top',
			'endpoint' => 0,
			'selected' => 0,
		),
		array(
			'key' => 'field_cn_header_logo',
			'label' => 'Main site logo',
			'name' => 'cn_header_logo',
			'aria-label' => '',
			'type' => 'image',
			'instructions' => 'Displayed in the desktop and mobile header.',
			'required' => 0,
			'conditional_logic' => false,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'id',
			'library' => 'all',
			'preview_size' => 'medium',
			'min_width' => 0,
			'min_height' => 0,
			'min_size' => 0,
			'max_width' => 0,
			'max_height' => 0,
			'max_size' => 0,
			'mime_types' => '',
		),
		array(
			'key' => 'field_6a897869fde06',
			'label' => 'Desktop menu',
			'name' => 'cn_header_menu',
			'aria-label' => '',
			'type' => 'select',
			'instructions' => 'Choose the main desktop menu. Its WPML translation is used automatically when available.',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'choices' => array(
				2 => 'منوی اصلی',
				68 => 'منوی فوتر',
			),
			'default_value' => false,
			'return_format' => 'value',
			'multiple' => 0,
			'allow_null' => 1,
			'allow_in_bindings' => 1,
			'ui' => 1,
			'ajax' => 0,
			'placeholder' => 'یک فهرست انتخاب کنید',
		),
		array(
			'key' => 'field_cn_header_mobile_menu',
			'label' => 'Mobile menu',
			'name' => 'cn_header_mobile_menu',
			'aria-label' => '',
			'type' => 'select',
			'instructions' => 'Optional. The desktop menu is used when this field is empty.',
			'required' => 0,
			'conditional_logic' => false,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'choices' => array(
				2 => 'منوی اصلی',
				68 => 'منوی فوتر',
			),
			'default_value' => false,
			'return_format' => 'value',
			'multiple' => 0,
			'allow_null' => 1,
			'ui' => 1,
			'ajax' => 0,
			'placeholder' => 'یک فهرست انتخاب کنید',
		),
		array(
			'key' => 'field_cn_header_phone',
			'label' => 'Header phone number',
			'name' => 'cn_header_phone',
			'aria-label' => '',
			'type' => 'text',
			'instructions' => 'The number shown to visitors.',
			'required' => 0,
			'conditional_logic' => false,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '021-43997',
			'placeholder' => '021-43997',
			'maxlength' => '',
			'prepend' => '',
			'append' => '',
		),
		array(
			'key' => 'field_cn_header_phone_link',
			'label' => 'Header phone link',
			'name' => 'cn_header_phone_link',
			'aria-label' => '',
			'type' => 'text',
			'instructions' => 'Use the international format for the tel link, for example +982143997.',
			'required' => 0,
			'conditional_logic' => false,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '+982143997',
			'placeholder' => '+982143997',
			'maxlength' => '',
			'prepend' => '',
			'append' => '',
		),
		array(
			'key' => 'field_cn_footer_tab',
			'label' => 'Footer',
			'name' => '',
			'aria-label' => '',
			'type' => 'tab',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => false,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'placement' => 'top',
			'endpoint' => 0,
			'selected' => 0,
		),
		array(
			'key' => 'field_cn_footer_logo',
			'label' => 'Footer logo',
			'name' => 'cn_footer_logo',
			'aria-label' => '',
			'type' => 'image',
			'instructions' => 'Optional. The main site logo is used when this field is empty.',
			'required' => 0,
			'conditional_logic' => false,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'id',
			'library' => 'all',
			'preview_size' => 'medium',
			'min_width' => 0,
			'min_height' => 0,
			'min_size' => 0,
			'max_width' => 0,
			'max_height' => 0,
			'max_size' => 0,
			'mime_types' => '',
		),
		array(
			'key' => 'field_cn_footer_description',
			'label' => 'Footer text',
			'name' => 'cn_footer_description',
			'aria-label' => '',
			'type' => 'textarea',
			'instructions' => 'A short text displayed below the footer logo.',
			'required' => 0,
			'conditional_logic' => false,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'rows' => 4,
			'new_lines' => '',
			'default_value' => '',
			'maxlength' => '',
			'placeholder' => '',
		),
		array(
			'key' => 'field_cn_footer_phone',
			'label' => 'Footer phone number',
			'name' => 'cn_footer_phone',
			'aria-label' => '',
			'type' => 'text',
			'instructions' => 'The number shown to visitors.',
			'required' => 0,
			'conditional_logic' => false,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '021-43997',
			'placeholder' => '021-43997',
			'maxlength' => '',
			'prepend' => '',
			'append' => '',
		),
		array(
			'key' => 'field_cn_footer_phone_link',
			'label' => 'Footer phone link',
			'name' => 'cn_footer_phone_link',
			'aria-label' => '',
			'type' => 'text',
			'instructions' => 'Use the international format for the tel link, for example +982143997.',
			'required' => 0,
			'conditional_logic' => false,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '+982143997',
			'placeholder' => '+982143997',
			'maxlength' => '',
			'prepend' => '',
			'append' => '',
		),
		array(
			'key' => 'field_cn_footer_email',
			'label' => 'Footer email',
			'name' => 'cn_footer_email',
			'aria-label' => '',
			'type' => 'email',
			'instructions' => 'Leave empty to use the WordPress administration email.',
			'required' => 0,
			'conditional_logic' => false,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
		),
		array(
			'key' => 'field_cn_footer_col1_menu',
			'label' => 'Footer column 1 menu',
			'name' => 'cn_footer_col1_menu',
			'aria-label' => '',
			'type' => 'select',
			'instructions' => 'Choose the main menu for the first footer column. Its WPML translation is used automatically when available.',
			'required' => 0,
			'conditional_logic' => false,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'choices' => array(
				2 => 'منوی اصلی',
				68 => 'منوی فوتر',
			),
			'default_value' => false,
			'return_format' => 'value',
			'multiple' => 0,
			'allow_null' => 1,
			'ui' => 1,
			'ajax' => 0,
			'placeholder' => 'یک فهرست انتخاب کنید',
		),
		array(
			'key' => 'field_cn_footer_col2_menu',
			'label' => 'Footer column 2 menu',
			'name' => 'cn_footer_col2_menu',
			'aria-label' => '',
			'type' => 'select',
			'instructions' => 'Choose the main menu for the second footer column. Its WPML translation is used automatically when available.',
			'required' => 0,
			'conditional_logic' => false,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'choices' => array(
				2 => 'منوی اصلی',
				68 => 'منوی فوتر',
			),
			'default_value' => false,
			'return_format' => 'value',
			'multiple' => 0,
			'allow_null' => 1,
			'ui' => 1,
			'ajax' => 0,
			'placeholder' => 'یک فهرست انتخاب کنید',
		),
		array(
			'key' => 'field_cn_footer_copyright',
			'label' => 'Footer copyright sentence',
			'name' => 'cn_footer_copyright',
			'aria-label' => '',
			'type' => 'text',
			'instructions' => 'Displayed after the year and site name.',
			'required' => 0,
			'conditional_logic' => false,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'maxlength' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'options_page',
				'operator' => '==',
				'value' => 'cn-site-settings-header-footer',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => true,
	'description' => 'Site-wide header and footer settings stored in the standard ACF options record.',
	'show_in_rest' => 0,
	) );

	if ( function_exists( 'iconly_get_icon_html' ) ) {
		acf_add_local_field_group( array(
			'key'                   => 'group_cn_term_icon',
			'title'                 => __( 'Term icon', 'voiceweb-admin' ),
			'fields'                => array(
				array(
					'key'               => 'field_cn_term_icon',
					'label'             => __( 'Icon', 'voiceweb-admin' ),
					'name'              => CN_TERM_ICON_FIELD,
					'aria-label'        => '',
					'type'              => 'iconly_icon',
					'instructions'      => __( 'Choose the icon displayed for this term.', 'voiceweb-admin' ),
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'allow_null'        => 1,
					'show_search'       => 1,
					'columns'           => 6,
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'taxonomy',
						'operator' => '==',
						'value'    => 'all',
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen'        => '',
			'active'                => true,
			'description'           => __( 'Iconly icon assigned to a WordPress term.', 'voiceweb-admin' ),
			'show_in_rest'          => 0,
		) );
	}
}
add_action( 'acf/include_fields', 'cn_register_acf_field_groups', 5 );

/** Storefront content: ordinary ACF arrays on the selected static front page. */
function voiceweb_register_front_page_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) { return; }
	$field = static fn( $name, $label, $type = 'text', $extra = array() ) => array_merge(
		array( 'key' => 'field_vw_front_' . $name, 'name' => 'vw_front_' . $name, 'label' => $label, 'type' => $type ), $extra
	);
	$sub = static fn( $prefix, $name, $label, $type = 'text', $extra = array() ) => $field( $prefix . '_' . $name, $label, $type, array_merge( array( 'name' => $name ), $extra ) );
	$tab = static fn( $name, $label ) => $field( $name, $label, 'tab' );
	$toggle = static fn( $name, $label ) => $field( $name, $label, 'true_false', array( 'ui' => 1, 'default_value' => 1 ) );
	$image = array( 'return_format' => 'id', 'preview_size' => 'medium', 'library' => 'all' );
	$link = array( 'return_format' => 'array' );
	$tones = array( 'ice' => 'Ivory', 'ink' => 'Forest green', 'rose' => 'Soft sand' );
	$product_fields = array( 'post_type' => array( 'product' ), 'post_status' => array( 'publish' ), 'return_format' => 'id', 'filters' => array( 'search', 'taxonomy' ), 'max' => 12, 'instructions' => 'Leave empty for automatic selection of public products. Drag selected products to reorder.' );
	$fields = array(
		$tab( 'tab_hero', 'Hero slider' ),
		$field( 'header_note', 'Header announcement' ),
		$field( 'slides', 'Slides', 'repeater', array( 'layout' => 'block', 'max' => 6, 'collapsed' => 'field_vw_front_slide_title', 'button_label' => 'Add slide', 'sub_fields' => array(
			$sub( 'slide', 'eyebrow', 'Small heading' ),
			$sub( 'slide', 'title', 'Slide title', 'textarea', array( 'rows' => 2, 'new_lines' => '' ) ),
			$sub( 'slide', 'description', 'Slide description', 'textarea', array( 'rows' => 2, 'new_lines' => '' ) ),
			$sub( 'slide', 'image', 'Desktop image', 'image', $image ),
			$sub( 'slide', 'mobile_image', 'Mobile image', 'image', array_merge( $image, array( 'instructions' => 'Optional; the desktop image is used when empty.' ) ) ),
			$sub( 'slide', 'primary', 'Primary link', 'link', $link ),
			$sub( 'slide', 'secondary', 'Secondary link', 'link', $link ),
			$sub( 'slide', 'tone', 'Color theme', 'select', array( 'choices' => $tones, 'default_value' => 'ice' ) ),
			$sub( 'slide', 'floating_images', 'Floating product images', 'repeater', array( 'max' => 3, 'layout' => 'table', 'sub_fields' => array( $sub( 'slide_float', 'image', 'Product image', 'image', $image ) ) ) ),
		) ) ),
		$field( 'autoplay', 'Automatic slide rotation', 'true_false', array( 'ui' => 1, 'default_value' => 0 ) ),
		$field( 'interval', 'Rotation interval (seconds)', 'number', array( 'min' => 5, 'max' => 20, 'default_value' => 8 ) ),
		$tab( 'tab_offers', 'Special offers' ), $toggle( 'offers_enabled', 'Show special offers' ),
		$field( 'offers_title', 'Special offers heading' ), $field( 'offers_description', 'Special offers description' ),
		$field( 'offers_products', 'Selected offer products', 'relationship', $product_fields ),
		$field( 'offers_deadline', 'Offer deadline', 'date_time_picker', array( 'return_format' => 'Y-m-d H:i:s', 'display_format' => 'Y/m/d H:i', 'instructions' => 'Saving this future deadline schedules the existing sale prices of selected products. The timer uses actual WooCommerce sale dates.' ) ),
		$tab( 'tab_categories', 'Product categories' ), $toggle( 'categories_enabled', 'Show product categories' ),
		$field( 'categories_title', 'Categories heading' ),
		$field( 'categories', 'Category cards', 'repeater', array( 'max' => 12, 'layout' => 'block', 'collapsed' => 'field_vw_front_category_label', 'sub_fields' => array(
			$sub( 'category', 'term', 'Product category', 'taxonomy', array( 'taxonomy' => 'product_cat', 'field_type' => 'select', 'return_format' => 'id', 'save_terms' => 0, 'load_terms' => 0, 'add_term' => 0, 'required' => 1 ) ),
			$sub( 'category', 'label', 'Custom label' ), $sub( 'category', 'description', 'Category description' ), $sub( 'category', 'image', 'Category image', 'image', $image ),
		) ) ),
		$tab( 'tab_story', 'Brand story and about' ),
		$field( 'story_title', 'Story heading' ), $field( 'story_description', 'Story description', 'textarea', array( 'rows' => 4 ) ),
		$field( 'story_image', 'Story image', 'image', $image ), $field( 'story_link', 'Story link', 'link', $link ),
		$field( 'about_title', 'About heading' ), $field( 'about_description', 'About description', 'textarea', array( 'rows' => 4 ) ),
		$field( 'about_image', 'About image', 'image', $image ), $field( 'about_link', 'About link', 'link', $link ),
		$field( 'collection_title', 'New collection heading' ), $field( 'collection_description', 'New collection description', 'textarea', array( 'rows' => 2 ) ),
		$field( 'collection_image', 'New collection image', 'image', $image ), $field( 'collection_link', 'New collection link', 'link', $link ),
		$tab( 'tab_more', 'More sections' ),
		$field( 'promos', 'Featured collections', 'repeater', array( 'max' => 2, 'layout' => 'block', 'sub_fields' => array(
			$sub( 'promo', 'title', 'Collection title' ), $sub( 'promo', 'description', 'Collection description', 'textarea', array( 'rows' => 2 ) ),
			$sub( 'promo', 'image', 'Collection image', 'image', $image ), $sub( 'promo', 'link', 'Collection link', 'link', $link ),
			$sub( 'promo', 'tone', 'Color theme', 'select', array( 'choices' => $tones ) ),
		) ) ),
		$toggle( 'arrivals_enabled', 'Show new arrivals' ), $field( 'arrivals_title', 'New arrivals heading' ),
		$field( 'arrivals_products', 'Selected new products', 'relationship', $product_fields ),
		$toggle( 'blog_enabled', 'Show blog section' ), $field( 'blog_title', 'Blog heading' ), $field( 'blog_description', 'Blog description' ),
		$field( 'blog_link', 'Blog archive link', 'link', array_merge( $link, array( 'instructions' => 'Leave empty for the WordPress posts page.' ) ) ),
		$field( 'blog_posts', 'Selected blog posts', 'relationship', array( 'post_type' => array( 'post' ), 'post_status' => array( 'publish' ), 'return_format' => 'id', 'max' => 3, 'filters' => array( 'search', 'taxonomy' ) ) ),
		$field( 'benefits', 'Brand values', 'repeater', array( 'max' => 4, 'layout' => 'table', 'sub_fields' => array(
			$sub( 'benefit', 'title', 'Value title' ), $sub( 'benefit', 'description', 'Value description' ),
			$sub( 'benefit', 'icon', 'Value icon', 'select', array( 'choices' => array( 'check' => 'Check', 'truck' => 'Delivery', 'shield' => 'Security', 'user' => 'Account' ) ) ),
		) ) ),
		$field( 'faq_title', 'FAQ heading' ), $field( 'faq', 'Frequently asked questions', 'repeater', array( 'max' => 6, 'sub_fields' => array(
			$sub( 'faq', 'question', 'Question' ), $sub( 'faq', 'answer', 'Answer', 'textarea', array( 'rows' => 3 ) ),
		) ) ),
	);
	acf_add_local_field_group( array( 'key' => 'group_voiceweb_front_page', 'title' => 'Storefront homepage', 'fields' => $fields, 'location' => array( array( array( 'param' => 'page_type', 'operator' => '==', 'value' => 'front_page' ) ) ), 'position' => 'normal', 'style' => 'default', 'show_in_rest' => 0 ) );
}
add_action( 'acf/include_fields', 'voiceweb_register_front_page_fields', 5 );
// Run before ACF's default-priority Local JSON loader so the theme-owned keys
// cannot be shadowed by a same-key JSON file.

/**
 * Route ACF field-group interface strings through the VoiceWeb translator.
 *
 * Structure-management screens stay untranslated so saving a field group
 * cannot write translated UI strings into the English schema source.
 *
 * @param string $textdomain Existing ACF localization text domain.
 * @return string
 */
function cn_acf_textdomain( $textdomain ) {
	if ( is_admin() ) {
		$post_type = ( isset( $_REQUEST['post_type'] ) && is_string( $_REQUEST['post_type'] ) )
		? sanitize_key( wp_unslash( $_REQUEST['post_type'] ) )
		: '';
		$page      = ( isset( $_REQUEST['page'] ) && is_string( $_REQUEST['page'] ) )
		? sanitize_key( wp_unslash( $_REQUEST['page'] ) )
		: '';
		$post_id   = ( isset( $_REQUEST['post'] ) && is_scalar( $_REQUEST['post'] ) )
		? absint( $_REQUEST['post'] )
		: 0;

		if (
			$post_type === 'acf-field-group'
			|| $page === 'acf-tools'
			|| ( $post_id && get_post_type( $post_id ) === 'acf-field-group' )
		) {
			return '';
		}
	}

	return 'voiceweb-acf';
}
add_filter( 'acf/settings/l10n_textdomain', 'cn_acf_textdomain' );

/**
 * Translate an ACF interface string with the theme admin catalogs.
 *
 * @param string $translation Translation produced for the virtual domain.
 * @param string $text        Source string.
 * @param string $domain      Requested text domain.
 * @return string
 */
function cn_translate_acf_string( $translation, $text ) {
	return cn_translate_admin( $text );
}
add_filter( 'gettext_voiceweb-acf', 'cn_translate_acf_string', 10, 2 );

/**
 * Translate field-group descriptions omitted by ACF's default localization.
 *
 * @param array $field_group ACF field-group settings.
 * @return array
 */
function cn_translate_acf_description( $field_group ) {
	if ( isset( $field_group['description'] ) && is_string( $field_group['description'] ) ) {
		$field_group['description'] = acf_translate( $field_group['description'] );
	}

	return $field_group;
}
add_filter( 'acf/translate_field_group', 'cn_translate_acf_description' );

/**
 * Load ACF JSON definitions from the parent and active child themes.
 *
 * @param string[] $paths Existing JSON load paths.
 * @return string[]
 */
function cn_acf_json_paths( $paths ) {
	$paths[] = get_template_directory() . '/acf-json';

	if ( is_child_theme() ) {
		$paths[] = get_stylesheet_directory() . '/acf-json';
	}

	return $paths;
}
add_filter( 'acf/settings/load_json', 'cn_acf_json_paths' );

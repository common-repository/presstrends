<?php
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

$count_posts 	= wp_count_posts( 'post' );
$count_pages 	= wp_count_posts( 'pages' );
$comments_count = wp_count_comments();
$plugin_count 	= count( get_option( 'active_plugins' ) );

if(is_plugin_active('woocommerce/woocommerce.php')) {
	// WOO-TASTIC METRICS
	$orders = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'shop_order' AND post_status = 'publish'" );
	$completed_term = $wpdb->get_var( "SELECT term_id FROM $wpdb->terms WHERE name = 'completed'" );
	$order_totals = $wpdb->get_row("
		SELECT AVG(meta.meta_value) AS total_sales, COUNT(posts.ID) AS total_orders FROM {$wpdb->posts} AS posts

		LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
		LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
		LEFT JOIN {$wpdb->terms} AS term USING( term_id )

		WHERE 	meta.meta_key 		= '_order_total'
		AND 	posts.post_date		>= DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)
		AND 	posts.post_type 	= 'shop_order'
		AND 	posts.post_status 	= 'publish'
		AND 	tax.taxonomy		= 'shop_order_status'
		AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed' ) ) ) . "')
	");

	$revenue 					= $order_totals->total_sales;
	$completed_orders 			= $order_totals->total_orders;
	$cart_checkout_conversion 	= @number_format( ( $completed_orders / $orders ) * 100, 0, '.', '' );
	$avg_time_btw_checkout 		= $wpdb->get_var("SELECT TIMESTAMPDIFF(SECOND, MIN(post_date), MAX(post_date)) / (COUNT(*)-1) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'shop_order'");

} else {
	$revenue 					= '0';
	$completed_orders 			= '0';
	$cart_checkout_conversion 	= '0';
	$avg_time_btw_checkout 		= '0';
}

// SPAM PERCENTAGE
$spam_percentage = @number_format( ( $comments_count->spam / $comments_count->total_comments ) * 100, 0, '.', '' );

// APPROVED PERCENTAGE
$approved_percentage = @number_format( ( $comments_count->approved / $comments_count->total_comments ) * 100, 0, '.', '');

// TOTAL REPLY COMMENTS
$replies = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->comments WHERE comment_parent > 0 AND comment_approved = '1'" );
$reply_percentage = @number_format( ( $replies / $comments_count->total_comments ) * 100, 0, '.', '' );

// TOTAL CATEGORIES
$thecats = wp_list_categories( 'title_li=&style=none&echo=0' );
$splitcats = explode( '<br />', $thecats );
$category_sum = count( $splitcats ) - 1;

// TOTAL PINGBACKS
$pingback_result = $wpdb->get_var( "SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_type = 'pingback'" );
$pingback_percentage = @number_format( ( $pingback_result / $count_posts->publish ) * 100, 0, '.', '' );

// TOP COMMENT DAY
$day_result = $wpdb->get_results( "SELECT count(DAYNAME(comment_date)) AS c, DAYNAME(comment_date) AS h FROM $wpdb->comments WHERE comment_approved = '1' GROUP BY h ORDER BY c DESC Limit 1" );

// TOP COMMENT HOUR
$hour_result = $wpdb->get_results( "SELECT count(hour(comment_date)) AS c, hour(comment_date) AS h FROM $wpdb->comments WHERE comment_approved = '1' GROUP BY h ORDER BY c DESC Limit 1" );

// AVERAGE TIME BETWEEN PUBLISHED POSTS
$avg_time_btw_posts = $wpdb->get_var("SELECT TIMESTAMPDIFF(SECOND, MIN(post_date), MAX(post_date)) / (COUNT(*)-1) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post'");

// AVERAGE TIME BETWEEN APPROVED COMMENT
$avg_time_btw_comments = $wpdb->get_var("SELECT TIMESTAMPDIFF(SECOND, MIN(comment_date), MAX(comment_date)) / (COUNT(*)-1) FROM $wpdb->comments WHERE comment_approved = '1'");

// Total Post Conversion
$total_posts_with_comments = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'post' AND comment_count > 0" );
$total_post_conversion = @number_format( ( $total_posts_with_comments / $count_posts->publish ) * 100, 0, '.', '' );

// WEEK
$week_replies = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_parent > 0 AND comment_approved = '1' AND comment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 WEEK)");
$week_comments = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_parent = '0' AND comment_approved = '1' AND comment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 WEEK)");
$week_posts = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post' AND post_date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 WEEK)");
$week_pingbacks = $wpdb->get_var("SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_type = 'pingback' AND comment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 WEEK)");
$week_interactions = $week_replies + $week_comments;
$week_approved_percentage = @number_format( ( ($week_comments + $week_replies) / $comments_count->total_comments ) * 100, 0, '.', '');
$week_posts_with_comments = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'post' AND comment_count > 0 AND post_date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 WEEK)" );
$week_post_conversion = @number_format( ( $week_posts_with_comments / $week_posts ) * 100, 0, '.', '' );
$week_avg_time_btw_posts = $wpdb->get_var("SELECT TIMESTAMPDIFF(SECOND, MIN(post_date), MAX(post_date)) / (COUNT(*)-1) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post' AND post_date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 WEEK)");
$week_avg_time_btw_comments = $wpdb->get_var("SELECT TIMESTAMPDIFF(SECOND, MIN(comment_date), MAX(comment_date)) / (COUNT(*)-1) FROM $wpdb->comments WHERE comment_approved = '1' AND comment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 WEEK)");

// MONTH
$month_replies = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_parent > 0 AND comment_approved = '1' AND comment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)");
$month_comments = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_parent = '0' AND comment_approved = '1' AND comment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)");
$month_posts = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post' AND post_date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)");
$month_pingbacks = $wpdb->get_var("SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_type = 'pingback' AND comment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)");
$month_interactions = $month_replies + $month_comments;
$month_approved_percentage = @number_format( ( ($month_comments + $today_replies) / $comments_count->total_comments ) * 100, 0, '.', '');
$month_posts_with_comments = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'post' AND comment_count > 0 AND post_date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)" );
$month_post_conversion = @number_format( ( $month_posts_with_comments / $month_posts ) * 100, 0, '.', '' );
$month_avg_time_btw_posts = $wpdb->get_var("SELECT TIMESTAMPDIFF(SECOND, MIN(post_date), MAX(post_date)) / (COUNT(*)-1) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post' AND post_date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)");
$month_avg_time_btw_comments = $wpdb->get_var("SELECT TIMESTAMPDIFF(SECOND, MIN(comment_date), MAX(comment_date)) / (COUNT(*)-1) FROM $wpdb->comments WHERE comment_approved = '1' AND comment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH )");

if($week_avg_time_btw_comments == '') {
	$week_avg_time_btw_comments = 0;
}
if($month_avg_time_btw_comments == '') {
	$month_avg_time_btw_comments = 0;
}
if($week_avg_time_btw_posts == '') {
	$week_avg_time_btw_posts = 0;
}
if($month_avg_time_btw_posts == '') {
	$month_avg_time_btw_posts = 0;
}

// PUBLISHED POSTS BY MONTH
$posts_by_month = $wpdb->get_results( "SELECT DISTINCT MONTH( post_date ) AS month,YEAR( post_date ) AS year, COUNT( id ) as post_count FROM $wpdb->posts WHERE post_status = 'publish' and post_date <= now( ) and post_type = 'post' GROUP BY month , year ORDER BY post_date DESC", 'ARRAY_A' );

// APPROVED COMMENTS BY MONTH
$comments_by_month = $wpdb->get_results( "SELECT DISTINCT MONTH( comment_date ) AS month,YEAR( comment_date ) AS year, COUNT( comment_ID ) as comment_count FROM $wpdb->comments WHERE comment_approved = '1' and comment_date <= now( ) GROUP BY month , year ORDER BY comment_date DESC", 'ARRAY_A' );

// Special thanks to @chrisguitarguy for the cleaner SQL on the posts/comments by month

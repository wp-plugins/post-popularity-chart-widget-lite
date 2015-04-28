<?php
/*
Plugin Name: Post Popularity Chart Widget Lite
Plugin URI: http://smartfan.pl/
Description: Widget which displays popularity chart / graph for posts.
Author: Piotr Pesta
Version: 0.9.2
Author URI: http://smartfan.pl/
License: GPL12
*/
include 'functions.php';

$options = get_option('post_popularity_graph');

register_activation_hook(__FILE__, 'post_popularity_graph_activate'); //akcja podczas aktywacji pluginu
register_uninstall_hook(__FILE__, 'post_popularity_graph_uninstall'); //akcja podczas deaktywacji pluginu

// instalacja i zak³adanie tabeli w mysql
function post_popularity_graph_activate() {
	global $wpdb;
	$post_popularity_graph_table = $wpdb->prefix . 'post_popularity_graph';
		$wpdb->query("CREATE TABLE IF NOT EXISTS $post_popularity_graph_table (
		id BIGINT(50) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		post_id BIGINT(50) NOT NULL,
		date DATETIME
		);");
}

// podczas odinstalowania - usuwanie tabeli
function post_popularity_graph_uninstall() {
	global $wpdb;
	$post_popularity_graph_table = $wpdb->prefix . 'post_popularity_graph';
	delete_option('post_popularity_graph');
	$wpdb->query( "DROP TABLE IF EXISTS $post_popularity_graph_table" );
}

class post_popularity_graph extends WP_Widget {

// konstruktor widgetu
function post_popularity_graph() {

	$this->WP_Widget(false, $name = __('Post Popularity Graph Widget', 'wp_widget_plugin'));

}

// tworzenie widgetu, back end (form)
function form($instance) {

// nadawanie i ³¹czenie defaultowych wartoœci
	$defaults = array('ignoredcategories' => '', 'ignoredpages' => '', 'numberofdays' => '10', 'title' => 'Post Popularity Graph');
	$instance = wp_parse_args( (array) $instance, $defaults );
?>

<p>
	<label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
	<input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
</p>

<p>
	<label for="<?php echo $this->get_field_id('numberofdays'); ?>">Include data from how many last days (1-30)?</label>
	<input id="<?php echo $this->get_field_id('numberofdays'); ?>" name="<?php echo $this->get_field_name('numberofdays'); ?>" value="<?php echo $instance['numberofdays']; ?>" style="width:100%;"/>
</p>

<p>
	<label for="<?php echo $this->get_field_id('ignoredpages'); ?>">If you would like to exclude any pages from being displayed, you can enter the Page IDs (comma separated, e.g. 34, 25, 439):</label>
	<input id="<?php echo $this->get_field_id('ignoredpages'); ?>" name="<?php echo $this->get_field_name('ignoredpages'); ?>" value="<?php echo $instance['ignoredpages']; ?>" style="width:100%;"/>
</p>

<p>
	<label for="<?php echo $this->get_field_id('ignoredcategories'); ?>">If you would like to exclude any categories from being displayed, you can enter the Category IDs (comma separated, e.g. 3, 5, 10):</label>
	<input id="<?php echo $this->get_field_id('ignoredcategories'); ?>" name="<?php echo $this->get_field_name('ignoredcategories'); ?>" value="<?php echo $instance['ignoredcategories']; ?>" style="width:100%;" />
</p>

<?php

}

function update($new_instance, $old_instance) {
$instance = $old_instance;

// available fields
$instance['title'] = strip_tags($new_instance['title']);
$instance['numberofdays'] = strip_tags($new_instance['numberofdays']);
$instance['ignoredpages'] = strip_tags($new_instance['ignoredpages']);
$instance['ignoredcategories'] = strip_tags($new_instance['ignoredcategories']);
return $instance;
}

// wyswietlanie widgetu, front end (widget)
function widget($args, $instance) {
extract($args);

// widget variables
$title = apply_filters('widget_title', $instance['title']);
$numberofdays = $instance['numberofdays'];
$numberofdays = trim(preg_replace('/\s+/', '', $numberofdays));
$numberofdays = $numberofdays - 1;
$ignoredpages = $instance['ignoredpages'];
$ignoredpages = trim(preg_replace('/\s+/', '', $ignoredpages));
$ignoredpages = explode(",",$ignoredpages);
$ignoredcategories = $instance['ignoredcategories'];
$ignoredcategories = trim(preg_replace('/\s+/', '', $ignoredcategories));
$ignoredcategories = explode(",",$ignoredcategories);
$postID = get_the_ID();
$catID = get_the_category($postID);
$postCatID = $catID[0]->cat_ID;
echo $before_widget;
	
	// check is category ID or post ID is banned or not
	if(in_array($postCatID, $ignoredcategories) || in_array($postID, $ignoredpages)) {
		add_hits($postID);
	}else{
		// check title availability
		if($title) {
		echo $before_title . $title . $after_title;
		}

		show_graph($postID, $numberofdays);

		add_hits($postID);

		echo $after_widget;
	}
}
}

// rejestracja widgetu
add_action('widgets_init', create_function('', 'return register_widget("post_popularity_graph");'));

?>
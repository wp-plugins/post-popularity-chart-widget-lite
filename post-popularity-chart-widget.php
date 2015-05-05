<?php
/*
Plugin Name: Post Popularity Chart Widget
Plugin URI: http://smartfan.pl/
Description: Widget which displays popularity chart / graph for posts.
Author: Piotr Pesta
Version: 1.0.0
Author URI: http://smartfan.pl/
License: GPL12
*/
include 'functions.php';

$options = get_option('post_popularity_graph');

register_activation_hook(__FILE__, 'post_popularity_graph_activate'); //akcja podczas aktywacji pluginu
register_uninstall_hook(__FILE__, 'post_popularity_graph_uninstall'); //akcja podczas deaktywacji pluginu

// Installation and SQL table creation
function post_popularity_graph_activate() {
	global $wpdb;
	$post_popularity_graph_table = $wpdb->prefix . 'post_popularity_graph';
		$wpdb->query("CREATE TABLE IF NOT EXISTS $post_popularity_graph_table (
		id BIGINT(50) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		post_id BIGINT(50) NOT NULL,
		date DATETIME
		);");
}

// If uninstall - remove SQL table
function post_popularity_graph_uninstall() {
	global $wpdb;
	$post_popularity_graph_table = $wpdb->prefix . 'post_popularity_graph';
	delete_option('post_popularity_graph');
	$wpdb->query( "DROP TABLE IF EXISTS $post_popularity_graph_table" );
}

class post_popularity_graph extends WP_Widget {

// Widget constructor
function post_popularity_graph() {

	$this->WP_Widget(false, $name = __('Post Popularity Graph Widget', 'wp_widget_plugin'));

}

// Widget backend
function form($instance) {

// Default values
	$defaults = array('chartcolor' => '#0033CC', 'backgroundcolor' => '#FFFFFF', 'vaxistitle' => 'Visits', 'haxistitle' => 'Time', 'chartstyle' => 'LineChart', 'ignoredcategories' => '', 'ignoredpages' => '', 'numberofdays' => '10', 'title' => 'Post Popularity Graph');
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

<p>
<label for="<?php echo $this->get_field_id('chartstyle'); ?>">Include posts that where visited in how many last days?</label>
<select id="<?php echo $this->get_field_id('chartstyle'); ?>" name="<?php echo $this->get_field_name('chartstyle'); ?>" value="<?php echo $instance['chartstyle']; ?>" style="width:100%;">
	<option value="LineChart" <?php if ($instance['chartstyle']=='LineChart') {echo "selected";} ?>>Line Chart</option>
	<option value="ColumnChart" <?php if ($instance['chartstyle']=='ColumnChart') {echo "selected";} ?>>Column Chart</option>
	<option value="AreaChart" <?php if ($instance['chartstyle']=='AreaChart') {echo "selected";} ?>>Area Chart</option>
</select>
</p>

<p>
	<label for="<?php echo $this->get_field_id('haxistitle'); ?>">Horizontal Axis Title:</label>
	<input id="<?php echo $this->get_field_id('haxistitle'); ?>" name="<?php echo $this->get_field_name('haxistitle'); ?>" value="<?php echo $instance['haxistitle']; ?>" style="width:100%;" />
</p>

<p>
	<label for="<?php echo $this->get_field_id('vaxistitle'); ?>">Vertical Axis Title:</label>
	<input id="<?php echo $this->get_field_id('vaxistitle'); ?>" name="<?php echo $this->get_field_name('vaxistitle'); ?>" value="<?php echo $instance['vaxistitle']; ?>" style="width:100%;" />
</p>

<p>
	<label for="<?php echo $this->get_field_id('backgroundcolor'); ?>">Background Color (it must be a html hex color code e.g. #000000, you can find color picker <a href="http://www.w3schools.com/tags/ref_colorpicker.asp" target="_blank">HERE</a>):</label>
	<input id="<?php echo $this->get_field_id('backgroundcolor'); ?>" name="<?php echo $this->get_field_name('backgroundcolor'); ?>" value="<?php
		//check hex value
		if (preg_match('/^\#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $instance['backgroundcolor'])) {
			echo $instance['backgroundcolor'];
		}else {
			echo "Error: it must be a html hex color code e.g. #000000";
		}
	?>" style="width:100%;" />
</p>

<p>
	<label for="<?php echo $this->get_field_id('chartcolor'); ?>">Chart Color (it must be a html hex color code e.g. #000000, you can find color picker <a href="http://www.w3schools.com/tags/ref_colorpicker.asp" target="_blank">HERE</a>):</label>
	<input id="<?php echo $this->get_field_id('chartcolor'); ?>" name="<?php echo $this->get_field_name('chartcolor'); ?>" value="<?php
		//check hex value
		if (preg_match('/^\#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $instance['chartcolor'])) {
			echo $instance['chartcolor'];
		}else {
			echo "Error: it must be a html hex color code e.g. #000000";
		}
	?>" style="width:100%;" />
</p>

<?php

}

function update($new_instance, $old_instance) {
$instance = $old_instance;

// Available fields
$instance['title'] = strip_tags($new_instance['title']);
$instance['numberofdays'] = strip_tags($new_instance['numberofdays']);
$instance['ignoredpages'] = strip_tags($new_instance['ignoredpages']);
$instance['ignoredcategories'] = strip_tags($new_instance['ignoredcategories']);
$instance['chartstyle'] = strip_tags($new_instance['chartstyle']);
$instance['haxistitle'] = strip_tags($new_instance['haxistitle']);
$instance['vaxistitle'] = strip_tags($new_instance['vaxistitle']);
$instance['backgroundcolor'] = strip_tags($new_instance['backgroundcolor']);
$instance['chartcolor'] = strip_tags($new_instance['chartcolor']);
return $instance;
}

// Widget front end
function widget($args, $instance) {
extract($args);

// Widget variables
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
$chartstyle = $instance['chartstyle'];
$haxistitle = $instance['haxistitle'];
$vaxistitle = $instance['vaxistitle'];
$backgroundcolor = $instance['backgroundcolor'];
$chartcolor = $instance['chartcolor'];
$postID = get_the_ID();
$catID = get_the_category($postID);
$postCatID = $catID[0]->cat_ID;
echo $before_widget;
	
	// Checking category ID or post ID is banned or not
	if(in_array($postCatID, $ignoredcategories) || in_array($postID, $ignoredpages)) {
		add_hits($postID);
	}else{
		// Check title availability
		if($title) {
		echo $before_title . $title . $after_title;
		}

		show_graph($postID, $numberofdays, $chartstyle, $haxistitle, $vaxistitle, $backgroundcolor, $chartcolor);

		add_hits($postID);

		echo $after_widget;
	}
}
}

// Widget registration
add_action('widgets_init', create_function('', 'return register_widget("post_popularity_graph");'));

?>
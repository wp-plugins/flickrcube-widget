<?php
/*
Plugin Name: FlickrCube Widget
Plugin URI: http://www.pjvolders.be
Description: This widget displays photos from flickr as a 3D spinning cube. You can show the latest public photos of a user, his/hers public favorites or a group pool. The animation is based on the jQuery Image Cube plugin by Keith Wood.
Version: 1.0
Author: PJ Volders
Author URI: http://www.pjvolders.be
License: GPL2
*/

/*  Copyright 2010  Pieter-Jan Volders  (email : support@pjvolders.be)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/**
 * FlickrCubeWidget Class
 */
class FlickrCubeWidget extends WP_Widget {
	/** constructor */
	function FlickrCubeWidget() {
		parent::WP_Widget(false, $name = 'FlickrCubeWidget');	
	}

	/** @see WP_Widget::widget */
	function widget($args, $instance) {	
		$path_to_plugin = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));	
		extract( $args );
		$title 	= apply_filters('widget_title', $instance['title']);
		$type 	= $instance['type'];
		$id 	= $instance['id'];
		?>
			<?php echo $before_widget; ?>
			<?php if ( $title )
				echo $before_title . $title . $after_title; ?>
				
			<div id="<? echo $this->id; ?>_box" style="display: block; width: 75px; height: 75px; padding: 0; margin: 7px auto 0 auto; ">
				<? echo $this->getFlickrPhotos($type, $id); ?>
			</div>
			<div id="<? echo $this->id; ?>_textbox" style="display: block; width: 100%; overflow: hidden; padding: 0; margin: 5px 0 0 0; position: relative; text-align: center;">FlickrCube Widget by PJVolders</div>
			<script type="text/javascript">
				function startingRotate(current, next) {
					jQuery('#<? echo $this->id; ?>_textbox').animate({
						opacity: 0
					}, 1000, function() {
						jQuery(this).text( jQuery(next).attr('alt') );
						jQuery(this).animate({
							opacity: 1
						}, 1000 );
					});
				    //jQuery('#flickr_widget_textbox').text( jQuery(current).attr('alt') ); 
				}
				
				jQuery('#<? echo $this->id; ?>_box').imagecube( {beforeRotate: startingRotate, imagePath: '<? echo $path_to_plugin; ?>'} ); 
			</script>
			<?php echo $after_widget; ?>
		<?php
	}

	function getFlickrPhotos($type, $id, $nr=20) {
		$url 	= array(
			"photos" => "http://api.flickr.com/services/feeds/photos_public.gne?id=$id", 
			"favs" => "http://api.flickr.com/services/feeds/photos_faves.gne?id=$id", 
			"group" => "http://api.flickr.com/services/feeds/groups_pool.gne?id=$id" );
		$rss =  simplexml_load_file($url[$type]);
		
		$img_html = "";
		foreach ($rss->entry as $entry) {
			//$small = $entry->link[1]->attributes()->href;
			//$sq = substr($small, 0, -5).'s.jpg';
			$content = $entry->content;
			$regex = '/http:\/\/farm[\w\.\/]*_m.jpg/';
			preg_match($regex, $content, $matches);
			//print_r($matches);
			$small = $matches[0];
			$sq = substr($small, 0, -5).'s.jpg';
			
			$alt = $entry->title;
			$img_html.= "<img src='{$sq}' alt='{$alt}'/>";
		} 
		return $img_html;
	}

	/** @see WP_Widget::update */
	function update($new_instance, $old_instance) {				
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['id'] = strip_tags($new_instance['id']);
		
		$types = array("photos", "favs", "group");
		if ( in_array($new_instance['type'], $types) ) {
			$instance['type'] = $new_instance['type'];
		} else {
			$instance['type'] = 'photos';
		}
		return $instance;
	}

	/** @see WP_Widget::form */
	function form($instance) {				
		$title	= esc_attr($instance['title']);
		$type 	= esc_attr($instance['type']);
		$id 	= esc_attr($instance['id']);
		
		$type_select = array();
		$type_select[$type] = 'selected="selected"';
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
		
		<p><label for="<?php echo $this->get_field_id('type'); ?>"><?php _e('Type:'); ?> 
		<select class="widefat" id="<?php echo $this->get_field_id('type'); ?>" name="<?php echo $this->get_field_name('type'); ?>">
			<option value="photos" <?echo $type_select['photos'];?>>Public photos</option>
		 	<option value="favs" <?echo $type_select['favs'];?>>Public favorites</option>
			<option value="group" <?echo $type_select['group'];?>>Group pool</option>
		</select>
		</label></p>

		<p><label for="<?php echo $this->get_field_id('id'); ?>"><?php _e('ID:'); ?> <b><a href="http://idgettr.com" target="_blanc" style="cursor:help;">?</a></b> <input class="widefat" id="<?php echo $this->get_field_id('id'); ?>" name="<?php echo $this->get_field_name('id'); ?>" type="text" value="<?php echo $id; ?>" /></label></p>
		<?php 
	}

} // class FlickrCubeWidget

// register FlickrCubeWidget widget
add_action('widgets_init', create_function('', 'return register_widget("FlickrCubeWidget");'));


/**
 * Add the required javascript files
 *
 * @author PJ Volders
 */
function flickrcubewidget_add_js() {
	$path_to_plugin = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
	wp_register_script( 'jquery.imagecube', $path_to_plugin.'jquery.imagecube.min.js' );
	
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery.imagecube' );
	//wp_enqueue_script( 'thickbox' );
}
// register the flickrcubewidget_add_js function
add_filter( 'get_header', 'flickrcubewidget_add_js', 9 );


?>
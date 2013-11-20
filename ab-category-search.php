<?php
/*
Plugin Name: AB Categories Search Widget
Plugin URI: 
Description: Provides a Search Widget with the ability to add category selection filters.
Author: Agustin Berasategui
Version: 0.1
Author URI: ajberasategui.com.ar
*/
/*
Copyright (C) 2013 Agustin Berasategui

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

class ABCategorySearch  extends WP_Widget {

	function __construct() {
		parent::__construct(
			'ab_cat_search',
			__( 'AB Search Categories', 'absc' ),
			array(
				'description' => __( 'Adds a Search Form with category selectors', 'absc' )
			)
		);
	}

	function widget( $args, $instance ) {
		extract( $args );
		echo $before_widget;
		$title = apply_filters('widget_title', $instance['title'] );
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}
		$incoming_search_cats = ( isset( $_GET['search_cat'] ) ) ? (array) $_GET['search_cat'] : array( 'empty' );
		?>
		
		<!--
		<pre>
			<?php print_r( $instance['categories'] ); ?>
		</pre>-->
		<div class="ab-cat-search-container">
			<form role="search" method="get" class="search-form" action="<?php echo home_url( '/' ); ?>">
				<label>
					<span class="screen-reader-text"><?php _e( 'Search', 'absc' ); ?>:</span>
					<input type="search" class="search-field" placeholder="<?php _e( 'Search', 'absc' ); ?>..." 
					value="" name="s" title="<?php _e( 'Search', 'absc' ); ?>" />
				</label><br/>
				<?php foreach( $instance['categories'] as $name => $cat_id ) : ?>
					<label for="<?php echo $this->id; ?>_cat_sel"><?php echo $name; ?></label>
					<select name="search_cat[]" id="<?php echo $this->id; ?>_cat_sel">
						<option value="<?php echo $cat_id; ?>" 
							<?php selected( true, in_array($cat_id, $incoming_search_cats ) ); ?>>
							<?php _e( 'All', 'absc' ); ?>
						</option>
						<?php
						$sub_cats = get_categories( array(
							'type'			=> 'post',
							'parent'		=> $cat_id, 
							'orderby'		=> 'name',
							'order'			=> 'ASC',
							'hide_empty'	=> 0,
							'taxonomy'		=> 'category' 
						) );
						foreach( $sub_cats as $sc ) : ?>
						<option value="<?php echo $sc->cat_ID; ?>" 
							<?php selected( true, in_array($sc->cat_ID, $incoming_search_cats ) ); ?>>
							<?php echo $sc->name; ?>
						</option>					 
						<?php endforeach; ?>			
				</select><br/>
				<?php endforeach; ?>
				<input type="submit" class="search-submit" value="<?php _e( 'Search', 'absc' ); ?>" />
			</form>	
		</div>
		<?php
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		foreach ( array('title') as $val ) {
			$instance[$val] = strip_tags( $new_instance[$val] );
		}
		$cats = array();
		foreach( $new_instance['categories'] as $cat ) {
			$cat_split = split( '_', $cat );
			$cats[$cat_split[0]] = $cat_split[1];
		}
		$instance['categories'] = $cats;
		return $instance;
	}

	function form( $instance ) {
		$defaults = array( 
			'title' 		=> 'title',
			'categories'	=> array() 
		);
		$instance = wp_parse_args( (array) $instance, $defaults ); 
		$cat_args = array(
			'type'                     => 'post',
			'child_of'                 => 0,
			'parent'                   => 0,
			'orderby'                  => 'name',
			'order'                    => 'ASC',
			'hide_empty'               => 0,
			'hierarchical'             => 0,
			'exclude'                  => '',
			'include'                  => '',
			'number'                   => '',
			'taxonomy'                 => 'category',
			'pad_counts'               => false 
		);
		$categories = get_categories( $cat_args );
		$selected_cats = ( '' != $instance['categories'] ) ? $instance['categories'] : array();
		/*
		echo '<pre>';
		//print_r( $categories );
		print_r( $instance['categories'] );
		echo '</pre>';
		*/
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( "Title", 'absc' ); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" 
				name="<?php echo $this->get_field_name( 'title' ); ?>" 
				value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'categories' ); ?>"><?php _e( "Categories", 'absc' ); ?>:</label><br/>
			<select multiple="multiple" name="<?php echo $this->get_field_name( 'categories' ); ?>[]" 
				id="<?php echo $this->get_field_id( 'categories' ); ?>" size="4">
				<?php foreach( $categories as $c ) : ?>
				<option value="<?php echo $c->name . '_' . $c->cat_ID; ?>"
					<?php 
					$in_array = in_array( $c->name, array_keys( $selected_cats) ) AND in_array( $c->cat_ID, $selected_cats ); 
					selected( true, $in_array, true ); ?>>
					<?php echo $c->name; ?>
				</option>
				<?php endforeach; ?>
			</select><br/>
			<small><?php _e( 'Use ctrl (or cmd) + click to select multiple categories.', 'absc' ); ?></small>
		</p>
	<?php 
	}
}

function absc_plugin_init() {
	load_plugin_textdomain( 'absc', false, '/ab-category-search/languages' );
}
add_action( 'plugins_loaded', 'absc_plugin_init' );	


function register_ab_cat_search_widget() {
	register_widget( 'ABCategorySearch' );
}
add_action( 'widgets_init', 'register_ab_cat_search_widget' );

function abcs_search_request( $wp_query ) {
	if ( !isset( $_GET['search_cat'] ) ) return $vars; // if searching from default form do nothing.
	$cats = array();
	foreach( $_GET['search_cat'] as $cat ) {
		if ( 0 != $cat )
			$cats[] = $cat;
	}
	if ( !empty( $cats ) )
		set_query_var( 'category__and', $cats );
	/*
	if ( isset( $_GET['search_cat'] ) ) {
		echo '<pre>';
		print_r( $wp_query->query_vars );
		echo '</pre>';
	}*/
}
add_filter( 'pre_get_posts', 'abcs_search_request' );
?>
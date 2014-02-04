<?php

/*
Plugin Name: AB Categories Search Widget
Plugin URI: 
Description: Provides a Search Widget with the ability to add category selection filters.
Author: Agustin Berasategui
Version: 0.2.5
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
		$hide_op = ( $instance['hide_op'] != 'off' && $instance['hide_op'] != '' );
		
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}
		$incoming_search_cats = ( isset( $_GET['absc_search_cat'] ) ) ? (array) $_GET['absc_search_cat'] : array( 'empty' );
		$mode = $instance['mode'];
		$connector = ( 'and' == $mode || '' == $mode ) ? __( 'and', 'absc' ) : __( 'or', 'absc' ); 
		?>
		<div class="ab-cat-search-container">
			<form role="search" method="get" class="search-form" action="<?php echo home_url( '/' ); ?>">
				<label>
					<span id="absc-search-label" class="screen-reader-text"><?php _e( 'Search', 'absc' ); ?>:</span>
					<input type="search" class="search-field" placeholder="<?php _e( 'Search', 'absc' ); ?>..." 
					value="<?php echo @$_GET['s']; ?>" name="s" title="<?php _e( 'Search', 'absc' ); ?>" 
					 id="absc-search-text" />
				</label><br/>
				<input type="hidden" name="absc_mode" value="<?php echo $mode; ?>" />
				<?php $i = 0; ?>
				<?php foreach( $instance['categories'] as $name => $cat_id ) : ?>
					<?php 
					if ( 0 != $i && !$hide_op ) echo '<small>'.$connector.'</small><br/>';
					$i++;
					?> 
					<label class="abcs_cat_label" for="<?php echo $this->id; ?>_cat_sel"><?php echo $name; ?></label>
					<select name="absc_search_cat[]" id="<?php echo $this->id; ?>_cat_sel">
						<option value="<?php echo $cat_id; ?>" 
							<?php selected( true, in_array( $cat_id, $incoming_search_cats ) ); ?>>
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
				</select>
				<?php endforeach; ?>
				<input type="submit" class="search-submit" id="absc-search-submit" value="<?php _e( 'Search', 'absc' ); ?>" />
			</form>	
		</div>
		<?php
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		foreach ( array('title', 'mode', 'hide_op', 'is_random_order' ) as $val ) {
			$instance[$val] = strip_tags( $new_instance[$val] );
		}
		update_option( 'absc_rorder', $new_instance['is_random_order'] );
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
				value="<?php echo $instance['title']; ?>" style="width:100%;" /><br/>			
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
		<p>
			<label for="<?php echo $this->get_field_id( 'is_random_order' ); ?>">
				<?php _e( 'Random order', 'absc' ); ?>
			</label>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'is_random_order' ); ?>" 
				name="<?php echo $this->get_field_name( 'is_random_order' ); ?>" 
				<?php checked( 'on', $instance['is_random_order'], true ); ?>/><br/>
			<small><?php _e( 'Randomize found posts order.', 'absc' ); ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'mode' ); ?>">
				<?php _e( 'Mode', 'absc' ); ?>:
			</label><br/>
			<select name="<?php echo $this->get_field_name( 'mode' ); ?>" 
				id="<?php echo $this->get_field_id( 'mode' ); ?>">
				<option value="and" <?php selected( 'and', $instance['mode'], true ); ?>>
					<?php _e( 'AND', 'absc' ); ?>
				</option>
				<option value="or" <?php selected( 'or', $instance['mode'], true ); ?>>
					<?php _e( 'OR', 'absc' ); ?>
				</option>
			</select><br/>
			<label for="<?php echo $this->get_field_id( 'hide_op' ); ?>">
				<?php _e( 'Hide Mode', 'absc' ); ?>
			</label>
			<input type="checkbox" name="<?php echo $this->get_field_name( 'hide_op' ); ?>" 
				id="<?php echo $this->get_field_id( 'hide_op' ); ?>" 
				<?php checked( 'on', $instance['hide_op'], true ); ?> />
			<p style="text-align:justify">				
				<small>
					<?php _e( 'This option defines how the search works. If you choose AND it <br/>will be searched
					 the posts that are simultaneously in all the selected <br/> categories. If you choose OR, it will be 
					 searched the posts that <br/>are at least in one of the selected categories.', 'absc' ); ?>
				</small>
			</p>
		</p>
		<p>
			<small>
			<?php _e( 'Please <b>notice</b> that when using "and mode" the search will be<br/>
			done against posts which strictly belongs to selected categories in the<br/>
			search widget. For example, lets say you have posts about bycicles<br/>
			and have the following categories: Condition with new and used as subcategories<br/>
			and colour with blue and white as subcategories. When a visitor	searches for<br/>
			keyword "alterrain" and selects All for condition and All for colour he will<br/>
			obtain the bycicle posts which are marked as belongin to the general categories<br/>
			"condition" and "colour". If they belong to "white" but not to "Colour" they will<br/>
			be not displayed in the result.', 'absc' ); ?>
			</small>
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
	// Is searching from our widget?	
	if ( !isset( $_GET['absc_search_cat'] ) ) return $wp_query; // if searching from default form do nothing.
	
	$located = locate_template( 'search.php' );
	if ( !empty( $located ) )
		add_filter( 'template_include', 'get_search_template' );
	
	// Generate an array containing the list of selected categories.
	$cats = array();
	foreach( $_GET['absc_search_cat'] as $cat ) {
		if ( 0 != $cat )
			$cats[] = $cat;
	}	
	//If categories were found
	if ( !empty( $cats ) ) {			
		//If uncategorized was found remove it from query.
		if ( FALSE !== $uncat )
			set_query_var( 'category__not_in', array( $uncat->cat_ID ) );
		
		// Set post_type to post to exclude pages.
		set_query_var( 'post_type', array( 'post' ) );
		
		if ( '' == $_GET['absc_mode'] || 'and' == $_GET['absc_mode'] ) { //Mode is 'and' or default (and)
			//array_shift( $_GET['absc_search_cat'] );
			set_query_var( 'category__and', $cats );
			//set_query_var( 'cat', implode( ',', $cats ) );
		} else { // Mode is or
			//set_query_var( 'category__in', $cats );
			set_query_var( 'cat', implode( ',', $cats ) );
		}
		if ( 'on' == get_option( 'absc_rorder', 'off' ) ) { //if random order
			set_query_var( 'orderby', 'rand' );
		}
	}
	return $wp_query;
}
add_action( 'pre_get_posts', 'abcs_search_request' );

function abcs_enqueue_front_scripts() {
	wp_enqueue_script( 'abcs-main', WP_PLUGIN_URL .'/ab-categories-search-widget/js/main.js', array( 'jquery' ) );
}
//add_action( 'wp_enqueue_scripts', 'abcs_enqueue_front_scripts' );
?>
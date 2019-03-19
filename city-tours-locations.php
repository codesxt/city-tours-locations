<?php

/*
Plugin Name: City Tours Locations
Plugin URI: http://ferativ.com/
Description: Adds location data to Tour, Hotel and Car entities in Cityt Tours.
Author: Ferativ
Version: 1.0
Author URI: http://ferativ.com/
*/


/**
 * Adds a meta box to the post editing screen
 */
function ctloc_location_meta() {
	add_meta_box( 'ctloc_meta', __( 'Georeferencia', 'ctloc-textdomain' ), 'ctloc_meta_callback', 'tour', 'normal', 'core' );
}
add_action( 'add_meta_boxes', 'ctloc_location_meta' );

/**
 * Outputs the content of the meta box
 */
function ctloc_meta_callback( $post ) {
	wp_nonce_field( basename( __FILE__ ), 'ctloc_nonce' );
	$ctloc_stored_meta = get_post_meta( $post->ID );
	?>
	<div id="mapid"></div>

	<script>
		let mymap = L.map('mapid').setView([-33.4727092,-70.769915], 13);
		L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
	    attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
	    maxZoom: 18,
	    id: 'mapbox.streets',
	    accessToken: 'pk.eyJ1IjoiY29kZXN4dCIsImEiOiJjanRnNnR2dnAwMnI2NDNxc3BsMTZhbG43In0.JxJPD5Tumyb1SkuPF_GL3Q'
		}).addTo(mymap);

		let drawnItems = new L.FeatureGroup();
		mymap.addLayer(drawnItems);

		let drawControl = new L.Control.Draw({
			draw: {
        polygon: false,
        marker: false
	    },
			edit: {
				featureGroup: drawnItems
			}
		});

		let editLayers = [];
		mymap.addControl(drawControl);

		<?php
			if ( isset ( $ctloc_stored_meta['meta-geo'] ) ) {
		?>
		let storedGeo = L.geoJSON(<?php echo $ctloc_stored_meta['meta-geo'][0]; ?>).addTo(mymap);
		editLayers.push(storedGeo);
		<?php
			}
		?>
		function clearLayers () {
			// Clear visible layers
			for (l of editLayers) {
				mymap.removeLayer(l);
			}
			document.getElementById("meta-geo").value = "";
		}

		mymap.on('draw:created', function (e) {
			let layer = e.layer;
			clearLayers();
			editLayers.push(layer);
	    mymap.addLayer(layer);
			document.getElementById("meta-geo").value = JSON.stringify(layer.toGeoJSON());
		});

	</script>
	<p>
		<input type="hidden" name="meta-geo" id="meta-geo" value="<?php if ( isset ( $ctloc_stored_meta['meta-geo'] ) ) echo $ctloc_stored_meta['meta-geo'][0]; ?>" />
	</p>

	<button class="button secondary" type="button" onclick="clearLayers()">
		Eliminar Georeferencia
	</button>

	<?php
}



/**
 * Saves the custom meta input
 */
function ctloc_meta_save( $post_id ) {

	// Checks save status
	$is_autosave = wp_is_post_autosave( $post_id );
	$is_revision = wp_is_post_revision( $post_id );
	$is_valid_nonce = ( isset( $_POST[ 'ctloc_nonce' ] ) && wp_verify_nonce( $_POST[ 'ctloc_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';

	// Exits script depending on save status
	if ( $is_autosave || $is_revision || !$is_valid_nonce ) {
		return;
	}

	// Checks for input and sanitizes/saves if needed
	if( isset( $_POST[ 'meta-geo' ] ) ) {
		update_post_meta( $post_id, 'meta-geo', sanitize_text_field( $_POST[ 'meta-geo' ] ) );
	}

}
add_action( 'save_post', 'ctloc_meta_save' );

/**
 * Adds the Leaflet resources
 */
function ctloc_admin_leaflet_styles(){
	global $typenow;
	if( $typenow == 'tour' ) {
		wp_enqueue_style( 'ctloc_admin_leaflet_styles', plugin_dir_url( __FILE__ ) . 'js/leaflet/leaflet.css' );
		wp_enqueue_style( 'ctloc_admin_leaflet_draw_styles', plugin_dir_url( __FILE__ ) . 'js/leaflet.draw/leaflet.draw.css' );
	}
}
add_action( 'admin_print_styles', 'ctloc_admin_leaflet_styles' );

/**
 * Enqueue Leaflet scripts
 */
function ctloc_admin_leaflet_scripts(){
	global $typenow;
	if( $typenow == 'tour' ) {
		wp_enqueue_script( 'leaflet-js', plugin_dir_url( __FILE__ ) . 'js/leaflet/leaflet.js' );
		wp_enqueue_script( 'leaflet-draw-js', plugin_dir_url( __FILE__ ) . 'js/leaflet.draw/leaflet.draw.js' );
	}
}
add_action( 'admin_enqueue_scripts', 'ctloc_admin_leaflet_scripts' );

/**
 * Adds the plugin styles
 */
function ctloc_admin_map_styles(){
	global $typenow;
	if( $typenow == 'tour' ) {
		wp_enqueue_style( 'ctloc_admin_map_styles', plugin_dir_url( __FILE__ ) . 'css/custom.css' );
	}
}
add_action( 'admin_print_styles', 'ctloc_admin_map_styles' );

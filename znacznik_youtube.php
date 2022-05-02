<?php
/*
Plugin Name: Znacznik YouTube
Plugin URI:
Description: Dodawanie filmu z YouTube. Optymalizacja ładowania się strony. Wtyczka dodaje też przycisk do edytora TinyMCE
Version: 1.0
Author: Paweł Nowak
Author URI: http://generatewp.com

Copyright (C) 2020  Paweł Nowak

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.





*/
class CzysteSpalanie_Znacznik_YouTube{
	/**
	 * $shortcode_tag
	 * holds the name of the shortcode tag
	 * @var string
	 */
	public $shortcode_tag = 'youtube';

	/**
	 * __construct
	 * class constructor will set the needed filter and action hooks
	 *
	 * @param array $args
	 */
	function __construct($args = array()){
		//add shortcode
		add_shortcode( $this->shortcode_tag, array( $this, 'shortcode_handler' ) );
		add_action( 'wp_enqueue_scripts', array( $this, '_action__enqueue_js_css' ) );

		if ( is_admin() ){
			add_action( 'admin_head', array( $this, 'admin_head' ) );
			add_action( 'admin_enqueue_scripts', array($this , 'admin_enqueue_scripts' ) );
		}
	}

	function _action__enqueue_js_css(){
		
		# Lajtboks (lightbox) dla filmów z YouTube

		wp_register_script( 'lity', plugin_dir_url( __FILE__ ) . 'lity/lity.min.js', array( 'jquery' ), '2.4.1', true );
		wp_register_style( 'lity', plugin_dir_url( __FILE__ ) . 'lity/lity.min.css', null, 0.1, 'all' );
		
	}

	/**
	 * shortcode_handler
	 * @param  array  $atts shortcode attributes
	 * @param  string $content shortcode content
	 * @return string
	 */
	function shortcode_handler($atts , $content = null){

	wp_enqueue_script('lity');
	wp_enqueue_style('lity');

	$url = plugin_dir_url( __FILE__ );

	    $a = shortcode_atts( array(
        'link' => '',
				'col' => '4',
				'float'=> ''

    ), $atts );

	$float = ( $a['float'] ) ? ' float-' . $a['float'] : '';

	$liczba = $a['col'] + 2;

	$classes = array (
		'd-inline-block',
		'col-' . $liczba,
		'col-sm-' . $a['col'],
		'col-md-' . $a['col'],
		$float,
	);

	$classes = implode( ' ', $classes);

	preg_match( "/(?<=v=|v\/|vi=|vi\/|youtu.be\/)[a-zA-Z0-9_-]{11}/", $a['link'], $film_id );

	#sprawdzam czy film jest dostępny na podstawie miniaturki
	$headers = @get_headers( 'https://img.youtube.com/vi/' . $film_id[0] . '/mqdefault.jpg' );

	if ( empty( $film_id ) ){
		$film_id = array(1);
	}

	if ( strpos( $headers[0], '200') ) {
	$img = '<img src="' . $url . 'images/play.png" class="card-title">';
	} else {
	$img = '<span class="badge badge-danger ml-1 mt-1 p-1">⚠ Film niedostępny</span>';
	}

	$output = '<figure class="col-6 col-sm-4 col-md-3 ' . esc_attr( $float ) . '">' . "\r\n";
	$output .= '<div class="shadow card mb-4 box-shadow rounded-0">' . "\r";
	$output .= '<a href="' . esc_url( $a['link'] ) . '" data-lity><img class="card-img-top img-fluid rounded-0"  src="https://img.youtube.com/vi/' . $film_id[0] . '/mqdefault.jpg"/></a>' . "\r\n";
	$output .= '<a href="' . esc_url( $a['link'] ) .'" class="position-absolute" data-lity>' . $img . '</a>' . "\r\n";

	$output .= '<figcaption class="card-body text-center bg-light py-2 px-2">' . "\r\n";
	$output .= '<p class="small card-text font-weight-normal">' . trim( $content ) . ' <a href="' . esc_url( $a['link'] ) . '" title="Link bezpośredni do YouTube" target="_blank" class="external-link"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-box-arrow-up-right align-baseline" viewBox="0 0 16 16">
	  <path fill-rule="evenodd" d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5z"/>
	  <path fill-rule="evenodd" d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0v-5z"/>
	</svg></a> </p>' . "\r\n";;
	$output .= '</figcaption>' . "\r\n";
	$output .= '</div>' . "\r\n";
	$output .= '</figure>' . "\r\n";

	return $output;
	}

	/**
	 * admin_head
	 * calls your functions into the correct filters
	 * @return void
	 */
	function admin_head() {
		// check user permissions
		if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
			return;
		}

		// check if WYSIWYG is enabled
		if ( 'true' == get_user_option( 'rich_editing' ) ) {
			add_filter( 'mce_external_plugins', array( $this ,'mce_external_plugins' ) );
			add_filter( 'mce_buttons', array($this, 'mce_buttons' ) );
		}
	}

	/**
	 * mce_external_plugins
	 * Adds our tinymce plugin
	 * @param  array $plugin_array
	 * @return array
	 */
	function mce_external_plugins( $plugin_array ) {
		$plugin_array[$this->shortcode_tag] = plugins_url( 'js/mce-button.js' , __FILE__ );
		return $plugin_array;
	}

	/**
	 * mce_buttons
	 * Adds our tinymce button
	 * @param  array $buttons
	 * @return array
	 */
	function mce_buttons( $buttons ) {
		array_push( $buttons, $this->shortcode_tag );
		return $buttons;
	}

	/**
	 * admin_enqueue_scripts
	 * Used to enqueue custom styles
	 * @return void
	 */
	function admin_enqueue_scripts(){
		 wp_enqueue_style('youtube_shortcode', plugins_url( 'css/mce-button.css' , __FILE__ ) );
	}
}//end class

new CzysteSpalanie_Znacznik_YouTube();

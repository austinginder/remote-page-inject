<?php 

/**
 * Plugin Name:       Remote Page Inject
 * Plugin URI:        https://github.com/austinginder/remote-page-inject
 * Description:       Fetches raw html from remote URL and injects into current page.
 * Version:           1.0.1
 * Author:            Austin Ginder
 * Author URI:        https://austinginder.com
 * License:           MIT License
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       remote-page-inject
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

function remote_page_inject_shortcode( $atts ) {

    // Attributes
    $atts = shortcode_atts( [ "url" => "" ], $atts, 'remote_page_inject' );
    $url  = $atts['url'];

    if ( empty( $url ) ) {
        return;
    }

    // Disable warnings from bad HTML markup
    libxml_use_internal_errors(true);

    $slug = sanitize_title( $url );

    // Load request from transient
    $page = get_transient( "remote_page_inject_{$slug}" );

    // If empty then update transient with remote call
    if ( empty( $page ) ) {

        // Fetch HTML from URL
        $response = wp_remote_get( $url );
        $page     = $response["body"];

        // Save the API response so we don't have to call again until tomorrow.
        set_transient( "remote_page_inject_{$slug}", $page, 1 * DAY_IN_SECONDS );

    }

    $dom  = new DOMDocument;
    $body = new DOMDocument;
    $dom->loadHTML( $page );
    $body_element   = $dom->getElementsByTagName('body')->item(0);

    foreach ($body_element->childNodes as $child){
        $body->appendChild($body->importNode($child, true));
    }

    return $body->saveHTML();

}
add_shortcode( 'remote_page_inject', 'remote_page_inject_shortcode' );

function remote_page_inject_if_needed() {

    global $post;

    if ( has_shortcode( $post->post_content, "remote_page_inject" ) ) {
        preg_match( '/\[remote_page_inject url=(.+)\]/', $post->post_content, $matches );
        // Extracts URL from shortcode
        $url = trim( trim( $matches[1], "'"), '"' );
        echo get_remote_page_inject_headers( $url );
    }

}
add_action( 'wp_head', 'remote_page_inject_if_needed' );

function get_remote_page_inject_headers( $url ) {

    // Disable warnings from bad HTML markup
    libxml_use_internal_errors(true);

    $slug = sanitize_title( $url );

    // Load request from transient
    $page = get_transient( "remote_page_inject_{$slug}" );

    // If empty then update transient with remote call
    if ( empty( $page ) ) {

        // Fetch HTML from URL
        $response = wp_remote_get( $url );
        $page     = $response["body"];

        // Save the API response so we don't have to call again until tomorrow.
        set_transient( "remote_page_inject_{$slug}", $page, 1 * DAY_IN_SECONDS );

    }
    $dom  = new DOMDocument;
    $head = new DOMDocument;
    $dom->loadHTML( $page );
    $header_element = $dom->getElementsByTagName('head')->item(0);
    foreach ( $header_element->childNodes as $child ) {
        $head->appendChild($head->importNode($child, true));
    }

    // Load HTML tag
    $html_tag  = $dom->getElementsByTagName( "html" );
    if ( empty( $html_tag->length ) ) {
        return $head->saveHTML();
    }

    $styles    = $html_tag[0]->getAttribute( "style" );
    $classes   = $html_tag[0]->getAttribute( "class" );

    // Inject styles for HTML tag
    if ( ! empty( $styles ) ) {
        $styleNode = $dom->createElement("style", "html { $styles }" );
        $styleNode->setAttribute('type', 'text/css');
        $head->appendChild($head->importNode($styleNode, true));
    }

    // Inject classes for HTML tag
    if ( ! empty( $classes ) ) {
        $javascript_code = <<< EOT
remote_classes = "$classes"
remote_classes.trim().split(" ").forEach(addRemoteClassToHTML)
function addRemoteClassToHTML(item) {
    let el = document.querySelector("html");
    el.classList.add( item );
}
EOT;
        $javascriptNode = $dom->createElement("script");
        $script         = $dom->createTextNode( $javascript_code );
        $javascriptNode->appendChild( $script );
        $head->appendChild($head->importNode($javascriptNode, true));
    }

    return $head->saveHTML();

}

// Hook in plugin updates
require "updater.php";
new RemotePageInject\Updater();
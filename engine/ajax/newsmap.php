<?php
/*
=============================================
 Name      : MWS News Sitemap v1.3
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : https://dle.net.tr/
 License   : MIT License
 Date      : 10.11.2018
=============================================
*/

if ( ! defined('DATALIFEENGINE')) {
	header( "HTTP/1.1 403 Forbidden" );
	header ( 'Location: ../../' );
	die( "Hacking attempt!" );
}

require_once ENGINE_DIR . '/data/newsmap.conf.php';

$settings = $nset;

if ( isset( $_POST['google'] ) ) {
	if ( $_POST['google']['user_hash'] == "" or $_POST['google']['user_hash'] != $dle_login_hash ) {
		die( "Hacking attempt!" );
	}
	unset( $_POST['google']['user_hash'] );
	foreach ( $_POST['google'] as $key => $value ) {
		if ( is_array( $value ) ) $value = implode( "||", $value );
		$settings[ "google_" . $key ] = $value;
	}
}

if ( isset( $_POST['yandex'] ) ) {
	if ( $_POST['yandex']['user_hash'] == "" or $_POST['yandex']['user_hash'] != $dle_login_hash ) {
		die( "Hacking attempt!" );
	}
	unset( $_POST['yandex']['user_hash'] );
	foreach ( $_POST['yandex'] as $key => $value ) {
		if ( is_array( $value ) ) $value = implode( "||", $value );
		$settings[ "yandex_" . $key ] = $value;
	}
}

$find = array( "'\r'", "'\n'" );
$replace = array( "", "" );

$handler = fopen( ENGINE_DIR . '/data/newsmap.conf.php', "w" );
fwrite( $handler, "<?PHP \n\n//MWS News Sitemap Configurations\n\n\$nset = array (\n\n" );
foreach ( $settings as $name => $value ) {
	$value = trim(strip_tags(stripslashes( $value )));
	$value = htmlspecialchars( $value, ENT_QUOTES, $config['charset']);
	$value = preg_replace( $find, $replace, $value );
	$name = trim(strip_tags(stripslashes( $name )));
	$name = htmlspecialchars( $name, ENT_QUOTES, $config['charset'] );
	$name = preg_replace( $find, $replace, $name );
	$value = str_replace( "$", "&#036;", $value );
	$value = str_replace( "{", "&#123;", $value );
	$value = str_replace( "}", "&#125;", $value );
	$value = str_replace( ".", "", $value );
	$value = str_replace( '/', "", $value );
	$value = str_replace( chr(92), "", $value );
	$value = str_replace( chr(0), "", $value );
	$value = str_replace( '(', "", $value );
	$value = str_replace( ')', "", $value );
	$value = str_ireplace( "base64_decode", "base64_dec&#111;de", $value );
	$name = str_replace( "$", "&#036;", $name );
	$name = str_replace( "{", "&#123;", $name );
	$name = str_replace( "}", "&#125;", $name );
	$name = str_replace( ".", "", $name );
	$name = str_replace( '/', "", $name );
	$name = str_replace( chr(92), "", $name );
	$name = str_replace( chr(0), "", $name );
	$name = str_replace( '(', "", $name );
	$name = str_replace( ')', "", $name );
	$name = str_ireplace( "base64_decode", "base64_dec&#111;de", $name );
	fwrite( $handler, "'{$name}' => '{$value}',\n\n" );
}
fwrite( $handler, ");\n\n?>" );
fclose( $handler );

echo "Ayarlar başarıyla kaydedildi.";

?>
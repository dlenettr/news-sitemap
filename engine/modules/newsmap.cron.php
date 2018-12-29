<?php
/*
=============================================
 Name      : MWS News SiteMap v1.2.1
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : http://dle.net.tr/   (c) 2017
 License   : MIT License
=============================================
*/

if ( ! defined( 'DATALIFEENGINE' ) ) {
	die( "Hacking attempt!" );
}

$_TIME = time();

$cat_info = get_vars ( "category" );
if ( ! is_array( $cat_info )) {
	$cat_info = array ();
	$db->query ( "SELECT * FROM " . PREFIX . "_category ORDER BY posi ASC" );
	while ( $row = $db->get_row () ) {
		$cat_info[$row['id']] = array ();
		foreach ( $row as $key => $value ) {
			$cat_info[$row['id']][$key] = stripslashes ( $value );
		}
	}
	set_vars ( "category", $cat_info );
	$db->free ();
}

// cron.php newsmap
if ( isset( $cronmode ) && $cronmode == "newsmap" ) {

	if ( ! isset( $config ) ) include_once ENGINE_DIR . '/data/config.php';
	include_once ENGINE_DIR . '/data/newsmap.conf.php';
	include_once ENGINE_DIR . '/classes/newsmap.class.php';

	$map = new NewsMAP( $config, "google", $nset );
	$config['charset'] = strtolower( $config['charset'] );
	$map->limit = 1000;	// Google limit: 1000

	$not_allow_cats = array();
	foreach( $cat_info as $value ) {
		if ( ! $value['allow_map'] ) $not_allow_cats[] = $value['id'];
	}
	if ( count( $not_allow_cats ) ) {
		if ( $config['allow_multi_category'] ) {
			$not_allow_cats = "category NOT REGEXP '[[:<:]](" . implode ( '|', $not_allow_cats ) . ")[[:>:]]' AND ";
		} else {
			$not_allow_cats = "category NOT IN ('" . implode ( "','", $not_allow_cats ) . "') AND ";
		}
	} else $not_allow_cats = "";

	$thisdate = date( "Y-m-d H:i:s", time() );
	if ( $config['no_date'] AND !$config['news_future'] ) $where_date = " AND date < '" . $thisdate . "'";
	else $where_date = "";

	$count = $db->super_query( "SELECT COUNT(p.id) as total FROM " . PREFIX . "_post p LEFT JOIN " . PREFIX . "_post_extras e ON (p.id=e.news_id) WHERE {$not_allow_cats} approve='1'" . $where_date );
	if ( $map->limit < $count['total'] ) {
		$pages_count = @ceil( $count['total'] / $map->limit );
		$sitemap = $map->build_index( $pages_count );
	} else {
		$sitemap = $map->build_map();
	}

	if ( $config['charset'] != "utf-8" ) {
		if ( function_exists( 'mb_convert_encoding' ) ) {
			$sitemap = mb_convert_encoding( $sitemap, "UTF-8", $config['charset'] );
		} else if ( function_exists( 'iconv' ) ) {
			$sitemap = iconv( $config['charset'], "UTF-8//IGNORE", $sitemap );
		}
	}
	$handler = fopen( ROOT_DIR . "/uploads/newsmap-google.xml", "wb+" );
	fwrite( $handler, $sitemap );
	fclose( $handler );
	@chmod( ROOT_DIR . "/uploads/newsmap-google.xml", 0666 );

	if ( $map->limit < $count['total'] ) {
		$pages_count = @ceil( $count['total'] / $map->limit );
		for ( $i = 1; $i <= $pages_count; $i++ ) {
			$sitemap = $map->build_map( $i );
			if ( $config['charset'] != "utf-8" ) {
				if ( function_exists( 'mb_convert_encoding' ) ) {
					$sitemap = mb_convert_encoding( $sitemap, "UTF-8", $config['charset'] );
				} else if ( function_exists( 'iconv' ) ) {
					$sitemap = iconv( $config['charset'], "UTF-8//IGNORE", $sitemap );
				}
			}
			$handler = fopen( ROOT_DIR . "/uploads/newsmap-google-{$i}.xml", "wb+" );
			fwrite( $handler, $sitemap );
			fclose( $handler );
			@chmod( ROOT_DIR . "/uploads/newsmap-google-{$i}.xml", 0666 );
		}
	}

	$text = "Ping:NO - ";
	if ( $count['total'] == 0 ) die( $text . "No news found in site" . "\n" );

	if ( isset( $ping ) && $ping == "yes" ) {
		$result = $map->ping( "google" );
		if ( strpos( $result, "successfully added" ) !== false ) $text = "Ping:OK - ";
	} else {
		$result = $map->ping( "google" );
		if ( strpos( $result, "successfully added" ) !== false ) $text = "Ping:OK - ";
	}
	if ( ! isset( $die ) ) {
		die( $text . date( "d.m.Y H:i:s", $_TIME ) . "\n" );
	}
}


?>
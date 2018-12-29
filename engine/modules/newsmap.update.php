<?php
/*
=============================================
 Name      : MWS News SiteMap v1.2
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : http://dle.net.tr/   (c) 2015
 License   : MIT License
=============================================
*/

if ( ! defined( 'DATALIFEENGINE' ) ) {
	die( "Hacking attempt!" );
}

if ( ! isset( $newsmap_mode ) ) {
	die( "Hacking attempt!" );
}

require_once ENGINE_DIR . '/data/newsmap.conf.php';

$die = true;
if ( $newsmap_mode == "modules/addnews" ) {
	if ( $nset['google_auto_addupdate'] == "2" || $nset['google_auto_addupdate'] == "3" ) {
		$ping = "no";
		if ( $nset['google_auto_ping'] == "1" || $nset['google_auto_ping'] == "3" ) {
			$ping = "yes";
		}
		$cronmode = "newsmap";
		include_once ROOT_DIR . "/engine/modules/newsmap.cron.php";
	}
}

else if ( $newsmap_mode == "inc/addnews" ) {
	if ( $nset['google_auto_addupdate'] == "1" || $nset['google_auto_addupdate'] == "3" ) {
		$ping = "no";
		if ( $nset['google_auto_ping'] == "1" || $nset['google_auto_ping'] == "3" ) {
			$ping = "yes";
		}
		$cronmode = "newsmap";
		include_once ROOT_DIR . "/engine/modules/newsmap.cron.php";
	}
}

else if ( $newsmap_mode == "ajax/editnews" ) {
	if ( $nset['google_auto_editupdate'] == "2" || $nset['google_auto_editupdate'] == "3" ) {
		$ping = "no";
		if ( $nset['google_auto_ping'] == "2" || $nset['google_auto_ping'] == "3" ) {
			$ping = "yes";
		}
		$cronmode = "newsmap";
		include_once ROOT_DIR . "/engine/modules/newsmap.cron.php";
	}
}

else if ( $newsmap_mode == "inc/editnews" ) {
	if ( $nset['google_auto_addupdate'] == "1" || $nset['google_auto_addupdate'] == "3" ) {
		$ping = "no";
		if ( $nset['google_auto_ping'] == "2" || $nset['google_auto_ping'] == "3" ) {
			$ping = "yes";
		}
		$cronmode = "newsmap";
		include_once ROOT_DIR . "/engine/modules/newsmap.cron.php";
	}
}

?>
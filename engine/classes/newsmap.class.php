<?php
/*
=============================================
 Name      : MWS News SiteMap v1.2
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : http://dle.net.tr/   (c) 2015
 License   : MIT License
=============================================
*/

class NewsMAP {

	var $allow_url = "";
	var $home = "";
	var $limit = 0;
	var $provider = "";
	var $default = array();
	var $settings = array();

	function __construct( $config, $provider, $settings ) {
		$this->allow_url = $config['allow_alt_url'];
		$this->home = $config['http_home_url'];
		$this->provider = $provider;
		$this->default['google'] = array();
		$this->default['yandex'] = array();
		foreach ( $settings as $key => $value ) {
			if ( substr( $key, 0, 6 ) == "google" ) {
				$this->default['google'][ substr( $key, 7 ) ] = $value;
			} else {
				$this->default['yandex'][ substr( $key, 7 ) ] = $value;
			}
			$this->default['google']['genres'] = str_replace( "||", ",", $this->default['google']['genres'] );
			$this->default['yandex']['genres'] = str_replace( "||", ",", $this->default['yandex']['genres'] );
		}
	}

	function build_index( $count ) {
		$map = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
		$lastmod = date( "Y-m-d" );
		for ( $i = 1; $i <= $count; $i++ ) {
			if ( $this->provider == "google" ) {
				$map .= "\t<sitemap>\n\t\t<loc>{$this->home}uploads/newsmap-google-{$i}.xml</loc>\n\t\t<lastmod>{$lastmod}</lastmod>\n\t</sitemap>\n";
			} else if ( $this->provider == "yandex" ) {
				$map .= "\t<sitemap>\n\t\t<loc>{$this->home}uploads/newsmap-yandex-{$i}.xml</loc>\n\t\t<lastmod>{$lastmod}</lastmod>\n\t</sitemap>\n";
			}
		}
		$map .= "</sitemapindex>";
		return $map;
	}


	function build_map( $page = false ) {
		if ( $this->provider == "google" ) {
			$map = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"\n        xmlns:news=\"http://www.google.com/schemas/sitemap-news/0.9\">\n";
		} else if ( $this->provider == "yandex" ) {
			$map = "";
		}
		$map .= $this->get_news( $page = $page );
		$map .= "</urlset>";
		return $map;
	}

	function get_news( $page = false ) {
		global $db, $config;
		$xml = "";
		if ( $page ) {
			$page = $page - 1;
			$page = $page * $this->limit;
			$this->sql_limit = " LIMIT {$page},{$this->limit}";
		} else {
			if ( $this->limit < 1 ) $this->limit = false;
			if ( $this->limit ) {
				$this->sql_limit = " LIMIT 0," . $this->limit;
			} else {
				$this->sql_limit = "";
			}
		}

		$cat_info = get_vars( "category" );
		if( ! is_array( $cat_info ) ) {
			$cat_info = array ();
			$db->query( "SELECT * FROM " . PREFIX . "_category ORDER BY posi ASC" );
			while ( $row = $db->get_row() ) {
				$cat_info[$row['id']] = array ();
				foreach ( $row as $key => $value ) {
					$cat_info[$row['id']][$key] = $value;
				}
			}
			set_vars( "category", $cat_info );
			$db->free();
		}
		$not_allow_cats = array();
		foreach( $cat_info as $value ) {
			if ( ! $value['allow_map'] && ! $this->default[ $this->provider ]['use_cat'] ) $not_allow_cats[] = $value['id'];
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

		$keys = $this->default[ $this->provider ]['keys'];

		$result = $db->query( "SELECT p.id, p.date, p.title, p.alt_name, p.category, p.{$keys}, e.editdate, e.disable_index FROM " . PREFIX . "_post p LEFT JOIN " . PREFIX . "_post_extras e ON (p.id=e.news_id) WHERE {$not_allow_cats} approve=1" . $where_date . " ORDER BY date DESC" . $this->sql_limit );

		while ( $row = $db->get_row( $result ) ) {
			$row['date'] = strtotime( $row['date'] );
			$row['category'] = intval( $row['category'] );
			if ( $row['disable_index'] ) continue;
			if ( $this->allow_url ) {
				if ( $config['seo_type'] == 1 OR  $config['seo_type'] == 2 ) {
					if ( $row['category'] and $config['seo_type'] == 2 ) {
						$loc = $this->home . get_url( $row['category'] ) . "/" . $row['id'] . "-" . $row['alt_name'] . ".html";
					} else {
						$loc = $this->home . $row['id'] . "-" . $row['alt_name'] . ".html";
					}
				} else {
					$loc = $this->home . date( 'Y/m/d/', $row['date'] ) . $row['alt_name'] . ".html";
				}
			} else {
				$loc = $this->home . "index.php?newsid=" . $row['id'];
			}
			if ( $row['editdate'] ){
				$row['date'] =  $row['editdate'];
			}
			$news = array(
				'title'	 	=> htmlspecialchars( strip_tags( stripslashes( $row['title'] ) ), ENT_QUOTES, $config['charset'] ),
				'loc' 		=> $loc,
				'date' 		=> gmdate( "Y-m-d\TH:i:s" . $this->default[ $this->provider ]['gmt'], $row['date'] ),
				'keywords'  => htmlspecialchars( $row[ $keys ] ),
			);
			$xml .= $this->get_xml( $news );
			unset( $news );
		}
		return $xml;
	}

	function ping( $service ) {
		global $config;

		$data = false;
		if ( $service == "google" ) {
			$url = "http://google.com/webmasters/sitemaps/ping?sitemap=";
			if ( $config['allow_alt_url'] ) {
				$map = $config['http_home_url'] . "newsmap-google.xml";
			} else {
				$map = $config['http_home_url']."uploads/newsmap-google.xml";
			}
			$file = $url . urlencode( $map );
		}

		if ( function_exists( 'curl_init' ) ) {
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $file );
			curl_setopt( $ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 6 );
			$data = curl_exec( $ch );
			curl_close( $ch );
			return $data;
		} else {
			return @file_get_contents( $file );
		}
	}


	function get_url( $id, $cat_info ) {
		if( ! $id ) return;
		$parent_id = $cat_info[$id]['parentid'];
		$url = $cat_info[$id]['alt_name'];
		while ( $parent_id ) {
			$url = $cat_info[$parent_id]['alt_name'] . "/" . $url;
			$parent_id = $cat_info[$parent_id]['parentid'];
			if ( $cat_info[$parent_id]['parentid'] == $cat_info[ $parent_id ]['id'] ) break;
		}
		return $url;
	}

	function get_xml( $news ) {
		foreach( $this->default[ $this->provider ] as $key => $val ) {
		   if ( ! array_key_exists( $key, $news ) ){
				$news[ $key ] = $val;
		   }
		}
		$loc = htmlspecialchars( $news['loc'], ENT_QUOTES, 'ISO-8859-1' );
		$xml = "\t<url>\n";
		$xml .= "\t\t<loc>" . $news['loc'] . "</loc>\n";
		$xml .= "\t\t<news:news>\n";
		$xml .= "\t\t\t<news:publication>\n";
		$xml .= "\t\t\t\t<news:name>" . $news['prefix'] . "</news:name>\n";
		$xml .= "\t\t\t\t<news:language>" . $news['lang'] . "</news:language>\n";
		$xml .= "\t\t\t</news:publication>\n";
		if ( $this->default[ $this->provider ]['show_access'] == "1" ) {
			$xml .= "\t\t\t<news:access>" . $news['access'] . "</news:access>\n";
		} else if ( $this->default[ $this->provider ]['show_access'] == "0" ) {
			$xml .= "\t\t\t<news:access/>\n";
		} else {

		}
		if ( $this->default[ $this->provider ]['show_genres'] == "1" ) {
			$xml .= "\t\t\t<news:genres>" . $news['genres'] . "</news:genres>\n";
		} else if ( $this->default[ $this->provider ]['show_genres'] == "0" ) {
			$xml .= "\t\t\t<news:genres/>\n";
		} else {

		}
		$xml .= "\t\t\t<news:publication_date>" . $news['date'] . "</news:publication_date>\n";
		$xml .= "\t\t\t<news:title>" . $news['title'] . "</news:title>\n";
		if ( ! empty( $news['keywords'] ) ) {
			$xml .= "\t\t\t<news:keywords>" . $news['keywords'] . "</news:keywords>\n";
		} else {
			$xml .= "\t\t\t<news:keywords/>\n";
		}
		//$xml .= "\t\t<news:stock_tickers>" . $news['stock_tickers'] . "<news:stock_tickers>\n";	// Borsa, hisse senedi vs.
		$xml .= "\t\t</news:news>\n";
		$xml .= "\t</url>\n";
		return $xml;
	}

}

?>
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

if ( ! defined( 'DATALIFEENGINE' ) OR ! defined( 'LOGGED_IN' ) ) {
  die("Hacking attempt!");
}

require_once ENGINE_DIR . '/data/newsmap.conf.php';

function makeDropDown( $options, $name, $selected, $id = "" ) {
	$id = ( ! empty( $id ) ) ? " id=\"$id\"" : "";
	$output = "<select class=\"uniform\" name=\"$name\"{$id}>\r\n";
	foreach( $options as $value => $description ) {
		$output .= "<option value=\"$value\"";
		if ( $selected == $value ) {
			$output .= " selected ";
		}
		$output .= ">$description</option>\n";
	}
	$output .= "</select>";
	return $output;
}

function makeMultiSelect( $options, $name, $selected ) {
	$size = ( count( $options ) >= 6 ) ? 6 : count( $options );
	$output = "<select class=\"uniform\" style=\"min-width:100px;\" size=\"".$size."\" name=\"{$name}[]\" multiple=\"multiple\">\r\n";
	foreach ( $options as $value => $description ) {
		$output .= "<option value=\"{$value}\"";
		for ( $x = 0; $x <= count( $selected ); $x++ ) {
			if ( $value == $selected[$x] ) $output .= " selected ";
		}
		$output .= ">{$description}</option>\n";
	}
	$output .= "</select>";
	return $output;
}


if ( $_GET['action'] == "create-google" ) {

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
	if ( isset( $_GET['ping'] ) && $_GET['ping'] == "yes" ) {
		$map->ping( "google" );
	}
}

$google_reg_div = ( $nset['google_show_access'] == "1" ) ? "block" : "none";
$google_genres_div = ( $nset['google_show_genres'] == "1" ) ? "block" : "none";
$yandex_reg_div = ( $nset['yandex_show_access'] == "1" ) ? "block" : "none";
$yandex_genres_div = ( $nset['yandex_show_genres'] == "1" ) ? "block" : "none";

$google_genres = explode( "||", $nset['google_genres'] );
$yandex_genres = explode( "||", $nset['yandex_genres'] );

$root_dir = ROOT_DIR;

echoheader( "<i class=\"fa fa-globe\"></i> MWS News Sitemap", "Haber sitemapları oluşturma sayfası" );
echo <<<HTML
<style>
#cron_info { display: none; }
#google_settings { display: none; margin-bottom: 5px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
	#google_settings ul { padding: 0; margin-left: 10px; list-style: circle; }
#google_reg_div { margin: 5px; display: {$google_reg_div}; }
#google_genres_div { margin: 5px; display: {$google_genres_div}; }
	#google_genres_div select { margin-top: 5px; width: 250px; height: 140px; padding: 1px; }
	#google_genres_div select option { padding: 3px 4px; }
#yandex_settings { display: none; margin-bottom: 5px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
	#yandex_settings ul { padding: 0; margin-left: 10px; list-style: circle; }
#yandex_reg_div { margin: 5px; display: {$yandex_reg_div}; }
#yandex_genres_div { margin: 5px; display: {$yandex_genres_div}; }
	#yandex_genres_div select { margin-top: 5px; width: 250px; height: 140px; padding: 1px; }
	#yandex_genres_div select option { padding: 3px 4px; }
</style>
<script>
$(document).ready( function() {
	$("select#google_show_access").change( function() {
		var sel = $(this).val();
		if ( sel == "1" ) {
			$("#google_reg_div").fadeIn();
		} else {
			$("#google_reg_div").fadeOut();
		}
	});
	$("select#google_show_genres").change( function() {
		var sel = $(this).val();
		if ( sel == "1" ) {
			$("#google_genres_div").fadeIn();
		} else {
			$("#google_genres_div").fadeOut();
		}
	});
	$("select#yandex_show_access").change( function() {
		var sel = $(this).val();
		if ( sel == "1" ) {
			$("#yandex_reg_div").fadeIn();
		} else {
			$("#yandex_reg_div").fadeOut();
		}
	});
	$("select#yandex_show_genres").change( function() {
		var sel = $(this).val();
		console.log( sel );
		if ( sel == "1" ) {
			$("#yandex_genres_div").fadeIn();
		} else {
			$("#yandex_genres_div").fadeOut();
		}
	});
});
function save_google_form() {
	var formData1 = $("form#google_form").serialize();
	$.ajax( {
		type :'post',
		url  :'engine/ajax/controller.php?mod=newsmap',
		data :formData1,
		beforeSend: function( ) {
			ShowLoading();
		}, complete: function( ) {
			HideLoading();
		}, success: function( result ) {
			DLEalert( result, 'Bilgilendirme' );
			$("#google_settings").slideUp( 1000 );
		}
	});
}
function save_yandex_form() {
	var formData2 = $("form#yandex_form").serialize();
	console.log( formData2 );
	$.ajax( {
		type :'post',
		url  :'engine/ajax/controller.php?mod=newsmap',
		data :formData2,
		beforeSend: function( ) {
			ShowLoading();
		}, complete: function( ) {
			HideLoading();
		}, success: function( result ) {
			DLEalert( result, 'Bilgilendirme' );
			$("#yandex_settings").slideUp( 1000 );
		}
	});
}
</script>
<div class="row">
	<div class="col-md-12">
		<input type="hidden" name="action" value="create-google">
		<div class="panel panel-default">
			<div class="panel-heading">
				<b>Google Haber Haritası</b>
				<div class="heading-elements">
					<ul class="icons-list">
						<li>
							<a href="{$PHP_SELF}?mod=newsmap"><i class="fa fa-home"></i> Ana Sayfa</a>
						</li>
						<li>
							<a href="javascript:ShowOrHide('cron_info');"><i class="fa fa-calendar"></i> Cron</a>
						</li>
						<li>
							<a href="javascript:ShowOrHide('google_settings');"><i class="fa fa-wrench"></i> Ayarlar</a>
						</li>
					</ul>
				</div>
			</div>
			<div class="panel-body">
				<div id="cron_info">
					Site haritanızı Cron ile güncellemek için kontrol panelinizden CronJob tanımlaması yapmalısınız. Kullanacağınız kod aşağıda verilmiştir. Kendi sitenize göre düzenlemesini yapmalısınız!<br /><br />
					<pre><code class="xml">cd {$root_dir}/; php -f cron.php newsmap >> newsmap.log</code></pre><br />
					Gerekli düzenleme ve cron tanımlamaları için ayrıntılı bilgilere <a href="http://forum.dle.net.tr/gelistiriciler/sistem-ve-moduller/630-dle-cron-kullanimi.html" target="_blank"><b>buradan</b></a> ulaşabilirsiniz.<br />
					<hr />
					Otomatik kurulum harici olarak gereken düzenlemeler:
					<br>
					Dosya: <b>.htaccess</b> <br>
					Bul:
					<pre>RewriteRule ^sitemap.xml$ uploads/sitemap.xml [L]</pre>
					<br>
					Altına Ekle:
<pre># News SiteMap
RewriteRule ^newsmap-google.xml$ uploads/newsmap-google.xml [L]
RewriteRule ^newsmap-yandex.xml$ uploads/newsmap-yandex.xml [L]</pre>
<br><br>
Dosya: <b>cron.php</b>
<br>
Bul:
<br>
<pre>} elseif($cronmode == "optimize") {</pre>
<br>
Üstüne Ekle:
<br>
<pre>
// News Sitemap
		} else if ( $cronmode == "newsmap" ) {
			include_once ROOT_DIR . "/engine/modules/newsmap.cron.php";
// News Sitemap
</pre>
<br>
Düzenlemelerini yapmanız gerekmektedir. Aksi halde periyodik olarak sitemap oluşturulmaz ve oluşuturulmuş olana erişilemez.
<br><br><br>
				</div>
				<form action="" id="google_form" method="post" class="systemsettings">
					<div id="google_settings" class="table-responsive">
						<table class="table">
							<tr>
								<td class="col-xs-10 col-sm-6 col-md-8 white-line">
									<h6 class="media-heading text-semibold">Sitenizin dili:</h6>
									<span class="text-muted text-size-small hidden-xs">Sitenizdeki haberleri yazdığınız dil. Dil kodları hakkında bilgi almak için <a href="http://www.loc.gov/standards/iso639-2/php/code_list.php">tıklayın</a> (ISO 639-1)</span>
								</td>
								<td class="col-xs-6 col-sm-6 col-md-5 white-line">
									<input style="width:100%;" name="google[lang]" value="{$nset['google_lang']}" type="text" class="form-control">
								</td>
							</tr>
							<tr>
								<td class="col-xs-10 col-sm-6 col-md-8">
									<h6 class="media-heading text-semibold">GMT Zaman dilimi:</h6>
									<span class="text-muted text-size-small hidden-xs">Sunucu kaynaklı zaman ayalarına daha kolay müdahele edebilmek için GMT dilimini manuel olarak girebilirsiniz. İlk olarak bir harita oluşturup, içerisindeki haberlerin tarihini kontrol ederek doğru tarih için GMT dilimini yazabilirsiniz.</span>
								</td>
								<td class="col-xs-6 col-sm-6 col-md-5">
									<input style="width:100%;" name="google[gmt]" value="{$nset['google_gmt']}" type="text" class="form-control">
								</td>
							</tr>
							<tr>
								<td class="col-xs-6 col-sm-6 col-md-7">
									<h6 class="media-heading text-semibold">Haber erişim tipi:</h6>
									<span class="text-muted text-size-small hidden-xs">Sitenizdeki haberleri, kullanıcıların okuyabilmesi için üyelik/abonelik yönteminiz. Eğer haberleriniz, kayıt olmadan görüntülenemiyorsa, "Üyelik" seçiniz.</span>
								</td>
								<td class="col-xs-6 col-sm-6 col-md-5">
HTML;
									echo makeDropDown( array( "0" => "Gösterme", "1" => "Göster", "2" => "Gizle" ), "google[show_access]", $nset['google_show_access'], "google_show_access" );
echo <<< HTML
									<div id="google_reg_div">
HTML;
									echo makeDropDown( array( "Subscription" => "Abonelik", "Registration" => "Üyelik" ), "google[access]", $nset['google_access'] );
echo <<< HTML
									</div>
								</td>
							</tr>
							<tr>
								<td class="col-xs-6 col-sm-6 col-md-7">
									<h6 class="media-heading text-semibold">Haber türü:</h6>
									<span class="text-muted text-size-small hidden-xs">
										<ul>
											<li><b>PressRelease (görünür):</b> (Basın Bildirisi) Resmi bir basın bildirisi.</li>
											<li><b>Satire (görünür):</b> (Hiciv) Öğretici amaçla, ele aldığı konuyla alay eden bir makale.</li>
											<li><b>Blog (görünür):</b> Blog'da veya blog biçiminde yayınlanan bir makale.</li>
											<li><b>OpEd:</b> (Serbest Kürsü) Özellikle sitenizin serbest kürsü bölümünden gelen, görüşlere dayanan makale.</li>
											<li><b>Opinion:</b> (Görüş) Serbest kürsü sayfasında yer almayan, görüşlere dayanan diğer makaleler (ör. incelemeler, röportajlar vb.).</li>
											<li><b>UserGenerated:</b> (Kullanıcı Tarafından Oluşturulan) Kullanıcı tarafından oluşturulmuş, haber değeri taşıyan ve sitenizdeki editörler tarafından gözden geçirilmiş içerik.</li>
										</ul>
									</span>
								</td>
								<td class="col-xs-2 col-md-3 settingstd">
HTML;
								echo makeDropDown( array( "0" => "Gösterme", "1" => "Göster", "2" => "Gizle" ), "google[show_genres]", $nset['google_show_genres'], "google_show_genres" );
echo <<< HTML
									<div id="google_genres_div">
HTML;
										echo makeMultiSelect( array(
											"UserGenerated" => "UserGenerated",
											"PressRelease" => "PressRelease",
											"Satire" => "Satire",
											"Blog" => "Blog",
											"OpEd" => "OpEd",
											"Opinion" => "Opinion",
										), 'google[genres]', $google_genres );
echo <<< HTML
									</div>
								</td>
							</tr>
							<tr>
								<td class="col-xs-10 col-sm-6 col-md-8 white-line">
									<h6 class="media-heading text-semibold">Kaynak adı:</h6>
									<span class="text-muted text-size-small hidden-xs">Haber kaynağı için bir başlık girebilirsiniz</span>
								</td>
								<td class="col-xs-6 col-sm-6 col-md-5 white-line">
									<input style="width:100%;" name="google[prefix]" value="{$nset['google_prefix']}" type="text" class="form-control">
								</td>
							</tr>
							<tr>
								<td class="col-xs-6 col-sm-6 col-md-7">
									<h6 class="media-heading text-semibold">Haber anahtar kelimleri:</h6>
									<span class="text-muted text-size-small hidden-xs">Haberler için göstermek istediğiniz anahtar kelimeler, etiketlerinizi ya da anahtar kelimelerinizi kullanabilirsiniz.</span>
								</td>
								<td class="col-xs-6 col-sm-6 col-md-5">
HTML;
								echo makeDropDown( array( "tags" => "Etiketleri kullan", "keywords" => "Anahtar kelimeleri kullan" ), "google[keys]", $nset['google_keys'], "google_keys" );
echo <<< HTML
								</td>
							</tr>

							<tr>
								<td class="col-xs-6 col-sm-6 col-md-7">
									<h6 class="media-heading text-semibold">Kategorileri dahil et:</h6>
									<span class="text-muted text-size-small hidden-xs">Tümünü seçerseniz; Tüm kategoriler otomatik olarak seçilir. Eğer diğer seçeneği seçerseniz, kategoriler için tek tek ayarlama yaparak site haritasına dahil etmek zorunda kalacaksınız.</span>
								</td>
								<td class="col-xs-6 col-sm-6 col-md-5">
HTML;
									echo makeDropDown( array( "1" => "Tümünü", "0" => "Sadece seçilenleri" ), "google[use_cat]", $nset['google_use_cat'], "google_use_cat" );
echo <<< HTML
								</td>
							</tr>

							<tr>
								<td class="col-xs-6 col-sm-6 col-md-7">
									<h6 class="media-heading text-semibold">Makale eklendiğinde otomatik güncelle:</h6>
									<span class="text-muted text-size-small hidden-xs">Sitenize makale eklendiğinde, site haritası otomatik olarak güncellenir.</span>
								</td>
								<td class="col-xs-6 col-sm-6 col-md-5">
HTML;
									echo makeDropDown( array( "0" => "Güncelleme yapma", "1" => "Admin panelinden eklendiğinde", "2" => "Site panelinden eklendiğinde", "3" => "Her iki panelden eklendiğinde" ), "google[auto_addupdate]", $nset['google_auto_addupdate'], "google_auto_addupdate" );
echo <<< HTML
								</td>
							</tr>

							<tr>
								<td class="col-xs-6 col-sm-6 col-md-7">
									<h6 class="media-heading text-semibold">Makale düzenlendiğinde otomatik güncelle:</h6>
									<span class="text-muted text-size-small hidden-xs">Sitenizde makale düzenlediğinizde (silme de dahil), site haritası otomatik olarak güncellenir.</span>
								</td>
								<td class="col-xs-6 col-sm-6 col-md-5">
HTML;
									echo makeDropDown( array( "0" => "Güncelleme yapma", "1" => "Admin panelinden eklendiğinde", "2" => "Site panelinden eklendiğinde", "3" => "Her iki panelden eklendiğinde" ), "google[auto_editupdate]", $nset['google_auto_editupdate'], "google_auto_editupdate" );
echo <<< HTML

								</td>
							</tr>

							<tr>
								<td class="col-xs-6 col-sm-6 col-md-7">
									<h6 class="media-heading text-semibold">Ping ayarlaması:</h6>
									<span class="text-muted text-size-small hidden-xs">Site haritasını belirlediğiniz ayarlamaya göre pingler.</span>
								</td>
								<td class="col-xs-6 col-sm-6 col-md-5">
HTML;
									echo makeDropDown( array( "0" => "Otomatik Pingleme yapma", "1" => "Makale eklendiğinde, güncellenirse", "2" => "Makale düzenlendiğinde, güncellenirse", "3" => "Eklendiğinde veya düzenlendiğinde, güncellenirse" ), "google[auto_ping]", $nset['google_auto_ping'], "google_auto_ping" );
echo <<< HTML

								</td>
							</tr>
							<!--tr>
								<td class="col-xs-6 col-sm-6 col-md-7">
									<h6 class="media-heading text-semibold">XML Görünümü:</h6>
								</td>
								<td class="col-xs-6 col-sm-6 col-md-5">
<pre><code class="xml">&#60;url&#62;
	&#60;loc&#62;http://siteniz.com/1-ornek-haber.html&#60;/loc&#62;
	&#60;news:news&#62;
		&#60;news:publication&#62;
			&#60;news:name&#62;Haber:Örnek Haber&#60;/news:name&#62;
			&#60;news:language&#62;tr&#60;/news:language&#62;
		&#60;/news:publication&#62;
		&#60;news:publication_date&#62;2015-03-03T10:51:53+02:00&#60;/news:publication_date&#62;
		&#60;news:title&#62;Örnek Haber&#60;/news:title&#62;
		&#60;news:keywords/&#62;
	&#60;/news:news&#62;
&#60;/url&#62;</code></pre>
								</td>
							</tr-->
						</table>
						<hr />
						<input type="hidden" name="google[user_hash]" value="{$dle_login_hash}" />
						<button class="btn bg-teal btn-raised" onclick="save_google_form(); return false;"><i class="fa fa-floppy-o position-left"></i>{$lang['user_save']}</button>
					</div>
				</form>
				<b>Son oluşturulan harita</b><hr />
HTML;
					if ( ! @file_exists(ROOT_DIR. "/uploads/newsmap-google.xml") ){
						echo "<p>Henüz haber haritası oluşturulmamış</p>";
					} else {
						$file_date = date("d.m.Y H:i", filectime( ROOT_DIR. "/uploads/newsmap-google.xml" ) );
						echo "<b>".$file_date."</b> ".$lang['google_map_info'];
						if ( $config['allow_alt_url'] ) {
							$map_link = $config['http_home_url'] . "newsmap-google.xml";
							echo " <a class=\"list\" href=\"" . $map_link . "\" target=\"_blank\">" . $config['http_home_url'] . "newsmap-google.xml</a>";
						} else {
							$map_link = $config['http_home_url']."uploads/newsmap-google.xml";
							echo " <a class=\"list\" href=\"" . $map_link . "\" target=\"_blank\">" . $config['http_home_url'] . "uploads/newsmap-google.xml</a>";
						}
						$map_link = base64_encode(urlencode($map_link));
						//echo "<br /><br /><input id=\"sendbutton\" name=\"sendbutton\" type=\"button\" class=\"btn btn-gray\" value=\"{$lang['google_map_send']}\" /><div id=\"send_result\" class=\"padded\"></div>";
					}
echo <<< HTML
			</div>
			<div class="panel-footer">
				<input type="button" onclick="window.location='?mod=newsmap&amp;action=create-google';" class="btn btn-primary btn-raised" value="Oluştur" />
				<input type="button" onclick="window.location='?mod=newsmap&amp;action=create-google&amp;ping=yes';" class="btn btn-success btn-raised" value="Oluştur ve Pingle" />
			</div>
		</div>
	</div>
</div>
HTML;

echofooter();
?>
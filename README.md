# News Sitemap
<img src="https://img.shields.io/badge/dle-13.0+-007dad.svg"> <img src="https://img.shields.io/badge/lang-tr,en-ce600f.svg">
Google News için site haritası oluşturabilirsiniz

Dosya: .htaccess

Bul:
`RewriteRule ^sitemap.xml$ uploads/sitemap.xml [L]`

Altına Ekle:
```
# News Sitemap
RewriteRule ^newsmap-google.xml$ uploads/newsmap-google.xml [L]
RewriteRule ^newsmap-yandex.xml$ uploads/newsmap-yandex.xml [L]
```

Dosya: cron.php

Bul:
`} elseif($cronmode == "optimize") {`

Üstüne Ekle:
```
// News Sitemap
		} else if ( $cronmode == "newsmap" ) {
			include_once ROOT_DIR . "/engine/modules/newsmap.cron.php";
// News Sitemap
```


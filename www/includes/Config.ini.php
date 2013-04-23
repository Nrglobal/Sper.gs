<?php
/*
 * Config.ini.php
 * 
 * Copyright (c) 2012 Andrew Jordan
 * 
 * Permission is hereby granted, free of charge, to any person obtaining 
 * a copy of this software and associated documentation files (the 
 * "Software"), to deal in the Software without restriction, including 
 * without limitation the rights to use, copy, modify, merge, publish, 
 * distribute, sublicense, and/or sell copies of the Software, and to 
 * permit persons to whom the Software is furnished to do so, subject to 
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be 
 * included in all copies or substantial portions of the Software. 
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, 
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF 
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. 
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY 
 * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, 
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE 
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

$time = explode(' ', microtime());
$start = $time[1] + $time[0];

$root_path_array = explode("/", dirname(__FILE__));
$root_path = "";

for($i=0; $i<sizeof($root_path_array)-1; $i++){
	$root_path .= $root_path_array[$i]."/";
}
$root_path = substr($root_path, 0, strlen($root_path)-1);

set_include_path(get_include_path().PATH_SEPARATOR.$root_path."/includes/smarty");

##Database Settings
define("DATABASE_TYPE", "mysql");
define("DATABASE_HOST", "");
define("DATABASE_NAME", "");
define("DATABASE_USER", "");
define("DATABASE_PASS", "");

##Sitewide Settings
define("DOMAIN", "localhost");
define("SITENAME", "Sper.gs");

##Search Settings
define("SEARCHD_PATH", "/usr/bin/searchd");
define("INDEXER_PATH", "/usr/bin/indexer");
define("SPHINX_HOST", "localhost");
define("SPHINX_PORT", 3312);
define("SPHINX_CONFIG", "/var/www/Sper.gs/sphinx/sphinx.conf");

##Security Settings
define("SALT_SIZE", 16);
define("USE_SSL", FALSE);
define("SITE_KEY", "<put lots of random letters, numbers, and symobls here>"); //DO NOT CHANGE ONCE SITE GOES LIVE
define("HASH_INTERATIONS", 1000); //DO NOT CHANGE ONCE SITE GOES LIVE
$allowed_tags =  array("\<b\>","\<\/b\>",
					 "\<strong\>","\<\/strong\>",
					 "\<i\>","\<\/i\>",
					 "\<em\>","\<\/em\>",
					 "\<u\>","\<\/u\>",
					 "\<strike\>","\<\/strike\>",
					 "\<s\>","\<\/s\>",
					 "\<del\>","\<\/del\>",
					 "\<spoiler\>", "\<\/spoiler\>",
					  "\<spoiler caption=\"(.+)\"\>",
					 "\<br\>", "\<br \/\>",
					 "\<br\/\>",
					 "\<pre\>", "\<\/pre\>",
					 /*"\<quote msgid=\"t,(\d+),(\d+)@(\d+)\"\>(.+)?<img src=\"https?:\/\/i\.(minus|imgur)\.com\/([A-z0-9_\.-]+)\.(jpg|gif|png|jpeg)\"( \/)?>", "<\/quote>",
					 */"\<quote msgid=\"t,(\d+),(\d+)@(\d+)\"\>", "\<\/quote\>",
					 "\<img src=\"https?:\/\/i\.(minus|imgur)\.com\/([A-Za-z0-9_.-]+)\"( \/)?\>");
					 
$allowed_tags = array("search" => $allowed_tags,
					  "replace" => array(
								"<b>", "</b>",
								"<strong>", "</strong>",
								"<i>", "</i>",
								"<em>", "</em>",
								"<u>", "</u>",
								"<strike>", "</strike>",
								"<s>", "</s>",
								"<del>", "</del>",
								"<span class=\"spoiler_closed\" id=\"s0_<!--\$i-->\"><span class=\"spoiler_on_close\"><a class=\"caption\" href=\"#\"><b>&lt;spoiler /&gt;</b></a></span><span class=\"spoiler_on_open\"><a class=\"caption\" href=\"#\">&lt;spoiler&gt;</a>", "<a class=\"caption\" href=\"#\">&lt;/spoiler&gt;</a></span></span><script type=\"text/javascript\">\$(document).ready(function(){llmlSpoiler($(\"#s0_<!--\$i-->\"));});</script>",
								"<span class=\"spoiler_closed\" id=\"s0_<!--\$i-->\"><span class=\"spoiler_on_close\"><a class=\"caption\" href=\"#\"><b>&lt;$1 /&gt;</b></a></span><span class=\"spoiler_on_open\"><a class=\"caption\" href=\"#\">&lt;$1 &gt;</a>",
								"<br/>", "<br />", "<br>",
								"<pre>", "</pre>",
								/*"<div class=\"quoted-message\" msgid=\"t,$1,$2@$3\">$4<div class=\"imgs\"><a target=\"_blank\" imgsrc=\"http://i.$5.com/$6s.$7\" href=\"http://i.$5.com/$6.$7\"><span class=\"img-placeholder\" style=\"width:90px;height:90px\" id=\"u0_<!--\$i-->\"></span><script type=\"text/javascript\">onDOMContentLoaded(function(){new ImageLoader($(\"u0_<!--\$i-->\"), \"http:\/\/i.$5.com\/$6s.$7\", 90, 90)})</script></a><div style=\"clear:both\"></div></div>", "</quote>",
								"<div class=\"quoted-message\" msgid=\"t,$1,$2@$3\">", "</div>",*/
								"<quote msgid=\"t,$1,$2@$3\">", "</quote>",
								"<a href=\"http://i.$1.com/$2\" target=\"_blank\"><img src=\"http://i.$1.com/$2\" /></a>"	
							)
						);

##Authentication Cookie Names
define("AUTH_KEY1", "sessionid");
define("AUTH_KEY2", "sessionkey");

##Template Engine Variables
define("TEMPLATE_DIR", $root_path."/templates");
define("TEMPLATE_CACHE", $root_path."/includes/smarty/cache");
define("TEMPLATE_CONFIG", $root_path."/includes/smarty/configs");
define("TEMPLATE_COMPILE", $root_path."/includes/smarty/templates_c");
define("DATE_FORMAT", "%m/%d/%Y %H:%M:%S");

?>
<?php

function printHeader() {
    global $edit;
    global $column;
    global $columns;
    global $_GET;

    header("Content-Type: text/html; charset=UTF-8");

    print("<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"\n");
    print("\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n");

    print("<html>\n");
    print("<head>\n");
    print("<title>SimpleRSS</title>\n");
    print("<link rel=\"stylesheet\" href=\"simplerss.css\""
	    . "type=\"text/css\" />\n");
    print("<meta http-equiv=\"Content-Type\" "
	    . "content=\"text/html; charset=UTF-8\" />\n");
    print("</head>\n\n");

    print("<body>\n\n");

    print("<div class=\"box\">\n\n");
    print("<div class=\"header\">\n\n");

    print("<a href=\"");
    print(htmlentities(getNewUrl($column)));
    print("\">\n");
    print("<span class=\"title1\">");
    print("Simple");
    print("</span>");
    print("<span class=\"title2\">");
    print("RSS");
    print("</span>");
    print("</a>\n\n");

    print("<span class=\"headerLinks\">\n");
    print("&nbsp;&nbsp;&nbsp;&nbsp;[\n");
    print("<a href=\"");
    print(htmlentities(getNewUrl($column)) . "&refreshall=1");
    print("\">Refresh all</a>\n");
    print("|\n");
    if ($_GET["list"] == "1") {
	print("<a href=\"");
	print(htmlentities(getNewUrl($column)));
	print("\">Normal view</a>\n");
    } else {
	print("<a href=\"");
	print(htmlentities(getNewUrl($column)) . "&list=1");
	print("\">List feeds</a>\n");
    }
    print("|\n");
    print("<a href=\"" . getBaseUrl() . "\">Clear</a>\n");
    print("|\n");
    print("<a href=\"about.php\">About/Help</a>\n");
    print("]\n");
    print("</span>\n\n");

    print("<form class=\"addfeedForm\" method=\"get\" action=\".\">\n");
    print("<input type=\"text\" name=\"addfeed\" size=\"50\"/>\n");
    print("<input type=\"submit\" value=\"Add feed\" />\n");

    if (sizeof($column) > 0) {
	$i = 0;

	foreach ($column as $col) {
	    if (is_array($col)) {
		print("<input type=\"hidden\" name=\"column$i\" ");
		print("value=\"");
		foreach ($col as $url) {
		    if ($url["url"] != "") {
			print(htmlentities($url["url"]));
			print(" " . $url["maxItems"]);
			print("\n");
		    }
		}
		print("\"/>\n");
		$i++;
	    }
	}

	print("</form>\n\n");
    }
    print("</div>\n\n");
    print("<div class=\"tmp\" />");
}

function printFooter() {
    print("<div class=\"tmp\" />");
    print("<span class=\"manage\">");
    echo gmdate("Y\-m\-d\TH\:i\:s\+00\:00", time());
    print("&nbsp;&nbsp;");
    print("<a href=\"mailto:kms@skontorp.net\">&lt;kms@skontorp.net&gt;</a><br>\n");
    print("</span>\n\n");
    print("</div>\n\n");
    print("</body>\n\n");
    print("</html>\n");
}

function getBaseUrl() {
    $baseUrl = "http://";
    $baseUrl .= $_SERVER["SERVER_NAME"];

    if ($_SERVER["SERVER_PORT"] != "80") {
	$baseUrl .= ":" . $_SERVER["SERVER_PORT"];
    }

    $baseUrl .= eregi_replace("(.*)index.php", "\\1", $_SERVER["SCRIPT_NAME"]);

    return $baseUrl;
}

function getNewUrl($column) {
    global $_SERVER;
    global $columns;

    $newUrl = getBaseUrl();

    $newUrl .= "?";

    if (is_array($column)) {
	$i = 0;

	foreach ($column as $col) {
	    if (is_array($col)) {
		$newUrl .= "column$i=";
		foreach ($col as $url) {
		    if ($url["url"] != "") {
			$newUrl .= rawurlencode($url["url"]);
			$newUrl .= rawurlencode(" ") . rawurlencode($url["maxItems"]);
			$newUrl .= rawurlencode("\n");
		    }
		}
		$newUrl .= "&";
		$i++;
	    }
	}
    }

    return $newUrl;
}

function parseColumns($get) {
    $i = 0;

    while (isSet($get["column$i"])) {
	$tok = strtok($get["column$i"], "\n");

	$urls = null;
	$j = 0;
	/* Get all URLs and add them to an array. */
	while ($tok) {
	    $urlSplitted = split(" ", $tok);

	    $column[$i][$j]["url"] = trim($urlSplitted[0]);
	    if (is_numeric(trim($urlSplitted[1])) && trim($urlSplitted[1] >= 0)) {
		$column[$i][$j]["maxItems"] = trim($urlSplitted[1]);
	    } else {
		$column[$i][$j]["maxItems"] = $DEFAULT_MAXITEMS;
	    }
	    $j++;
	    $tok = strtok("\n");
	}
	$i++;
    }
    return $column;
}

function getFeed($feed, $x, $y) {
    global $_GET;
    global $CACHE_DIR;
    global $VERSION;
    global $DEFAULT_MAX_AGE;

    $urlMD5 = md5($feed["url"]);

    $cacheFile = "$CACHE_DIR/$urlMD5";

    if (isset($_GET["refreshx"]) && isset($_GET["refreshy"]) 
	    && ($_GET["refreshx"] == $x) 
	    && ($_GET["refreshy"] == $y)) {
	$cacheAge = 65536;
    } else if ($_GET["refreshall"] == "1") {
	$cacheAge = 65536;
    } else {
	if (file_exists($cacheFile)) {
	    $cacheFileStat = stat($cacheFile);
	    $cacheAge = (time() - $cacheFileStat["ctime"]);
	} else {
	    $cacheAge = 65536;
	}
    }

    if ($cacheAge < $DEFAULT_MAX_AGE) {
	$handle = fopen($cacheFile, "rb");
	$data["data"] = fread($handle, filesize($cacheFile));
	$data["age"] = $cacheAge;
    } else {
	$ch = curl_init();
	//curl_setopt($ch, CURLOPT_PROXY, 
	//"http://home.tmvs.vgs.no:3128/");
	curl_setopt($ch, CURLOPT_URL, $feed["url"]);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, "SimpleRSS/$VERSION http://skontorp.net/simplerss/");
	curl_setopt($ch, CURLOPT_ENCODING, "");
	$data["data"] = curl_exec($ch);
	curl_close($ch);
	$tmpname = tempnam($CACHE_DIR, "simplerss-");
	$tmpfile = fopen($tmpname, "wb");
	fwrite($tmpfile, $data["data"]);
	fclose($tmpfile);
	rename($tmpname, $cacheFile);

	$data["age"] = 0;
    }

    return $data;
}
?>

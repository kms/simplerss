<?php
# simplerss
# Simple RSS viewer.
#
# (c) Karl-Martin Skontorp <kms@skontorp.net> ~ http://picofarad.org/
# Licensed under the GNU GPL 2.0 or later.

include("xmlParser-0.3.php");
include("feedParser-0.5.php");
include("functions.php");

ini_set("zlib.output_compression", "on");

$DEFAULT_MAXITEMS = 5;
$DEFAULT_MAX_AGE = 1800;
$CACHE_DIR = "cache";

$ALPHA = array("a", "b", "c", "d", "e", "f", "g", "h", 
	"i", "j", "k", "l", "m", "n", "o", "p", "q", 
	"r", "s", "t", "u", "v", "w", "x", "y", "z");

$VERSION = "0.9";

$column = parseColumns($_GET);

if ($_GET["list"] == "1") {
    printHeader();
    print("<p>");
    foreach ($column as $col) {
	if (is_array($col)) {
	    foreach($col as $feed) {
		print($feed["url"] . "<br/>");
	    }
	}
    }
    print("</p>");
    printFooter();
    exit;
}

if (isset($_GET["addfeed"])) {
    $i = sizeof($column[0]);

    $column[0][$i]["url"] = $_GET["addfeed"];
    $column[0][$i]["maxItems"] = $DEFAULT_MAXITEMS;

    header("Location: " . getNewUrl($column));
    exit;
}

if (sizeof($column) < 1) {
    printHeader();
    printf("<p>Please add feeds!</p>\n");
    printFooter();
    exit;
}

printHeader();

/* Calculate width of individual columns. */
$COLUMN_WIDTH = round(100 / sizeof($column));

foreach ($column as $x => $col) {

    if (is_array($col)) {
	print("<div class=\"blog\" style=\"width: $COLUMN_WIDTH%;\">\n");

	/* Iterate through elements. */
	foreach($col as $y => $feed) {

	    $data = getFeed($feed, $x, $y);

	    $p = new feedParser();
	    $info = $p->parseFeed($data["data"]);

	    if (sizeof($info["item"]) > 0) {
		print("<div class=\"blogTitle\">");
		if ($info["channel"]["link"] != "") {
		    print("<a href=\"" . $info["channel"]["link"]
			    . "\">"
			    . $info["channel"]["title"] . "</a>\n");
		} else {
		    print($info["channel"]["title"] . "\n");
		}
		print("<a href=\"" . $feed["url"] . "\">\n");
		print("<img src=\"xml-tiny.png\" /></a></div>");
		print("<div class=\"manage\">(");
		print($x . $ALPHA[$y] . ":&nbsp;\n");

		print("<a href=\"");
		$tmp = $column;
		if (!is_array($tmp[$x-1])) {
		    $tmp[$x-1] = array();
		}
		array_push($tmp[$x-1], $tmp[$x][$y]);
		unset($tmp[$x][$y]);
		print(htmlentities(getNewUrl($tmp)));
		print("\">L</a>&nbsp;\n");

		print("<a href=\"");
		$tmp = $column;
		if (!is_array($tmp[$x+1])) {
		    $tmp[$x+1] = array();
		}
		array_push($tmp[$x+1], $tmp[$x][$y]);
		unset($tmp[$x][$y]);
		print(htmlentities(getNewUrl($tmp)));
		print("\">R</a>&nbsp;\n");

		if (isset($tmp[$x][$y-1])) {
		    print("<a href=\"");
		    $tmp = $column;
		    $tmpFeed = $tmp[$x][$y];
		    $tmp[$x][$y] = $tmp[$x][$y-1];
		    $tmp[$x][$y-1] = $tmpFeed;
		    print(htmlentities(getNewUrl($tmp)));
		    print("\">U</a>&nbsp;\n");
		} else {
		    print("U&nbsp;\n");;
		}

		if (isset($tmp[$x][$y+1])) {
		    print("<a href=\"");
		    $tmp = $column;
		    $tmpFeed = $tmp[$x][$y];
		    $tmp[$x][$y] = $tmp[$x][$y+1];
		    $tmp[$x][$y+1] = $tmpFeed;
		    print(htmlentities(getNewUrl($tmp)));
		    print("\">D</a>)&nbsp;\n");
		} else {
		    print("D)&nbsp;\n");;
		}

		print("(<a href=\"");
		$tmp = $column;
		unset($tmp[$x][$y]);
		print(htmlentities(getNewUrl($tmp)));
		print("\">Del</a>)&nbsp;\n");

		print("(" . $feed["maxItems"] . ":&nbsp;");
		print("<a href=\"");
		$tmp = $column;
		$tmp[$x][$y]["maxItems"]++;
		print(htmlentities(getNewUrl($tmp)));
		print("\">+</a>/");
		if ($feed["maxItems"] > 0) {
		    print("<a href=\"");
		    $tmp = $column;
		    $tmp[$x][$y]["maxItems"]--;
		    print(htmlentities(getNewUrl($tmp)));
		    print("\">-</a>)\n");
		} else {
		    print("-)\n");
		}

		print("&nbsp;(");
		if ($data["age"] < ($DEFAULT_MAX_AGE * 0.1)) {
		    print("<span class=\"cacheAge10\">");
		} else if ($data["age"] < ($DEFAULT_MAX_AGE * 0.9)) {
		    print("<span class=\"cacheAge90\">");
		} else {
		    print("<span class=\"cacheAge100\">");
		}

		print($data["age"]);
		print(" s.:&nbsp;");
		print("</span>\n");
		print("<a href=\"");
		print(htmlentities(getNewUrl($tmp)) 
			. "&amp;refreshx=$x&amp;refreshy=$y");
		print("\">Ref</a>)\n");

		print("</div>\n");

		print("<ul>\n");

		$k = 0;

		foreach ($info["item"] as $val) {
		    $k++;

		    if ($k > $feed["maxItems"]) {
			break;
		    }

		    if($val["link"] != "") {
			print("<li><a href=\"" 
				. $val["link"] . "\">" 
				. $val["title"] . "</a>"
				. "</li>\n");
		    } else {
			print("<li>" . $val["title"]
				. "</li>\n");
		    }
		}

		print("</ul>\n");
	    } else {
		$l = sizeof($errors);
		$errors[$l]["url"] = $feed["url"];
		$errors[$l]["x"] = $x;
		$errors[$l]["y"] = $y;
	    }
	}
	print("</div>");
    }
}

if (sizeof($errors) > 0) {
    print("<div class=\"errors\">\n");
    print("<p>\n");
    print("<b>Feeds with errors:</b>\n");
    print("</p>\n");
    print("<ul>\n");
    foreach ($errors as $error) {
	print("<li>" . $error["url"]);
	print("<span class=\"manage\">");
	print("&nbsp;(<a class=\"manage\" href=\"");
	$tmp = $column;
	unset($tmp[$error["x"]][$error["y"]]);
	print(htmlentities(getNewUrl($tmp)));
	print("\">Del.</a>)");
	print("</span>");
	print("</li>\n");
    }
    print("</ul>\n");
    print("</div>\n");
}

printFooter();

?>

<?php
if ($_GET['dir']) {
	$sourceDir = filter_var( $_GET['dir'], FILTER_SANITIZE_URL );
	#$sourceDir = $_GET['dir'];
}
require_once('rss.inc.php');

$defaultDesc = "a show from the past";

header("Content-type: application/xml");
print "<?xml version='1.0' encoding='UTF-8'?>\n";
print "<?xml-stylesheet title='XSL_formatting' type='text/xsl' href='/includes/rss.xsl'?>\n";

$linkpre = "http://pictures.zz9-za.com/";
$server = explode('/',$_SERVER['PHP_SELF']);
$serverName = ucfirst($server[1]);
$linkpre .= $server[1]."/";
$title = "Shows Re-Viewed" . (isset($sourceDir) ? " - $sourceDir" : "");
$imageLoc = "images/buffy_f.jpg";

$dirs = scandir(".");
$afiles = array();

foreach ($dirs as $dir) {
	if ($dir[0] != "." and $dir != "images" and $dir != "src" and is_dir($dir)) {
		if ((isset($sourceDir) and $dir == $sourceDir) or (!isset($sourceDir))) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) != False) {
					if ((strlen($file) > 3) and (!is_dir($file)) and (is_validfile($file))) {
						$mtime = filemtime($dir."/".$file);
						$afiles[] = array( "filename" => $dir."/".$file, "mtime" => $mtime );
					}
				}
			}
		}
	}
}

usort( $afiles, "sortByAge" );
$afiles = array_reverse( $afiles );

$mostRecent = $afiles[0]["mtime"];
$pubDate = date("r", time());
if ($mostRecent != NULL) {
	$pubDate=date("r", $mostRecent);
}
?>
<rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">
<channel>
<title><?php echo $title; ?></title>
<link><?php echo $linkpre; ?></link>
<description>Shows - Re-runs are the best way to go</description>
<generator>PHP</generator>
<copyright>None</copyright>
<image>
	<url><?php print $linkpre . $imageLoc ?></url>
	<title><?php echo $title; ?></title>
	<link><?php echo $linkpre?></link>
	<width>200</width>
	<height>176</height>
</image>
<itunes:image href="<?php print $linkpre . $imageLoc ?>"><?php print $linkpre . $imageLoc ?></itunes:image>
<itunes:subtitle>A podcast of old(er) TV shows</itunes:subtitle>
<itunes:author>zz9-za.com</itunes:author>
<itunes:summary>Re-runs of older TV shows, published straight to your iTunes</itunes:summary>
<itunes:category text="TV &amp; Film">
</itunes:category>
<itunes:explicit>no</itunes:explicit>
<ttl>60</ttl>
<?php
print "<pubDate>$pubDate</pubDate>\n\n";

$itemFormat = False;

foreach ($afiles as $d) {
	$filename = $d["filename"];
	$loc = strrpos($filename,".");
	if ($loc != false) {
		$name = substr($filename,0,$loc);
	}
	$ifo = $name.".ifo";
	$desc = $defaultDesc;
	$title = $name;

	if (file_exists($ifo)) {
		$lines = file($ifo);
		if (sizeof($lines) > 0) { $title = trim($lines[0]); }
		if (sizeof($lines) > 1) { 
			array_shift($lines);
			$desc = trim(join(" ",$lines)); }
	}
	
	$pubdate=date("r", $d["mtime"]);

	$fsize = filesize($filename);

	$f = explode("/", $filename );
	$f[1] = rawurlencode($f[1]);
	$filename = implode("/", $f );
	
	print "<item>".($itemFormat ? "\n\t" : "");
	print "<title>".htmlentities($title)."</title>".($itemFormat ? "\n\t" : "");
	print "<link>$linkpre".$filename."</link>".($itemFormat ?  "\n\t" : "");
	print "<pubDate>$pubdate</pubDate>".($itemFormat ? "\n\t" : "");
	print "<description>$desc</description>".($itemFormat ? "\n\t" : "");
	print "<guid>$linkpre".$filename."</guid>".($itemFormat ? "\n\t" : "");
	print "<enclosure url='$linkpre".$filename."' size='".$fsize."' type='video/x-m4v'/>".($itemFormat ? "\n" : "");
	print "</item>\n";
}

?>
</channel>
</rss>

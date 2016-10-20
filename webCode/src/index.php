<?php
require_once('rss.inc.php');

header("Content-type: application/xml");
print "<?xml version='1.0' encoding='UTF-8'?>\n";
print "<?xml-stylesheet title='XSL_formatting' type='text/xsl' href='/includes/rss.xsl'?>\n";

$linkpre = "http://pictures.zz9-za.com/";
$imageLoc = $linkpre."shows/images/buffy_f.jpg";
$server = explode('/',$_SERVER['PHP_SELF']);
array_shift($server);array_pop($server); # one off the front, and the rear
$linkpre .= implode("/", $server) . "/";
#$linkpre .= $server[1]."/";
$title = "Shows Re-Viewed";
#$imageLoc = "images/buffy_f.jpg";

$dir = ".";
$afiles = array();

if (is_dir($dir)) {
  if ($dh = opendir($dir)) {
    while (($file = readdir($dh)) != False) {
      if ((strlen($file) > 3) and (!is_dir($file)) and (is_validfile($file))) {
	$mtime = filemtime($file);
	$afiles[] = array( "filename" => $file, "mtime" => $mtime );
      }
    }
  }
}

usort( $afiles, "sortByAge");
$afiles = array_reverse($afiles);

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
	<url><?php print $imageLoc ?></url>
	<title><?php echo $title; ?></title>
	<link><?php echo $linkpre?></link>
	<width>200</width>
	<height>176</height>
</image>
<itunes:image href="<?php print $imageLoc ?>"><?php print $imageLoc ?></itunes:image>
<itunes:subtitle>A podcast of old(er) TV shows</itunes:subtitle>
<itunes:author>zz9-za.com</itunes:author>
<itunes:summary>Re-runs of older TV shows, published straight to your iTunes</itunes:summary>
<itunes:category text="TV &amp; Film">
</itunes:category>
<itunes:explicit>no</itunes:explicit>
<?php
print "<pubDate>$pubDate</pubDate>\n\n";

foreach ($afiles as $d) {
	$filename = $d["filename"];
	$loc = strrpos($filename,".");
	if ($loc != false) {
		$name = substr($filename,0,$loc);
	}
	$ifo = $name.".ifo";
	$desc = "show of the week";
	$title = $name;

	if (file_exists($ifo)) {
		$lines = file($ifo);
		if (sizeof($lines) > 0) { $title = trim($lines[0]); }
		if (sizeof($lines) > 1) { 
			array_shift($lines);
			$desc = trim(join(" ",$lines)); }
	}
	
	$pubdate=date("r", $d["mtime"]);
	
	print "<item>\n\t<title>".htmlentities($title)."</title>\n";
	print "\t<link>$linkpre".rawurlencode($filename)."</link>\n";
	print "\t<pubDate>$pubdate</pubDate>\n";
	print "\t<description>$desc</description>\n";
	print "\t<guid>$linkpre".rawurlencode($filename)."</guid>\n";
#	print "\t<enclosure url='$linkpre".urlencode($d)."' size='".filesize($d)."' type='text/html'/>\n";
	print "\t<enclosure url='$linkpre".rawurlencode($filename)."' size='".filesize($filename)."' type='video/x-m4v'/>\n";
	print "</item>\n";
}

?>
</channel>
</rss>

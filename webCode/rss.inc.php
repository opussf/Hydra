<?php

$ignore_file_types = array("PHP", "SWP", "PDF", "XSL", "XML", "CSV", "IFO", "PY", "TXT");

$mime_types = array(
	"m4a"=> "audio/mp4",
	"mp3"=> "audio/mpeg",
	"m4v"=> "video/x-m4v",
	"mp4"=> "video/x-mp4",
);

function get_filetype($fname) {
  return end(explode('.', $fname));
}

function is_validfile($fname) {
  global $ignore_file_types;
  $ext = strtoupper(get_filetype($fname));
  foreach($ignore_file_types as $ignoretype) {
    if (strcasecmp($ext, $ignoretype) == 0) { return False; }
  }
  return True;
}
function is_validdir($dname) {
}

function sortByAge( $a, $b ) {
	if ($a["mtime"] == $b["mtime"]) {
		return 0;
	}
	return ($a["mtime"] < $b["mtime"]) ? -1 : 1;
}

?>

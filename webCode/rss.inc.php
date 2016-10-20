<?php

$ignore_file_types = array("PHP", "SWP", "PDF", "XSL", "XML", "CSV", "IFO", "PY", "TXT");

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

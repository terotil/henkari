<?php
/****************************************************************************
 * Copyright 2002, Tero Tilus <tero@tilus.net>
 *
 * This php function is distributed under the terms of the GNU General
 * Public License <http://www.gnu.org/licenses/gpl.html> and WITHOUT
 * ANY WARRANTY.
 **/

// Get the contents of file.  Empty if file doesn't exist or it
// cannot be read.
function filecontents($file) {
  // Assume file
  if ( file_exists($file) ) {
    $handle = fopen($file, 'r');
    if ( ! $handle ) { return ''; }
    $content = fread($handle, filesize($file));
    fclose($handle);
    return $content;
  }
  // File failed, assume url (fallback in case of disabled fopen wrappers)
  preg_match_all('/(http:\/\/)([^:]+:.+@)?([^\/]+)\/?(.*)/sm', $file, $matches);
  if ( count($matches[0]) > 0 ) {
    $auth = $matches[2][0];
    $host = $matches[3][0];
    $uri = $matches[4][0];
    if ( $host != '' ) {
      $fp = fsockopen ($host, 80, $errno, $errstr, 15);
      if (!$fp) {
	return "$errstr ($errno)\nHost: $host";
      } else {
	fputs($fp, "GET /$uri HTTP/1.0\r\nHost: $host\r\n");
	if ( $auth ) {
	  $auth = substr($auth, 0, strlen($auth)-1); // Chop '@' away
	  fputs($fp, "Authorization: Basic ".base64_encode($auth)."\r\n");
	}
	fputs($fp, "\r\n");
	$retval = '';
	while (!feof($fp)) {
	  $retval .= fgets($fp,256);
	}
	fclose ($fp);
	list($headers, $body) = split("\r\n\r\n", $retval, 2);
	return $body;
      }
    }
  }
  // Failed, return false
  return false;
}

?>

<?php
/****************************************************************************
 * Copyright 2002, Tero Tilus <tero@tilus.net>
 *
 * This php class is distributed under the terms of the GNU General
 * Public License <http://www.gnu.org/licenses/gpl.html> and WITHOUT
 * ANY WARRANTY.
 **/
require_once('lib/htmlHandler.class.php');
require_once('lib/CGI.class.php');

/****************************************************************************
 * "Skimpy" handler for html-files.  If any '<!-- BREAK -->' -markers
 * are found from content, page is shown up to nth marker, where n is
 * given as parameter.  Content after last marker is always shown
 * (i.e. single marker has no actual effect).  If a part of page is
 * hidden (due markers and small enough n) "show me more" and "show
 * all" -buttons are appended to content.
 **/
class skimpHtmlHandler extends htmlHandler {
  /**************************************************************************
   * Show page
   **/
  function showPage() {
    // First do whatever htmlHandler would do.
    parent::showPage();
    // Then start to look for markers.
    $ui = UI::getInstance();
    $splitcontent = split('<!-- *BREAK *-->',
			  $ui->tpl->varvals[$ui->contentVariable]);
    $partcount = count($splitcontent);
    if ( $partcount > 2 ) {
      // There could be hidden parts (first and last are always shown)
      $showparts = CGI::safeLookupGP('showparts', 0);
      if ( $showparts === 'all' ) {
	// Show all parts
	$showparts = $partcount;
      }
      if ( $showparts < 0 ) {
	// Negative count of shown parts not allowed.
	$showparts = 0; 
      }
      if ( $showparts < $partcount-2 ) {
	// There are hidden parts.  Count of parts that could be
	// hidden is $partcount-2 because first and last part are
	// always shown.  If $showparts is less than that there are
	// hidden parts.
	$lastpart = array_pop($splitcontent);
	for ( $i=0; $i<$partcount-$showparts-2; $i++ ) {
	  // Count of hidden parts to remove = $partcount-$showparts-2,
	  // which is guaranteed to be positive (look at the condition
	  // of the previous if).
	  array_pop($splitcontent);
	}
	// Append 'more' and 'all' buttons
	$splitcontent[] = sprintf('<table><tr><td><form action="{this_uri}"><input type="hidden" name="showparts" value="%d" /><input type="submit" value="%s" /></form></td><td><form action="{this_uri}"><input type="hidden" name="showparts" value="all" /><input type="submit" value="%s" /></form></td></tr></table>',
				  $showparts+1, 'More', 'All');
	// Append last part
	$splitcontent[] = $lastpart;
	// Set new retouched content
	$ui->tpl->varvals[$ui->contentVariable] = join("\n", $splitcontent);
      }
    }
  }
}

?>

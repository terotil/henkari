<?php
/****************************************************************************
 * Copyright 2002, Tero Tilus <tero@tilus.net>
 *
 * This php class is distributed under the terms of the GNU General
 * Public License <http://www.gnu.org/licenses/gpl.html> and WITHOUT
 * ANY WARRANTY.
 **/
require_once('lib/CGI.class.php');

/****************************************************************************
 * Class for managing Henkari configuration
 **/
class henkariConfig {

  function henkariConfig() {

    //////////////////////
    // DEFAULT SETTINGS //
    
    // Webroot of Henkari.  Physical path to start traversal from.
    $this->start_with = './root';

    // Is asisHandler forced?
    $this->force_asis = (CGI::safeLookupGP('asis', 'no') == 'yes');
  
    // Custom or general request configuration
    if ( file_exists('./config_request.php') ) {
      include('./config_request.php');
    } else {
      // Requested path (complete request URI with 'root' removed),
      // stored in PATH_INFO or 'path' variable
      $this->req_path = CGI::safeLookup('HTTP_SERVER_VARS', 'PATH_INFO', false);
      if ( ! $this->req_path ) {
	$this->req_path = CGI::safeLookupGP('path', false);
      }
      $this->req_path = rawurldecode($this->req_path);
  
      // URI corresponding to $this->start_with.  Query
      // {$this->root}/some/file maps to {$this->start_with}/some/file
      // We can't use $HTTP_SERVER_VARS['SCRIPT_NAME']; because it can
      // be fooled with simple internal url rewrite.
      $ru = rawurldecode(CGI::safeLookup('HTTP_SERVER_VARS', 'REQUEST_URI', ''));
      $rru = strrev($ru);
      $pi = $this->req_path;
      if ( $pi != '' ) {
	$rpi = strrev($pi); $lpi = strlen($pi);
	// /this/henkari/index.php/path/is/a_request?uri=to&a=file
	// |----------------------- $ru -------------------------|
	//                        |------ $pi -----|
	// |-------- strstr($rru, $rpi) -----------|
	$this->root = substr($ru, 0, strlen(strstr($rru, $rpi)) - $lpi);
      } else {
	// FIXME: Only remove possible parameters, there's no trailing slash.
	// No path info, just remove possible parameters.
	//      $this->root = ereg_replace('^([^?]+)/?\??.*', '\1', $ru);
	$this->root = CGI::safeLookup('HTTP_SERVER_VARS', 'SCRIPT_NAME');
      }
      // Fix braindead /////// -addresses.
      $this->root = ereg_replace('/+', '/', $this->root);
    }
  
    // Split path to components.  Remove empty components and '..'
    // traversals.
    $spath = split('/', $this->req_path);
    $spath = array_filter($spath, create_function('$s', 'return (strlen($s)!=0);')); 
    $spath = array_filter($spath, create_function('$s', 'return !($s=="..");'));
    $this->splitpath = $spath;

    // Date format used in {date_modified}, see
    // http://www.php.net/date for documentation on the formatting
    // function.
    $this->date_format = 'j.n.Y H:i';

    // Labeling options
    $this->label = null;
    $this->label->mainpage = 'Main page';
    $this->mainpagelabel =& $this->label->mainpage; // Backwards compability
    // Parts to be replaced from names when creating default label.
    // Replacements are done in the order they appear in array.  Keys
    // are eregs that are to be replaced with the corresponding
    // values.  See http://www.php.net/ereg-replace
    $this->label->eregs = 
      array('^[0-9]+_' => '',
	    '\.[^.]*$' => '',
	    '_' => '&nbsp;');

    // Name of the default file to show when directory is requested.
    $this->indexfile = 'index.html';

    // Name of template file
    $this->templatefile = '_template.html';
  
    // Cache
    //  see additional setting at then end of this method
    $this->cache = false;
    $this->cachedir = './pagecache';

    // Do we want to record navigational information to generate
    // navigational elements (TOC, Path and Menu).
    $this->collect_navdata = true;

    // TOC rendering
    /* Variables that can be used in $this->TOC->(hilite)cell 
     *   $indent_level = Indentation level of current TOC item
     *   $indent       = Indet string ($this->TOC->indent repeated $indent_level times)
     *   $url          = URL associated to current TOC item
     *   $title        = Title text of TOC item 
     **/
    $this->TOC = null;
    $this->TOC->start  = "<div class=\"TOCListing\">\n";
    $this->TOC->end    = "</div>\n";
    $this->TOC->shift  = "";
    $this->TOC->unshift= "";
    $this->TOC->indent = "&nbsp;&nbsp;";
    $this->TOC->cell   = '<span class="TOCItem"><a href="$url">$title</a></span><br />'."\n";
    $this->TOC->hilitecell = '<span class="selectedTOCItem"><a href="$url">$title</a></span><br />'."\n";

    // Path rendering
    $this->path = null;
    $this->path->start = '<span class="PathListing">';
    $this->path->item  = ' / <a href="$uri">$label</a>';
    $this->path->end   = '</span>';
    $this->path->files = 'noindex'; // none, noindex, all

    // Menu rendering
    $this->menu = null;
    $this->menu->start = "<div class=\"DirectoryListing\">\n";
    $this->menu->item  = '<a href="$uri">$label</a><br />'."\n";
    $this->menu->end   = "</div>\n";
    $this->menu->up_prefix = '&lt;&lt;&nbsp;';
    $this->menu->generate_always = false;

    // Title rendering
    $this->title = null;
    $this->title->separator = ' / ';

    // Java sketchpad thingies
    $this->javaskethcpad = null;
    $this->javaskethcpad->codebase = '/jsp';
    $this->javasketchpad->template = CGI::safeLookupGP('ejsc_template', 'local');

    // htmlHandler
    $this->htmlHandler = null;
    $this->htmlHandler->editbutton = 'Edit this page';
    $this->htmlHandler->savebutton = 'Save';
    $this->htmlHandler->donebutton = 'Show this page';
    $this->htmlHandler->rows = 30;
    $this->htmlHandler->cols = 72;

    // Hide files matching any of the patterns listed
    $this->hideMatching = array();

    ////////////////////
    // LOCAL SETTINGS //
    if ( file_exists('./config.php') ) {
      include('./config.php');
    }

    // See if we are going to do any non-cacheable operations
    switch ( CGI::safeLookupGP('action', 'view') ) {
    case 'save':
    case 'edit':
      $this->cache = false;
    default:
    }

    if ( $this->cache ) {
      // We do caching.  Build the name for the cache file
      $this->cachefile = $this->cachedir . '/henkaricache'. 
	str_replace('/','#',$this->req_path) . '.' .
	dechex(crc32(CGI::getSerialized('get').
		     CGI::getSerialized('post')));
    }

  }

  function Get($variable) {
    if ( isset( $this->$variable ) ) {
      return $this->$variable;
    }
    return null;
  }

  function Set($variable, $value) { 
    $this->$variable = $value;
  }

}

?>

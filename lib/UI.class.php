<?php
/****************************************************************************
 * Copyright 2002, Tero Tilus <tero@tilus.net>
 *
 * This php class is distributed under the terms of the GNU
 * General Public License <http://www.gnu.org/licenses/gpl.html> and
 * WITHOUT ANY WARRANTY.
 **/

require_once('phplib/template.inc');
require_once('phplib/menu.inc');
require_once('lib/html.php');
require_once('lib/filecontents.function.php');

/****************************************************************************
 * Dynamically folding TOC (tree menu) for Henkari
 **/
class henkariTOC extends Menu {
  var $classname = 'henkariTOC';
  var $nomain = true;

  function stripTrailingSlash($s) {
    $l = strlen($s);
    while ( $s[$l-1] == '/' ) {
      $s = substr($s, 0, $l-1);
      $l--;
    } 
    return $s;
  }

  function henkariTOC(&$menuitems) {
    // FIXME: $PHP_SELF must be tweaked.  Otherwise url rewrite in
    // Apache conf most propably blows up TOC.
    global $PHP_SELF;
    global $config;
    $PHP_SELF = $config->root . '/' . join('/',$config->splitpath) . '/';
    $minlen = 1000; $this->rootnode = '/';
    foreach ( $menuitems as $uri => $txt ) {
      if ( strpos($uri, '|') === false ) {
	// Normal case
	$path = $this->stripTrailingSlash($uri);
      } else {
	// Embedded url or pseudo item, etc.
	list($path, $uri)   = split("\|", $uri);
      }
      $this->urlmap[$uri] = $path;
      $this->item[$path]  = array('title' => $txt);
      // Find root node
      if ( ($l = strlen($path)) < $minlen ) { $minlen = $l; $this->rootnode = $path; }
    }
    $this->setup();
    $this->rootlevel = substr_count($this->rootnode, "/") + 1;
  }
  /* private: normalize current menu position. 
   *
   * I had to override this to achieve desired functionality.
   * Trailing slash must be stripped from path before comparation.
   */
  function normalize_pos($pos) {
    if ( isset($this->urlmap[$pos]) ) {
      return $this->urlmap[$pos];
    }
    $m = $this->stripTrailingSlash($pos);
    while( strlen($m) > 1 ) {
      if (isset($this->urlmap[$m])) { return $this->urlmap[$m]; } // Check mapping with and without trailing slash
      $m .= '/';
      if (isset($this->urlmap[$m])) { return $this->urlmap[$m]; }
      $m = dirname($m);
    }
    return $this->urlmap[$m];
  }
  function start_menu() { global $config; return $config->TOC->start; }
  function end_menu()   { global $config; return $config->TOC->end;   }
  function shift_in($oldlevel, $level) { 
    global $config; 
    return str_repeat($config->TOC->shift, $level-$oldlevel); 
  }
  function shift_out($oldlevel, $level) { 
    global $config; 
    return str_repeat($config->TOC->unshift, $oldlevel-$level); 
  }
  function get_cell($n, $level, $class = "") {
    global $config;
    if ( ($indent_level = (($level - $this->rootlevel) - 1)) < 0 ) {
      $indent_level = 0;
    }
    $indent = str_repeat($config->TOC->indent, $indent_level);
    $hilite = ($this->visible[$n] == $this->map);
    $url    = $this->item[$this->visible[$n]]["url"];
    $title  = $this->item[$this->visible[$n]]["title"];
    $celltemplate = 
      addcslashes($hilite ? $config->TOC->hilitecell : $config->TOC->cell,"\"\n");
    return $indent . eval("return \"$celltemplate\";");
  }
}

/****************************************************************************
 * Mapping between keys and labels
 **/
class labelMapper {
  // Constructor
  function labelMapper() {
    $this->names = array();
  }
  // Retrieve label of $key
  function getLabel($key) {
    if ( isset($this->names[$key]) ) {
      return $this->names[$key];
    }
    if ( isset($this->names["$key/"]) ) {
      return $this->names["$key/"];
    }
    return $this->defaultLabel($key);
  }
  // Set label of $key to $label
  function setLabel($key, $label) {
    $this->names[$key] = $label;
  }
  // Return default label for $key
  function defaultLabel($key) {
    return $key;
  }
}

/****************************************************************************
 * Mapping between uris and labels in Henkari
 * FIXME: Replacement array should be moved from config to labelMapper
 **/
class henkariLabelMapper extends labelMapper {
  function henkariLabelMapper() {
    $this->labelMapper();
  }
  // Return default label for $key
  function defaultLabel($key) {
    $label  = trim(basename($key));
    global  $config; 
    // Apply regexps
    foreach ( $config->label->eregs as $pattern => $replacement ) {
      $label = ereg_replace($pattern, $replacement, $label);
    }
    // Capitalize first
    $tmplabel = $label;
    $label  = strtoupper($tmplabel[0]); 
    $label .= substr($tmplabel, 1, strlen($tmplabel)-1);
    return $label;
  }
}

/****************************************************************************
 * User interface for Henkari
 *
 * Assumed template variables: title, content
 **/
class UI {

  // Constructor
  function UI() {
    // Template
    $this->tpl = new Template();
    $this->contentVariable = 'content';
    $this->tpl->set_var('page', '{'.$this->contentVariable.'}');
    $this->tpl->set_var($this->contentVariable, '');
    global $config; $this->tpl->set_var('entrypoint_uri', $config->Get('root').'/');
    // Navigation
    $this->naviPath = array();
    $this->naviTOC  = array();
    $this->naviMenu = array();
    $this->labelMapper = new henkariLabelMapper();
    // Response to send to browser
    $this->responseData = false;
    $this->responseHeaders = array();
  }

  // Returns an (singleton) instance of UI.
  function &getInstance() {
    static $Instance = null;
    if ( $Instance === null ) {
      $Instance = new UI();
    }
    return $Instance;
  }

  /* Not available with nested templates
  // Set template file
  function setTemplateFile($filename) {
    // FIXME: Nested templates!!!
    $this->tpl->set_file('page', $filename);
  }
  */

  // Set template.  Templates are nested.  The first template gets to
  // be the root.  Only title and body from subsequent templates are
  // used (to replace {title} and {content} from current template) and
  // everyting else is discarded.
  function setTemplate($template, $override=false) {
    $tmp = $this->tpl->get_var('page');
    if ( ($tmp == '{content}') || ($tmp == '') || ($override) ) {
      // Root level template (no previous template, or override forced)
      $tmp = $template;
    } else {
      // There allready is a template.  Use body of given template to
      // replace {content} from current template.
      $tmp = str_replace('{content}', 
			 html_extract_tag_contents($template,'body'), 
			 $tmp);
      $this->pageTitle(html_extract_tag_contents($template,'title'), true);
    }
    $this->tpl->set_var('page', $tmp);
  }

  // Set header
  function addHeader($h) {
    if ( strpos($h, 'HTTP/') === 0 ) {
      // If header is a statuscode it must be the first header.
      array_unshift($this->responseHeaders, $h);
    } else {
      array_push($this->responseHeaders, $h);
    }
  }

  // Lookup page from cache, return true on hit and false on miss.
  function cacheLookup() {
    global $config;
    if ( $config->cache && file_exists($config->cachefile) ) {
      $this->responseHeaders = split("\n", filecontents($config->cachefile.'.headers'));
      $this->responseData = filecontents($config->cachefile);
      // Cache lookup succeeded.
      return true;
    }
    // Cache lookup failed or caching off.
    return false;
  }

  function cacheWrite() {
    global $config;
    if ( $config->cache ) {
      // Write header file
      $of = @fopen($config->cachefile.'.headers', 'w');
      if ( !$of || (fwrite($of, join("\n",$this->responseHeaders)) == -1) ) {
	return -1;
      }
      fclose($of);
      // Write data file
      $of = @fopen($config->cachefile, 'w');
      if ( !$of || (fwrite($of, $this->responseData) == -1) ) {
	return -2;
      }
      fclose($of);
    }
    return 0;
  }

  // Send header information and page data
  function sendPage() {
    if ( ! $this->responseData ) {
      // Default contents to menu
      if ( $this->tpl->get_var($this->contentVariable) == '' ) {
	$this->tpl->set_var($this->contentVariable, '{menu}');
      }
      // Render navigation
      $this->renderPath();
      $this->renderTOC();
      $this->renderMenu();
      // Retrieve contents from template
      $this->tpl->parse('output', 'page');
      $this->responseData = $this->tpl->get('output');
    }
    // Generate ETag
    $this->addHeader(sprintf('ETag: "%s"', md5($this->responseData)));
    // Write page to cache
    $this->cacheWrite();
    // Send headers
    if ( is_array($this->responseHeaders) ) {
      foreach ( $this->responseHeaders as $h) {
	header($h);
      }
    }
    // Send page
    echo $this->responseData;
  }

  // Append $str to template variable.  Default is to append $str to
  // page content.
  function appendToPage($str, $var=false) {
    if ( ! $var ) {
      $var = $this->contentVariable;
    }
    $this->tpl->set_var($var, $this->tpl->get_var($var) . $str);
  }

  // Set page title
  function pageTitle($str, $append=false) {
    if ( $append ) {
      global $config;
      $this->tpl->set_var('title', $this->tpl->get_var('title') . $config->title->separator . $str);
    } else {
      $this->tpl->set_var('title', $str);
    }
  }

  // Check if template contains $variable as undefined variable.
  function isUndefined($variable) {
    foreach ( array('file', 'varkeys') as $variablearray ) {
      foreach ( $this->tpl->{$variablearray} as $existingvar => $val ) {
	$undefined = $this->tpl->get_undefined($existingvar);
	if ( is_array($undefined) && 
	     in_array($variable, $undefined) ) {
	  return true;
	}
      }
    }
    return false;
  }

  // Path (traversed path from main page), TOC (tree structure
  // containing sitemap) and Menu (available transitions) functions.
  function appendToPath($uri, $label) {
    $this->naviPath[$uri] = $label;
  }

  function appendToTOC($uri, $label) {
    $this->naviTOC[$uri] = $label;
  }

  function appendToMenu($uri, $label) {
    $this->naviMenu[$uri] = $label;
  }

  function renderPath() {
    if ( $this->isUndefined("path") &&
         count($this->naviPath)>0 ) {
      global $config;
      // Render only when this will be used and isn't defined yet
      $this->appendToPage($config->path->start, 'path');
      foreach ( $this->naviPath as $uri => $label ) {
	// Quote template to be double quoted string in eval.
	// $config->path->item may have $uri and $label, which get
	// expanded during evaluation.
	$template = addcslashes($config->path->item, "\n\"");
	$this->appendToPage(eval("return \"$template\";"), 'path');
      }
      $this->appendToPage($config->path->end, 'path');
    }
  }

  function renderTOC() {
    if ( $this->isUndefined("TOC") &&
         count($this->naviTOC)>0 ) {
      // Render only when this will be used and isn't defined yet
      global $config;
      $this->appendToTOC($config->root, 
			 $this->labelMapper->getLabel($config->root));
      $henkaritoc = new henkariTOC($this->naviTOC);
      $this->appendToPage($henkaritoc->get(), 'TOC');
    }
  }

  function sortMenu() {
    // Alphabetic order (by keys), just like TOC
    $keys = array_keys($this->naviMenu);
    sort($keys);
    $sorted = array();
    foreach ( $keys as $key ) {
      $sorted[$key] = $this->naviMenu[$key];
    }
    $this->naviMenu = $sorted;
  }

  function renderMenu() {
    // Render only when this will be used and isn't defined yet
    global $config;
    if ( $this->isUndefined("menu") &&
	 count($this->naviMenu)>0 ) {
      // Sort
      $this->sortMenu();
      // Render
      $this->appendToPage($config->menu->start, 'menu');
      foreach ( $this->naviMenu as $uri => $label ) {
	// Quote template to be double quoted string in eval.
	// $config->menu->item may have $uri and $label, which get
	// expanded during evaluation.
	$template = addcslashes($config->menu->item, "\n\"");
	$this->appendToPage(eval("return \"$template\";"), 'menu');
      }
      $this->appendToPage($config->menu->end, 'menu');
    }
  }
}
?>

<?php
/****************************************************************************
 * Copyright 2002, Tero Tilus <tero@tilus.net>
 *
 * This php class is distributed under the terms of the GNU General
 * Public License <http://www.gnu.org/licenses/gpl.html> and WITHOUT
 * ANY WARRANTY.
 **/
require_once('lib/UI.class.php');
require_once('lib/authFactory.class.php');
require_once('lib/filecontents.function.php');

/****************************************************************************
 * Abstract base class for handlers
 *
 * Assumes
 *  $config->date_format, formatting string for http://www.php.net/date
 **/
class abstractHandler {
  // Constructor
  // $root_uri_, base for uri-references created by handler.
  // $filename_, actual file (or dir) which is to be dealed with.
  // $patharray_, request path (split on '/') "into" file/dir.
  function abstractHandler($root_uri_, $filename_, $patharray_) {
    global $config;
    $this->isDirectory = false;
    $this->tmpdir = '/tmp';
    $this->setRootURI($root_uri_);
    $this->setFilename($filename_);
    $this->setPathArray($patharray_);
    $this->tmpdir_exists = false;
    $ui =& UI::getInstance();
    // Automatic date of last modification
    if ( file_exists($this->filename) ) {
      $this->lastModified(filemtime($this->filename));
    } else {
      $this->lastModified(time());
    }
    // Automatic {base_uri}
    $ui->tpl->set_var('base_uri', dirname($this->root_uri));
    // Automatic {this_uri}
    $ui->tpl->set_var('this_uri', $this->root_uri);
  }

  // Setting properties
  function setPathArray($patharray_) { $this->patharray = $patharray_; }
  function setFilename($filename_)   { $this->filename = $filename_;   }
  function setRootURI($root_uri_)    { $this->root_uri = $root_uri_;   }

  function lastModified($timestamp) {
    global $config;
    $ui =& UI::getInstance();
    $ui->tpl->set_var('date_modified', date($config->date_format, $timestamp));
    $ui->addHeader('Last-Modified: ' . gmdate('D, d M Y H:i:s', $timestamp) .
		   ' GMT');
    $this->lastModified = $timestamp;
  }

  // Create temporary directory.  Return true on success, false on
  // failure.
  function createTmpDir() {
    // We must not do this twice.
    if ( ! $this->tmpdir_exists ) {
      $this->deftmpdir = $this->tmpdir;
      // Get unique name
      $this->tmpdir = tempnam($this->tmpdir, 'henkari_');
      // Remove file created by tempnam
      system('rm -f '.escapeshellarg($this->tmpdir));
      // Create directory with same name
      $this->tmpdir_exists = mkdir($this->tmpdir, 0700);
      return $this->tmpdir_exists;
    }
  }

  // Remove tmpdir with all it's contents.
  function removeTmpDir() {
    if ( $this->tmpdir_exists ) {
      exec('rm -Rf '.escapeshellarg($this->tmpdir));
      // Resume the old tmpdir
      $this->tmpdir = $this->deftmpdir;
    }
  }

  // Get the contents of file.  Empty if file doesn't exist or it
  // cannot be read.
  function filecontents($file) {
    return filecontents($file);
  }

  // Handler function (to be overriden)
  function handle() {
  }

  // Parse nested page queries
  function parseNested($page) {
    // {#include http://tavi.auvila.jyu.fi/}
    // {#include hepnanne.html}
    $reg = "/\{\#include ([^}]*)\}/sm";
    //  Haetaan #include -tageja
    preg_match_all($reg, $page, $matches);
    //  Jos löytyi, niin korvataan vastaavilla tiedostoilla
    if ( count($matches[0]) > 0 ) {
      foreach ( array_keys($matches[0]) as $mkey ) {
	// Include command string.  This is replaced
	$include_command = $matches[0][$mkey];
	// Contents of included page.
	$included_page   = $this->filecontents($matches[1][$mkey]);
	// Check if we can find body-tag.  If found only contents of
	// body-tag are used.
	if ( '' == ($included_body = html_extract_tag_contents($included_page, 'body')) ) {
	  $included_body = $included_page;
	}
	// Replace include command with corresponding included content.
	$page = str_replace($include_command, $included_body, $page);
      }
    }
    return $page;
  }

  function writePathEntry() {
    global $config;
    // Do we "do" navdata?
    if ( $config->collect_navdata ) {
      $ui =& UI::getInstance();
      if ( ( $config->path->files == 'all' ) ||
	   ( ($config->path->files == 'noindex') && 
	     (basename($this->filename) != $config->indexfile)) ) {
	// Write path entry if configuration allows it
	$ui->appendToPath($this->root_uri.($this->isDirectory?'/':''), 
			  $ui->labelMapper->getLabel($this->root_uri));
      }
    }
  }

  // Authenticate user
  function authenticateUser() {
    $af =& authFactory::getInstance();
    $au =& $af->getAuth();
    return $au->authenticateUser();
  }
}
?>

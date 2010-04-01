<?php
/****************************************************************************
 * Copyright 2002, Tero Tilus <tero@tilus.net>
 *
 * This php class is distributed under the terms of the GNU General
 * Public License <http://www.gnu.org/licenses/gpl.html> and WITHOUT
 * ANY WARRANTY.

TODO:
 - Possibility to register directory handler or handlers for spesific files.

 **/
require_once('lib/MIME.class.php');
require_once('lib/dirHandler.class.php');
require_once('lib/asisHandler.class.php');
require_once('lib/errorHandler.class.php');
require_once('lib/abstractHenkariFactory.class.php');

/****************************************************************************
 * Instantiator of handlers
 **/
class handlerFactory extends abstractHenkariFactory {

  // Constructor
  function handlerFactory() {
    $this->abstractHenkariFactory();
    $this->handlers_bymime = array();
    $this->dirhandlers = array();
    $this->handlers_byname = array();
  }

  // Register handler
  function _registerHandler($arrayname, $key, $classname, $classfile) {
    if ( $classfile == '' ) {
      // Generate default classfile from classname
      $classfile = $this->getDefaultClassfile($classname);
    }
    // Set handler in handlers-array.
    $handler_array =&  $this->$arrayname;
    $handler_array[$key] = 
      array('classname' => $classname,
	    'classfile' => $classfile);
  }

  // Register new file handler for mime type
  function registerHandler($mime, $classname, $classfile='') {
    $this->_registerHandler('handlers_bymime', $mime, $classname, $classfile);
  }

  // Register handler for files matching pattern
  function registerHandlerByName($pattern, $classname, $classfile='') {
    $this->_registerHandler('handlers_byname', $pattern, $classname, $classfile);
  }

  // Register handler for directories matching pattern
  function registerDirHandler($pattern, $classname, $classfile='') {
    $this->_registerHandler('dirhandlers', $pattern, $classname, $classfile);
  }

  // From keys of given array find first pattern matching to given
  // string and return the corresponding value.
  function &findMatch(&$array, &$string) {
    foreach ( $array as $pattern => $v ) {
      if ( ereg($pattern, $string) ) {
	return $array[$pattern];
      }
    }
    return false;
  }

  // Obtain instances of abstractHandler's subclasses by calling
  // handlerFactory::createHandler();
  function &createHandler($root_uri_, $filename_, $patharray_) {
    global $config;

    if (is_dir($filename_)) {
      // Directories
      if ( $handler =& $this->findMatch($this->dirhandlers, $filename_) ) {
	// Matching handler found
	include($handler['classfile']);
	return new $handler['classname']($root_uri_, $filename_, $patharray_);
      }
      // Fallback to default directory handler
      return new dirHandler($root_uri_, $filename_, $patharray_);

    } elseif (is_file($filename_)) {
      // Files
      // Try to find matching handler
      if ( $handler =& $this->findMatch($this->handlers_byname, $filename_) ) {
	// Matching handler found
	include($handler['classfile']);
	return new $handler['classname']($root_uri_, $filename_, $patharray_);
      }

      // Fallback to mime-based handler selection
      // Get mimetype
      $mime =& MIME::getInstance();
      $mimetype = $mime->getMimeTypeByFilename($filename_);
      if ( isset($this->handlers_bymime[$mimetype]) && (!$config->force_asis) ) {
	// Use registered handler
	include($this->handlers_bymime[$mimetype]['classfile']);
	return new $this->handlers_bymime[$mimetype]['classname']($root_uri_, $filename_, $patharray_);
      } else {
	// No registered handler or forced as-is.  Use as-is file handler.
	return new asisHandler($root_uri_, $filename_, $patharray_);
      }

    } else {

      // Everytihing else produces error
      return new errorHandler($root_uri_, $filename_, $patharray_);

    }
  }

  // Returns an (singleton) instance of handlerFactory.
  function &getInstance() {
    static $Instance = null;
    if ( $Instance === null ) {
      $Instance = new handlerFactory();
    }
    return $Instance;
  }
}
?>

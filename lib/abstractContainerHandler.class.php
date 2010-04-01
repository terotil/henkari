<?php
/****************************************************************************
 * Copyright 2002, Tero Tilus <tero@tilus.net>
 *
 * This php class is distributed under the terms of the GNU General
 * Public License <http://www.gnu.org/licenses/gpl.html> and WITHOUT
 * ANY WARRANTY.
 **/
require_once('lib/abstractHandler.class.php');

/****************************************************************************
 * Abstract handler for containers like packages
 **/
class abstractContainerHandler extends abstractHandler {

  // Constructor
  function abstractContainerHandler($root_uri_, $filename_, $patharray_) {
    $this->abstractHandler($root_uri_, $filename_, $patharray_);
  }

  // Extract contents of container to $this->tmpdir (to be overriden)
  function extractContents() {
  }

  // Handler
  function handle() {
    // FIXME: Caching of containers' contents could be added here.
    // Just don't remove tmpdirs, keep registry of $this->root_uri ->
    // $this->tmpdir -mappings and compare $this->root_uri to
    // root_uri's in registry.  If match is found, use that, otherwise
    // create new (which is currently done every time).
    if ( $this->createTmpDir() ) {
      $this->extractContents();
      $hf =& handlerFactory::getInstance();
      $h =& $hf->createHandler($this->root_uri, $this->tmpdir, $this->patharray);
      $h->handle();
      $this->removeTmpDir();
    } else {
      $eh = new errorHandler($this->root_uri, $this->tmpdir, $this->patharray);
      $eh->handle("Error creating temporary directory '{$this->tmpdir}'");
    }
  }
}
?>

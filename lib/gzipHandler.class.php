<?php
/****************************************************************************
 * Copyright 2002, Tero Tilus <tero@tilus.net>
 *
 * This php class is distributed under the terms of the GNU General
 * Public License <http://www.gnu.org/licenses/gpl.html> and WITHOUT
 * ANY WARRANTY.
 **/
require_once('lib/abstractContainerHandler.class.php');

/****************************************************************************
 * Handler for gzip compressed files
 **/
class gzipHandler extends abstractContainerHandler {
  function extractContents() {
    // Copy gzip package to temporary directory.
    $target = $this->tmpdir.'/'.basename($this->filename);
    copy($this->filename, $target);
    // Handle copy instead of the original.
    $this->setFilename($target);
    // Decompress
    exec('gunzip '.escapeshellarg($this->filename));
  }
}
?>

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
 * Handler for tar-files
 **/
class tarHandler extends abstractContainerHandler {
  function extractContents() {
    // Directly extract contents of tar to $this->tmpdir
    exec('tar --extract --file '.escapeshellarg($this->filename).
	 ' --directory '.escapeshellarg($this->tmpdir));
  }
}
?>

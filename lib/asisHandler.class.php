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
 * Generic handler for files
 **/
class asisHandler extends abstractHandler {
  // Handler function
  function handle() {
    $mime =& MIME::getInstance();
    $ui =& UI::getInstance();
    $ui->addHeader('Content-type: ' . 
		   $mime->GetMimeTypeByFilename($this->filename));
    $ui->responseData = $this->filecontents($this->filename);
  }
}
?>

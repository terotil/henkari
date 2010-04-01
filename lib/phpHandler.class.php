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
 * Handler for php scripts
 **/
class phpHandler extends abstractHandler {
  function handle() {
    // Tweak php environment variables.
    // Buffer output
    ob_start();

    // Execute php code
    include($this->filename);

    // Append buffer contents to page contents, empty buffer and end
    // buffering.
    $ui =& UI::getInstance();
    $ui->appendToPage(ob_get_contents());
    ob_end_clean();
  }
}

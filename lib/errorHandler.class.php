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
 * Generic handler for errors
 **/
class errorHandler extends abstractHandler {
  function errMsg($msg) {
    return '<p style="color: red"><b>Henkari error:</b><br>' . $msg . '</p>';
  }

  // Handler function
  function handle($error='general') {
    $ui =& UI::getInstance();

    // Try to find out the reason
    if ( $error == 'general' ) {
      if ( ! file_exists($this->filename) ) {
	$error = 'notfound';
      } elseif ( ! is_readable($this->filename) ) {
	$error = 'notreadable';
      }
    }

    switch ( $error ) {
    case 'notfound':
      $ui->addHeader('HTTP/1.0 404 Not Found');
      $ui->appendToPage($this->errMsg("'{$this->root_uri}' not found."));
      break;
    case 'notreadable':
      $ui->addHeader('HTTP/1.0 403 Forbidden');
      $ui->appendToPage($this->errMsg("'{$this->root_uri}' is not readable."));
      break;
    case 'general':
      $ui->addHeader('HTTP/1.0 500 Internal henkari error');
      $ui->appendToPage($this->errMsg("General error processing '{$this->root_uri}'."));
    default:
      $ui->appendToPage($this->errMsg($error));
      break;
    }
  }
}
?>

<?php
/****************************************************************************
 * Copyright 2002, Tero Tilus <tero@tilus.net>
 *
 * This php application is distributed under the terms of the GNU
 * General Public License <http://www.gnu.org/licenses/gpl.html> and
 * WITHOUT ANY WARRANTY.
 **/

require_once('lib/CGI.class.php');
require_once('lib/henkariConfig.class.php');
require_once('lib/handlerFactory.class.php');
require_once('lib/authFactory.class.php');
require_once('lib/UI.class.php');

/****************************************************************************
 * Let's get hands dirty. */

// Load configuration
$config = new henkariConfig();

// Assert proper path.
if ( strlen($config->req_path) < 1 ) {
  // No pathinfo, must add trailing slash.
  header("Location: ".$config->root."/");
  exit;
}

// Instantiate user interface
$ui =& UI::getInstance();
$ui->labelMapper->setLabel($config->root, 
			   $config->mainpagelabel);

// Cache lookup
if ( ! $ui->cacheLookup() ) {

  // Instantiate handler.
  $hf =& handlerFactory::getInstance();
  $h =& $hf->createHandler($config->root, 
			   $config->start_with, 
			   $config->splitpath);
  // Geronimooooooo!
  $h->handle();

} else { 
  // Don't write cache if lookup was successfull.
  $config->cache = false; 
}

// The last chance to override all that has been done
if ( file_exists('./finalize.php') ) {
  include('./finalize.php');
}

// Cache and send 
$ui->sendPage();
?>

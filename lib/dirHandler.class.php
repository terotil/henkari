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
 * Generic handler for directories
 *
 * Assumes
 *   $config->templatefile
 **/
class dirHandler extends abstractHandler {
  // Constructor
  function dirHandler($root_uri_, $filename_, $patharray_) {
    $this->abstractHandler($root_uri_, $filename_, $patharray_);
    $this->isDirectory = true;
  }

  // Handler function
  function handle() {
    global $config;
    $ui =& UI::getInstance();

    // Set per-directory template
    $templatefile = $this->filename . '/' . $config->templatefile;
    if ( file_exists($templatefile) ) {
      $ui->setTemplate($this->filecontents($templatefile));
    }
    // Take a step forward along the path.
    $next = array_shift($this->patharray);
    $generate_menu = $config->menu->generate_always;
    if ( $next === NULL ) {
      // There's no path left to traverse.
      $generate_menu = true;
      if ( file_exists($this->filename.'/'.$config->indexfile) ) {
	// Handle default index file
	$nextroot = '';
	$nextfile = '/'.$config->indexfile;
      } else {
	// No path left and no index given.  Generate index (later).
	$ui->pageTitle($ui->labelMapper->getLabel($this->root_uri), true);
      }
    } else {
      // There was still path left to traverse. 
      $nextroot = "/$next";
      $nextfile = "/$next";
    }

    /* FIXME: Interpretation of dotindex could be separated from
       dirHandler.  Could it be best to create a handler for it?
       Maybe the same for dothenkari too?*/

    // Navigation
    // Retrieve mappings, index entries and hide -definitions from
    // '.index'.  Syntax is
    //   command file           Label
    //   # comment
    //   map     something      Label of something
    //   pseudo  nonexisting    Another label
    //   hide    filenottoshow
    $ui->naviMenu = array(); // Clear menu from previous entries
    if ( file_exists($this->filename.'/.index') ||
	 file_exists($this->filename.'/_index') ) {
      $index  = $this->filecontents($this->filename.'/.index');
      $index .= "\n";
      $index .= $this->filecontents($this->filename.'/_index');
      $pseudoindex = 0;
      $pseudo = array();
      foreach ( split("\n", $index) as $line ) {
	// Skip comments and empty lines
	if ( ($line[0] != '#') && ($line != '')) {
	  list($command, $file, $label) =
	    split("[ \t]+", $line, 3);
	  switch ( $command ) {
	  case 'hide':
	    $config->hideMatching[] = $file;
	    break;
	  case 'pseudo':
	    // FIXME: Ordering of pseudo elements!
	    $ui->appendToTOC($this->root_uri."/zzz_pseudo_$pseudoindex|".$file, $label);
	    $ui->appendToMenu($file, $label);
	    $pseudoindex++;
	    break;
	  case 'map':
	    $ui->labelMapper->setLabel($this->root_uri.'/'.$file, $label);
	    break;
	  }
	}
      }
    }

    // Add current address to path
    $this->writePathEntry();

    // Do we want to generate navdata?
    if ( $config->collect_navdata ) {

      // Generate index from directory listing
      if ( $generate_menu ) {
	// up, next, prev
	if ( ($config->root != $this->root_uri) &&
	     ! in_array('\.\.', $config->hideMatching)) {
	  // Don't show "up" on root level or when hidden
	  $up_uri = dirname($this->root_uri) . '/';
	  $ui->appendToMenu($up_uri, $config->menu->up_prefix.
			    $ui->labelMapper->getLabel($up_uri));
	}
      }

      // Read current directory and list entries
      $d = dir($this->filename);
      while ( $entry = $d->read() ) {
	$show = true;
	// Hide '.', '..', backups, dotfiles and files starting with
	// '_' (M$ FrontPage!) from list.
	if ( ($entry[0] == '.') || ($entry[0] == '_') ||
	     ($entry[strlen($entry)-1] == '~') ) {
	  $show = false;
	} else {
	  // Hide entries matching to any of given hide-patterns
	  reset($config->hideMatching);
	  while ( (list($k,$pattern) = each($config->hideMatching)) &&
		  ($show) ) {
	    if ( ereg($pattern, $entry) ) {
	      $show = false;
	    }
	  }
	}
	// List entry if it's to be shown
	if ( $show ) {
	  // Find out if entry is a dir
	  $entry_file = $this->filename.'/'.$entry; 
	  $postfix = '';
	  if (is_link($entry_file)) {
	    $tmp = $entry_file;
	    while (is_link($tmp)) { // Dereference links
	      $tmp = readlink($tmp);
	    }
	  }
	  if (is_dir($entry_file)) {
	    $postfix = '/';
	  }
	  // Create reference with $this->root_uri as base.  Put
	  // trailing slash do directories.
	  $entry_uri = $this->root_uri.'/'.$entry.$postfix;
	  $ui->appendToTOC($entry_uri, $ui->labelMapper->getLabel($entry_uri));
	  if ( $generate_menu ) {
	    $ui->appendToMenu($entry_uri, 
			      $ui->labelMapper->getLabel($entry_uri));
	  }
	}
      }
      $d->close();
    } // end of generating navdata

    // Include per-directory configuration file .henkari or _henkari
    if ( file_exists($this->filename.'/.henkari') ) {
      include($this->filename.'/.henkari');
    } elseif ( file_exists($this->filename.'/_henkari') ) {
      include($this->filename.'/_henkari');
    }

    // Handle further...
    if ( isset($nextroot) && isset($nextfile) ) {
      $this->next($nextroot, $nextfile);
    }
  }

  function next($nextroot, $nextfile) {
    $hf =& handlerFactory::getInstance();
    $h =& $hf->createHandler(
	    $this->root_uri . $nextroot, 
	    $this->filename . $nextfile,
	    $this->patharray);
    $h->handle();
  }
}
?>

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
 * Handler for extended JavaSketchpad constructions
 **/
class ejscHandler extends abstractHandler {

  // Constructor
  function ejscHandler($root_uri_, $filename_, $patharray_) {
    $this->abstractHandler($root_uri_, $filename_, $patharray_);
    $this->ejscc           =  './bin/ejscc -p -o -';
    $this->defaults = 
      array("codebase"     => '/jsp',
	    "string_name"  => 'JSP Construction',
	    "appletwidth"  => 300,
	    "appletheight" => 200);
    global $config;
    if ( isset($config->javasketchpad->codebase) ) {
      $this->defaults['codebase'] = $config->javasketchpad->codebase;
    }
  }

  // Execute command and return output.
  function buffered_system($command, &$retcode) {
    $output = '';
    if ( $p=popen("($command)2>&1","r") ) {
      while (!feof($p)) {
	$l=fgets($p,100);
	$output = $output . $l;
      }
    }
    return $output;
  }

  function handle() {
    global $config;
    $ui =& UI::getInstance();
    $ui->addHeader("Content-Type: text/html");
    // Try to retrieve template, $config->javasketchpad->template = {local,default,plain_local,plain_default}
    if ( $config->javasketchpad->template == 'local' ||
	 $config->javasketchpad->template == 'plain_local' ) {
      $template = $this->filecontents($this->filename . '.html');
    }
    if ( $template == '' ) {
      $template = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title>{string_name}</title>
  </head>
  <body>
    <applet code="GSP.class" codebase="{codebase}" archive="jsp4.jar"
            width="{appletwidth}" height="{appletheight}">
      <param name="Construction" value="
{construction}">
    </applet>
    {content}
  </body>
</html>'; //";{//workaround to make lazy-lock-mode work properly
    }
    
    // Set template.  Override previously set, if plain template is
    // requested.
    if ( $config->javasketchpad->template == 'plain_local' ||
	 $config->javasketchpad->template == 'plain_default' ) {
      $ui->setTemplate($template, true);
    } else {
      $ui->setTemplate($template);
    }

    // Set defaults
    foreach ( $this->defaults as $key => $val ) {
      $ui->tpl->set_var($key, $val);
    }

    // Create temporary file
    $tmpfile_name = tempnam($this->tmpdir, "henkari_");
    $tmpfile = fopen($tmpfile_name, "w");
    // Dump source
    $source = $this->filecontents($this->filename);
    fwrite($tmpfile, $source);
    // Parse DEFAULT_ -parameters
    $variables = array();
    foreach ( split("\n", $source) as $line ) {
      if ( substr($line, 0, 16) == "#define DEFAULT_" ) {
	list($key, $val) = split(" ", substr($line, 16), 2);
	$variables[$key] = $val;
      }
    }
    // Parse GET/POST variables.
    foreach ( array('HTTP_POST_VARS', 'HTTP_GET_VARS') as $arr) {
      global $$arr;
      foreach ( $$arr as $key => $val ) {
	$variables[$key] = $val;
      }
    }
    // Dump variables.
    foreach ( $variables as $key => $val ) {
      // Write variable to template.
      $ui->tpl->set_var($key, $val);
      //  $template = str_replace('{'.$key.'}', $val, $template);
      // Write variable to source code file.  Enclose variables having
      // 'string' in their name in single quotes.
      if ( !(stristr($key, 'string') === false) ) {
	$val = "'".$val."'";
      }
      fwrite($tmpfile, "$key = $val;\n");
    }

    // Close temporary file
    fclose($tmpfile);
    // Compile construction.
    $code = $this->buffered_system("{$this->ejscc} $tmpfile_name", $ret);

    // Render page
    // Place construction on template.
    $ui->tpl->set_var('construction', $code);
    // Write path entry
    $ui->appendToPath($this->root_uri, $ui->labelMapper->getLabel($this->root_uri));
    //    $page = str_replace("{construction}", $code, $template);
  }
}
?>

<?php
/****************************************************************************
 * Copyright 2002, Tero Tilus <tero@tilus.net>
 *
 * This php class is distributed under the terms of the GNU General
 * Public License <http://www.gnu.org/licenses/gpl.html> and WITHOUT
 * ANY WARRANTY.
 **/
require_once('lib/abstractHandler.class.php');
require_once('lib/CGI.class.php');
require_once('lib/html.php');

/****************************************************************************
 * Handler for html-files 
 **/
class htmlHandler extends abstractHandler {
  /**************************************************************************
   * Handler function
   **/
  function handle() {
    switch ( CGI::safeLookupGP('action', 'view') ) {
    case 'view':
    case 'show':
    default:
      $this->showPage();
      break;
    case 'save':
      $this->savePage();
    case 'edit':
      $this->editPage();
      break;
    }
  }

  /**************************************************************************
   * Show page
   **/
  function showPage() {
    global $config;
    if ( (!is_readable($this->filename)) || 
	 !($htmlpage = $this->filecontents($this->filename)) ) {
      // File wasn't readable or the read-operation failed to return
      // any data.
      $h = new errorHandler($this->root_uri, $this->filename, $this->patharray);
      $h->handle();
      return;
    }
    $this->writePathEntry();
    $ui =& UI::getInstance();
    // Show page
    $ui->addHeader('Content-type: text/html');
    $ui->pageTitle(html_extract_tag_contents($htmlpage, 'title'), true);
    if ( !($body = html_extract_tag_contents($htmlpage, 'body')) ) {
      $body = $htmlpage;
    }
    $ui->appendToPage($this->parseNested($body));

    // Check permissions and if they allow editing, show edit-link.
    $af =& authFactory::getInstance();
    $au =& $af->getAuth();
    if ( is_writable($this->filename) && 
	 $au->canProvideLogin() ) {
      $ui->appendToPage(' <a href="'.$this->root_uri.'?action=edit">'.
			 $config->htmlHandler->editbutton.'</a> ', 
			'footnote');
    }
  }

  /**************************************************************************
   * Quote html special chars and curly braces (phplib variables).
   * Phplib blocks are well-behaving because htmlspecialchars() quotes
   * (<'s and >'s in)them.
   **/
  function quoteEditable($str) {
    $retstr = htmlspecialchars($str);
    $retstr = str_replace('{', '&#123;', $retstr);
    $retstr = str_replace('}', '&#125;', $retstr);
    return $retstr;
  }

  /**************************************************************************
   * Show page source in edit form
   **/
  function editPage() {
    global $config;
    $ui =& UI::getInstance();
    $ui->addHeader('Content-type: text/html');
    // Authenticate user
    if ( ! $this->authenticateUser() ) {
      return false; // User authentication failed, editing not allowed
    }
    // Check writability of this file and show warning if changes
    // can't be saved.
    if ( ! is_writable($this->filename) ) {
      $ui->appendToPage("<p style=\"color: red; font-weight: bold\">File not writable!</p>\n");
    }
    // Try to read file contents
    $source = $this->filecontents($this->filename);
    if ( ! $source ) {
      $ui->appendToPage('<p style="color: red; font-weight: bold">Error opening page.</p>');
      return;
    }
    // Show preview of the page.
    $this->writePathEntry();
    $ui->pageTitle(html_extract_tag_contents($source, 'title'), true);
    if ( '' == ($body = html_extract_tag_contents($source, 'body')) ) {
      $body = $source;
    }
    // Build edit-form for page.
    $editform  = sprintf('<form method="post" action="%s">'."\n", 
			 $this->root_uri.
			 ( (basename($this->filename) == $config->indexfile) ?
			   '/':''));
    $editform .= sprintf('<input type="hidden" name="action" value="save">'."\n");
    $editform .= sprintf('<textarea name="data" rows="%d" cols="%d" wraping="none" scrollbars="both">%s</textarea>'."\n",
			 $config->htmlHandler->rows, 
			 $config->htmlHandler->cols,
			 $this->quoteEditable($source));
    $editform .= sprintf('<br><input type="submit" value="%s">'."\n</form>\n",
			 $config->htmlHandler->savebutton);
    // Append preview and edit-form.  Parse nested pages only from
    // preview, not from the source.
    $ui->appendToPage($this->parseNested($body).$editform);
    $ui->appendToPage(' <a href="'.$this->root_uri.'">'.$config->htmlHandler->donebutton.'</a>', 
		      'footnote');
  }

  /**************************************************************************
   * Save data coming from edit form
   **/
  function savePage() {
    $ui = UI::getInstance();
    // Authenticate user
    if ( ! $this->authenticateUser() ) {
      return false; // User authentication failed, editing not allowed
    }
    // Writability
    if ( ! is_writable($this->filename) ) {
      $ui->appendToPage("<p style=\"color: red; font-weight: bold\">File not writable!</p>\n");
      return false;
    }

    // Creating backup
    $ui->appendToPage("<p>Creating backup.</p>");
    $backupfile = $this->filename.date('.Y-m-d_H-i-s');
    copy($this->filename, $backupfile);
    chmod($backupfile, 0644);

    // Open file for writing
    $ui->appendToPage("<p>Opening file '{$this->filename}' for write.</p>");
    $of = @fopen($this->filename, 'w');
    if ( ! $of ) {
      $ui->appendToPage("<p style=\"color: red; font-weight: bold\">Couldn't open file '{$this->filename}' for writing!</p>\n");
    }

    // Get data to write
    $data = CGI::safeLookupGP('data', false);
    if ( $data === false ) {
      $ui->appendToPage("<p style=\"color: red; font-weight: bold\">No data!</p>\n");
    } 

    // Deal with magic quotes
    if ( get_magic_quotes_gpc() == 1 ) {
      $data = stripslashes($data);
    }

    // Stay with unix linebreaks
    $data = str_replace("\r\n", "\n", $data);  // Win -> Unix
    $data = str_replace("\r", "\n", $data);    // Mac -> Unix
    // Write
    if ( fwrite($of, $data) == -1 ) {
      $ui->appendToPage("<p style=\"color: red; font-weight: bold\">Write of file '{$this->filename}' failed!</p>\n");
    } else {
      $ui->appendToPage("<p>Write successfull.</p>\n");
    }
    fclose($of);
    // Stat cache must be cleared to viewPage to function properly.
    // Otherwise size of the file is likely to be wrong.
    clearstatcache();
    // Cleanup cache.
    global $config;
    @unlink($config->cachefile);
  }
}
?>

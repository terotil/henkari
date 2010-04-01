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

class statementlistHandler extends abstractHandler {
  function handle() {
    $ui =& UI::getInstance();
    $template = $this->filecontents($this->filename . '.html');
    if ( $template == '' ) {
      $template = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title>Statements</title>
  </head>
  <body>
    <form action="{this_uri}" method="GET">
    <table border="1">
      <tr><th>Statement</th><th>True</th><th>False</th><th>Answer</th></tr>
<!-- BEGIN statement -->
      <tr>
        <td>{statement_text}</td>
        <td><input type="radio" name="{statement_name}" value="1"{true_checked}></td>
        <td><input type="radio" name="{statement_name}" value="0"{false_checked}></td>
<!-- BEGIN answers -->
<!-- BEGIN right -->
        <td><span style="color:green">Right!</span></td>
<!-- END right -->
<!-- BEGIN wrong -->
        <td><span style="color:red">Wrong!</span></td>
<!-- END wrong -->
<!-- BEGIN noanswer -->
        <td>&nbsp;</td>
<!-- END noanswer -->
<!-- END answers -->
      </tr>
<!-- END statement -->
      <tr><td colspan="4"><input type="submit" value="Check"></td></tr>
    </table>
    </form>
    {content}
  </body>
</html>'; //";{//workaround to make lazy-lock-mode work properly
    }

    // Parse possible nested page queries from template.
    $ui->setTemplate($this->parseNested($template));
    
    // Prepare template
    $ui->tpl->set_block('page', 'statement', 'statements');
    $ui->tpl->set_block('statement', 'answers', 'answer');
    $ui->tpl->set_block('answers', 'right');
    $ui->tpl->set_block('answers', 'wrong');
    $ui->tpl->set_block('answers', 'noanswer');

    // Get and parse statements
    $statements = $this->filecontents($this->filename);
    $statements = split("\n\n", $statements);
    foreach ( $statements as $num => $statement) {
      $statement_name = "statement_$num";
      $ui->tpl->set_var('statement_name', $statement_name);
      list($statement_text, $statement_truthvalue) = split("\|", $statement);
      $ui->tpl->set_var('statement_text', $statement_text);
      $statement_truthvalue = trim($statement_truthvalue);
      $statement_answer =  CGI::safeLookupGP($statement_name, 'noanswer');
      if ( $statement_answer === '1' ) {
	$ui->tpl->set_var('true_checked', ' checked');
	$ui->tpl->set_var('false_checked', '');
      }
      if ( $statement_answer === '0' ) {
	$ui->tpl->set_var('true_checked', '');
	$ui->tpl->set_var('false_checked', ' checked');
      }
      if ( $statement_answer === 'noanswer' ) {
	$ui->tpl->set_var('true_checked', '');
	$ui->tpl->set_var('false_checked', '');
	$ui->tpl->set_var('answer', $ui->tpl->get_var('noanswer'));
      } elseif ( $statement_answer === $statement_truthvalue ) {
	$ui->tpl->set_var('answer', $ui->tpl->get_var('right'));
      } else {
	$ui->tpl->set_var('answer', $ui->tpl->get_var('wrong'));
      }
      $ui->tpl->parse('statements', 'statement', true);
    }

    // Show in path
    $this->writePathEntry();
  }
}

?>

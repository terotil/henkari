<?php
/******************************************************************************
 ** Some quick and dirty html processing functions written for MAPPI.
 ** This code is by no means MAPPI spesific.  Feel free to use.
 **
 ** Copyringht (c) 2001 Mediaseitti
 **                     Tero Tilus
 **
 ** @author Tero Tilus / Mediaseitti
 */

/******************************************************************************
 ** Extracts tag contents from string
 */
function html_extract_tag(&$string, $tagname, $end=true) {
  if ( $end ) {
    // Ungreedy, case Insensitive, dot matches newlineS too
    $preg_tag = "|<$tagname([^>]*)>(.*)</$tagname>|Uis";
  } else {
    // No endtag
    $preg_tag = "|<$tagname([^>]*)>|Uis";
  }
  if ( preg_match_all($preg_tag, $string, $match, PREG_SET_ORDER) ) {
    return $match;
  } 
  return false;
}

function html_extract_tag_contents(&$string, $tagname, $tag_index=0) {
  if ( $tag = html_extract_tag($string, $tagname, true) and
       count($tag) > $tag_index ) {
    return $tag[$tag_index][2];
  }
  return false;
}

function deparameterize($paramstr) {
  // FIXME: Välilyöntejä sisältävät parametrit eivät konvertoidu
  // oikein.  Regexpejä peliin!  
  // from 'key="val" key="val"' to array('key="val"', 'key="val"')
  $parr = explode(' ', $paramstr);
  $return_parr = array();
  foreach ( $parr as $par ) {
    // Split 'key="val"' to 'key' and '"val"'
    list($key, $val) = explode('=', $par, 2);
    $key = strtolower(trim($key));
    $val = str_replace('"', '', trim($val));
    $return_parr[$key] = $val;
  }
  return $return_parr;
}

function html_extract_tag_params(&$string, $tagname, $endtag=true, $tag_index=0) {
  if ( $tag = html_extract_tag($string, $tagname, $endtag) and
       count($tag) > $tag_index ) {
    return deparameterize($tag[$tag_index][1]);
  }
  return false;
}

function html_extract_tag_param(&$string, $tagname, $param_name, $tag_index=0) {
  $params = html_extract_tag_params($string, $tagname, $tag_index);
  if ( is_array($params) and
       array_haskey($params, $param_name) ) {
    return $params[$param_name];
  }
  return false;
}

function html_get_title(&$string) {
  return html_extract_tag_contents($string, 'title');
}

function html_get_body(&$string) {
  return html_extract_tag_contents($string, 'body');
}

/******************************************************************************
 ** Producing HTML
 */
function parameterize(&$item, $key) {
  if ( $item === null ) {
    $item = " $key";
  } else {
    $item = " $key=\"$item\"";
  }
}

function html_make_tag($name, $content='', $params=array()) {
  $param_str = '';
  if ( count($params) > 0 ) {
    array_walk($params, 'parameterize');
    $param_str = join('', $params);
  }
  if ( $content == '' ) {
    return "<$name$param_str>";
  }
  return "<$name$param_str>$content</$name>";
}

function html_make_a($href, $content, $params=array()) {
  return html_make_tag('a', 
		       $content, 
		       array_merge(array('href'=>$href), 
				   $params)
		       );
}

function html_make_dropdown($list, $name, $selected=null) {
  $tmp = "\n";
  foreach ( $list as $key => $val ) {
    // Valitun merkitseminen
    if ( $val === $selected ) {
      $tmp_param = array('value'=>$val, 'selected'=>null);
    } else {
      $tmp_param = array('value'=>$val);
    }
    // Lisätään valintatagi
    $tmp .= html_make_tag('option', $key, $tmp_param) . "\n";
  }
  return html_make_tag('select', $tmp, array('name'=>$name));
}

function html_make_table($list, $params=array()) {
  // Alustetaan taulukon sisältö tyhjäksi
  $tmp_tab = "\n";

  // Tarkistetaan, että kyseessä on taulukko
  if ( is_array($list) ) {
    // Käydään läpi rivit
    foreach ( $list as $row ) {
      // Alustetaan rivin sisältö tyhjäksi
      $tmp_row = '';
      // Tarkistetaan, että kyseessä on taulukko
      if ( is_array($row) ) {
	// Käydään läpi yhden rivin sarakkeet
	foreach ( $row as $field ) {
	  $tmp_row .= html_make_tag('td', $field);
	}
      }
      $tmp_tab .= html_make_tag('tr', $tmp_row) . "\n";
    }
  }
  return html_make_tag('table', $tmp_tab, $params);
}

function html_make_form($method, $action, $content='', 
			$hidden_params = array(), 
			$form_tag_params = array()) {
  return html_make_tag('form',
		       "\n".
		       array_to_params($hidden_params, 'POST').
		       $content.
		       "\n",
		       array_merge(array('method' => $method, 'action' => $action),
				   $form_tag_params)
		       );
}

function html_make_button($url='', $text='Reload', $params = array()) {
  return html_make_form('GET', 
			$url, 
			html_make_tag('input', 
				      null, 
				      array('type'=>'submit', 
					    'value'=>$text)
				      ),
			$params
			);
}

?>

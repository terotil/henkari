<?php
/******************************************************************************
 ** Reindex two-dimensional array ( = array containing arrays, an array
 ** of table-rows) with values of given columns ( = keys found from
 ** every row) as new keys in given order.  Every row of given
 ** 2-dimensional array must have all the given keys.  Old indexing is
 ** lost during operation.  If it has to be preserved you can append
 ** the key of every array to array itself before reindexing.
 **
 ** Example:
 **
 ** <pre>
 ** $arr = array(array('a',1,'c'),
 **              array('a',2,'d'),
 **              array('a',2,'e'),
 **              array('b',3,'f'),
 **              array('b',3,'g'),
 **              array('b',4,'h'));
 ** $folded = array_fold($arr, array(0));
 ** </pre>
 **
 ** Now <code>$folded</code> contains
 **
 ** <pre>
 ** array("a" => array(array("a",1,"c"),
 **                   array("a",2,"d"),
 **                   array("a",2,"e")
 **                   ),
 **      "b" => array(array("b",3,"f"),
 **                   array("b",3,"g"),
 **                   array("b",4,"h")
 **                   )
 **      );
 ** </pre>
 **
 ** @param $arr 2-dimensional array to be reindexed
 **
 ** @param $dimcols Array of keys to use when reindexing.  Every row of
 ** $arr must have all the keys listed in $dimcols
 **
 ** @param $verified True when it has been checked that reindexing
 ** request is valid.  This variable is used internally to speed up
 ** recursive requests when reindexing on more than one column.
 **
 ** @return Reference to reindexed array.
 */

function &array_fold(&$arr, $dimcols, $verified=false) {
  if ( !$verified ) {
    // Check that every row contains keys in $dimcols
    foreach ($arr as $arrkey => $row) {
      $rowkeys = array_keys($row);
      foreach ($dimcols as $wanted_key) {
	if ( !in_array($wanted_key, $rowkeys) ) {
	  // A missing key!
	  // FIXME: Proper error message
	  trigger_error("mappi_lib.php::array_fold() : taulukon ".
			"riviltä '$arrkey' puuttuu indeksi '$wanted_key'",
			E_USER_WARNING);
	  return array();
	}
      }
    }
  }

  // Take first key from $dimcols and fold array on that column.
  $folded = array();
  $newdim = array_shift($dimcols);
  foreach ($arr as $row) {
    $folded[$row[$newdim]][] = $row;
  }

  // If there's still keys left in $dimcols, call array_fold
  // recursively on every member of newly folded array.
  if ( count($dimcols) > 0 ) {
    foreach (array_keys($folded) as $foldedkey) {
      $folded[$foldedkey] =& array_fold($folded[$foldedkey], $dimcols, true);
    }
  }

  return $folded;
}
?>

<?php

function log_error($a) {
  ;
}

$file = "haushalt2011.xml";

$info=array();
$GLOBALS['depth']=0;

function startElement($parser, $name, $attrs) {
  global $info;
 
  switch (strtolower($name)) {
  case 'page':
    $GLOBALS['info']['pagenumber']=$attrs['NUMBER'];
  case 'text':
    $GLOBALS['info']['interestingElement']['page']=$GLOBALS['info']['pagenumber'];
    $GLOBALS['info']['interestingElement']['attrs']=$attrs;
    $GLOBALS['info']['interestingElement']['content']="";
    break;
  default:
    log_error("Begin unknown Element $name");
  }
  
  $GLOBALS['depth']++;
}

function endElement($parser, $name) {
  switch (strtolower($name)) {
  case 'page':
    $GLOBALS['info']['pagenumber']=null;
    break;
  case 'text':
    if ($GLOBALS['info']['pagenumber']=='168') { // erstmal nur eine Seite...
      $GLOBALS['info']['elements'][]=$GLOBALS['info']['interestingElement'];
    }
    unset($GLOBALS['info']['interestingElement']);
    break;
  default:
    log_error("Ended unknown Element $name");
  }

  $GLOBALS['depth']--;
}

function charData($parser,$data) {
  if (isset($GLOBALS['info']['interestingElement']))
    $GLOBALS['info']['interestingElement']['content'].=$data;
  else
    log_error("Unassociated Char Data $data");
}

$xml_parser = xml_parser_create();
xml_set_element_handler($xml_parser, "startElement", "endElement");
xml_set_character_data_handler($xml_parser,"charData");

if (!($fp = fopen($file, "r"))) {
  die("could not open XML input");
 }

while ($data = fread($fp, 4096)) {
  if (!xml_parse($xml_parser, $data, feof($fp))) {
    die(sprintf("XML error: %s at line %d",
		xml_error_string(xml_get_error_code($xml_parser)),
		xml_get_current_line_number($xml_parser)));
  }
 }
xml_parser_free($xml_parser);
//$tree = end(end($stack));

//pg_connect('');

echo "<div style='font-size:10px'>";
echo "\n";
$last=null;

$table=array();
$row=array();
$cell='';

foreach ($GLOBALS['info']['elements'] as $i=>$e) {
  if (preg_match('/^[^a-z]*-?[0-9.,]+[^a-z]*$/',$e['content'])) {
    $color='green';
  } else {
    $color='red';
  }
  extract($e['attrs']);

  if ($TOP < 153 || $TOP >=400) // nur ein Test..
    continue;

  $RIGHT=$LEFT+$WIDTH;
  $BOTTOM=$TOP+$HEIGHT;

  if ($last) {
    if ($WIDTH<200) {
      //      echo $e['content']."\n";

      if ($TOP>=$last['attrs']['TOP'] && $LEFT<$last['attrs']['LEFT']-100) {
	echo "down: $e[content]<br/>";
	$row[]=$cell; // links darunter: naechste zeile
	$table[]=$row;
	$row=array();
	$cell=$e['content'];
      } elseif ($TOP>$last['attrs']['TOP']+3 && $LEFT<$last['attrs']['LEFT']+2) {
	echo "same: $e[content]<br/>";
	$cell.=$e['content']; // darunter: selbe spalte
      } elseif ($TOP<=$last['attrs']['TOP']+3 && $LEFT>$last['attrs']['LEFT']+20) {
	echo "right: $e[content]<br/>";
	$row[]=$cell; // rechts daneben: naechste spalte
	$cell=$e['content'];
      }      
    }
  } else {
    echo "first: $e[content]<br/>";
    $cell=$e['content']; // erste Zelle
  }
    
  $last=$e;
}

// HTML-Tabelle erzeugen: zwischenlösung, erstmal den Parser hinbekommen

echo "<table>";
foreach ($table as $row) {
  echo "<tr>\n";
  foreach ($row as $cell) {
    echo "<td>$cell</td>\n";
  }
  echo "</tr>\n";
}
echo "</table>\n";

echo "</div>";

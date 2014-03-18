<?php
@include('__main__');

/* initial settings */
$name['your_bot'] = '';
$name['opponent_bot'] = '';

$superRegions = array();
$regions = array();
$neighbourList = array();
$startingRegions = array();

/* turn local variables */
$spawnTo = NULL;
$spawn = NULL;
$actual = NULL;

/* main loop */
while($line = fgets(STDIN))
  {
    $xpl = explode("\n",$line);
    $xpl = explode(' ',$xpl[0]);
    switch($xpl[0])
      {
      case "settings":
	if($xpl[1] == 'starting_armies')
	  {
	    $spawn = (int)$xpl[2];
	    break;
	  }
	$name[$xpl[1]] = $xpl[2];
	break;
      case "setup_map":
	if($xpl[1] == 'neighbors')
	  {
	    $tmpList = array();
	    for($i = 2; $i < count($xpl); $i += 2)
	      $tmpList[$xpl[$i]] = explode(',',$xpl[$i+1]);
	    foreach($tmpList as $key => $value)
	      {
		foreach($value as $val)
		  {
		    $neighbourList[$key][] = (int)$val;
		    $neighbourList[$val][] = $key;
		  }
	      }
	  }
	break;
      case "pick_starting_regions":
	echo "No moves\n";
	break;
      case "update_map":
	for($i = 1; $i < count($xpl); $i += 3)
	  {
	    $max = 0;
	    if($xpl[$i+1] == $name['your_bot'] && $xpl[$i+2] > $max)
	      {
		$max = $xpl[$i+2];
		$spawnTo = (int)$xpl[$i];
		$actual = (int)$xpl[$i+2];
	      }
	  }
	break;
      case "opponent_moves":
	break;
      case "go":
	if($xpl[1] == 'place_armies')
	  echo $name['your_bot'].' place_armies '.$spawnTo.' '.$spawn."\n";
	else
	  {
	    $goTo = $neighbourList[$spawnTo][rand(0,count($neighbourList[$spawnTo])-1)];
	    echo $name['your_bot'].' attack/transfer '.$spawnTo.' '.$goTo.' '.($actual + $spawn - 1)."\n";
	  }
	break;
      }
  }

<?php

/* TODO: opisy tablic */

class Parser
{
  public static function parseLine($line)
  {
    $xpl = explode("\n",$line);
    $xpl = explode(' ',$xpl[0]);
    switch($xpl[0])
      {
      case "settings":
	if($xpl[1] == 'starting_armies')
	  {
	    Intelligence::$mySpawn = (int)$xpl[2];
	    Intelligence::$hisSpawn = (int)$xpl[2];
	    break;
	  }
	Storage::$botName[$xpl[1]] = $xpl[2];
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
		    Storage::$neighbourList[$key][] = (int)$val;
		    Storage::$neighbourList[$val][] = $key;
		  }
	      }
	  }
	if($xpl[1] == 'super_regions')
	  {
	    for($i = 2; $i < count($xpl); $i += 2)
	      {
		Storage::$superRegions[(int)$xpl[$i]] = array();
		Storage::$superRegions[(int)$xpl[$i]]['value'] = (int)$xpl[$i+1];
		Storage::$superRegions[(int)$xpl[$i]]['regions'] = array();
	      }
	  }
	if($xpl[1] == 'regions')
	  {
	    for($i = 2; $i < count($xpl); $i += 2)
	      {
		Storage::$superRegions[(int)$xpl[$i+1]]['regions'][] = (int)$xpl[$i];
		Storage::$regions[(int)$xpl[$i]] = (int)$xpl[$i+1];
	      }
	  }
	break;
      case "pick_starting_regions":
	for($i = 2; $i < count($xpl); $i++)
	  Storage::$startingRegions[] = (int)$xpl[$i];
	Storage::floyd();
	return 1;
      case "update_map":
	$re = array();
	for($i = 1; $i < count($xpl); $i += 3)
	  $re[] = array(
			'region' => (int)$xpl[$i],
			'bot' => $xpl[$i+1],
			'armies' => (int)$xpl[$i+2]
			);
	Intelligence::updateMap($re);
	break;
      case "opponent_moves":
	$re = array();
	for($i = 1; $i < count($xpl); $i += 4)
	  if($xpl[$i+1] == 'place_armies')
	    {
	      $re[] = array(
			    'bot' => $xpl[$i],
			    'kind' => $xpl[$i+1],
			    'region' => (int)$xpl[$i+2],
			    'armies' => (int)$xpl[$i+3]
			    );
	    }
	  else			/* attack/transfer */
	    {
	      $re[] = array(
			    'bot' => $xpl[$i],
			    'kind' => $xpl[$i+1],
			    'from' => (int)$xpl[$i+2],
			    'to' => (int)$xpl[$i+3],
			    'armies' => (int)$xpl[$i+4]
			    );
	      $i++;
	    }
	Intelligence::opponentMoves($re);
	break;
      case "go":
	if($xpl[1] == 'place_armies')
	  return 2;
	return 3;
      }
    return 0;
  }

  public static function makePick($picks)
  {
    if($picks == array())
      return "No moves\n";
    Storage::$chosenStartingRegions = $picks;
    return implode(' ',$picks)."\n";
  }

  public static function makePlace($places)
  {
    $re = array();
    foreach($places as $place)
      $re[] = Storage::$botName['your_bot'].' place_armies '.$place['region'].' '.$place['armies'];
    if($re == array())
      return "No moves\n";
    return implode(', ',$re)."\n";
  }

  public static function makeMove($moves)
  {
    $re = array();
    foreach($moves as $move)
      $re[] = Storage::$botName['your_bot'].' attack/transfer '.$move['from'].' '.$move['to'].' '.$move['armies'];
    if($re == array())
      return "No moves\n";
    return implode(', ',$re)."\n";
  }
}
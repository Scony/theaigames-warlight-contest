<?php

class SimpleExpand extends Strategy
{
  public function pick()
  {
    $tmp = array();
    foreach(Storage::$startingRegions as $region)
      $tmp[$region] = Storage::$superRegions[Storage::$regions[$region]]['value']; /* FIXME: more inteligent/general */
    asort($tmp);
    $re = array();
    $i = 0;
    foreach($tmp as $region => $rate)
      {
	if($i++ >= Hardcode::$numInitialRegions)
	  break;
	$re[] = $region;
      }
    return $re;
  }

  public function place()
  {
    return array();
  }

  public function move()
  {
    return array();
  }
}
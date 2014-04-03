<?php

class RunForrestI extends Strategy
{
  private $trace = array();
  private $armies = 0;
  private $spawnTo = NULL;

  public function place()
  {
    /* find my max region */
    $this->armies = 0;
    $this->spawnTo = NULL;
    foreach(Intelligence::$regions as $rid => $val)
      if($val['bot'] == Storage::$botName['your_bot'] && $val['armies'] > $this->armies)
	{
	  $this->armies = $val['armies'];
	  $this->spawnTo = $rid;
	}

    /* return placement */
    return array(
		 array(
		       'region' => $this->spawnTo,
		       'armies' => Intelligence::$mySpawn
		       )
		 );
  }

  public function move()
  {
    $tmp = array();
    foreach(Storage::$neighbourList[$this->spawnTo] as $target)
      $tmp[$target] = $this->regionSafety($target,$this->spawnTo);
    asort($tmp);
    $keys = array_keys($tmp);
    $goTo = $keys[0];

    return array(
		 array(
		       'from' => $this->spawnTo,
		       'to' => $goTo,
		       'armies' => $this->armies + Intelligence::$mySpawn - 1
		       )
		 );
  }

  protected function regionSafety($region, $parent = -1, $depth = 1)
  {
    if($depth < 0)
      return 0;
    if(!array_key_exists($region,Intelligence::$regions))
      return 0;

    $safety = NULL;
    if(Intelligence::$regions[$region]['bot'] == Storage::$botName['your_bot'])
      $safety = -Intelligence::$regions[$region]['armies'];
    else if(Intelligence::$regions[$region]['bot'] == Storage::$botName['opponent_bot'])
      $safety = Intelligence::$regions[$region]['armies'];
    else
      $safety = 0;
    foreach(Storage::$neighbourList[$region] as $neighbour)
      if($neighbour != $parent)
	$safety += $this->regionSafety($neighbour,$region,$depth-1);

    return $safety;
  }
}
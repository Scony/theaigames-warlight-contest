<?php

class StackSpread extends Strategy
{
  private $trace = array();
  private $armies = 0;
  private $spawnTo = NULL;

  public function place()
  {
    /* update trace */
    foreach($this->trace as $key => $value)
      $this->trace[$key] = $value + 1;

    /* find my max region */
    $this->armies = 0;
    $this->spawnTo = NULL;
    foreach(Intelligence::$regions as $rid => $val)
      if($val['bot'] == Storage::$botName['your_bot'] && $val['armies'] > $this->armies)
	{
	  $this->armies = $val['armies'];
	  $this->spawnTo = $rid;
	}

    /* reset trace */
    $this->trace[$this->spawnTo] = 0;

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
      if(!array_key_exists($target,$this->trace))
	$tmp[] = $target;
    if($tmp == array())
      {
	$max = 0;
	foreach(Storage::$neighbourList[$this->spawnTo] as $target)
	  {
	    if($this->trace[$target] == $max)
	      $tmp[] = $target;
	    if($this->trace[$target] > $max)
	      {
		$tmp = array();
		$tmp[] = $target;
		$max = $this->trace[$target];
	      }
	  }
      }
    $goTo = $tmp[rand(0,count($tmp)-1)];

    return array(
		 array(
		       'from' => $this->spawnTo,
		       'to' => $goTo,
		       'armies' => $this->armies + Intelligence::$mySpawn - 1
		       )
		 );
  }
}
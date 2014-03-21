<?php
@include('__main__');

class Storage
{
  public static $botName = array(
				 'your_bot' => NULL,
				 'opponent_bot' => NULL
				 );

  public static $superRegions = array();
  public static $regions = array();
  public static $neighbourList = array();
  public static $startingRegions = array();
}

class Intelligence
{
  public static $ownership = array();
  public static $regions = array();

  public static $mySpawn = NULL;
  public static $hisSpawn = NULL;

  public static function update($updates)
  {
    foreach($updates as $update)
      self::$regions[$update['region']] = array(
						'bot' => $update['bot'],
						'armies' => $update['armies']
						);
  }
}

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
      case "setup_map":		/* TODO: more cases */
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
	break;
      case "pick_starting_regions":
	for($i = 2; $i < count($xpl); $i++)
	  Storage::$startingRegions[] = (int)$xpl[$i];
	return 1;
      case "update_map":
	$re = array();
	for($i = 1; $i < count($xpl); $i += 3)
	  $re[] = array(
			'region' => (int)$xpl[$i],
			'bot' => $xpl[$i+1],
			'armies' => (int)$xpl[$i+2]
			);
	Intelligence::update($re);
	break;
      case "opponent_moves":
	/* TODO: */
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
    return implode(' ',$picks)."\n";
  }

  public static function makePlace($places)
  {
    $re = array();
    foreach($places as $place)
      $re[] = Storage::$botName['your_bot'].' place_armies '.$place['region'].' '.$place['armies'];
    return implode(', ',$re)."\n";
  }

  public static function makeMove($moves)
  {
    $re = array();
    foreach($moves as $move)
      $re[] = Storage::$botName['your_bot'].' attack/transfer '.$move['from'].' '.$move['to'].' '.$move['armies'];
    return implode(', ',$re)."\n";
  }
}

class DecisionMaker
{
  private static $strategy = NULL;

  public static function getStrategy()
  {
    if(!self::$strategy)
      self::$strategy = new StackSpread;

    return self::$strategy;
  }
}

abstract class Strategy
{
  public function pick()
  {
    return array();
  }
  abstract function place();
  abstract function move();
}

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

$strategy = DecisionMaker::getStrategy();

/* main loop */
while($line = fgets(STDIN))
  {
    $re = Parser::parseLine($line);
    switch($re)
      {
      case 1:
	echo Parser::makePick($strategy->pick());
	break;
      case 2:
	echo Parser::makePlace($strategy->place());
	break;
      case 3:
	echo Parser::makeMove($strategy->move());
	break;
      }
  }

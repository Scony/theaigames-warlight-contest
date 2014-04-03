<?php

class Intelligence
{
  public static $round = 0;

  public static $ownership = array();
  public static $regions = array();

  public static $mySpawn = Hardcode::$numInitialSpawn;
  public static $hisSpawn = Hardcode::$numInitialSpawn;

  public static function updateMap($updates)
  {
    /* updates */
    foreach($updates as $update)
      self::$regions[$update['region']] = array(
						'bot' => $update['bot'],
						'armies' => $update['armies']
						);
    /* look for enemy by round0 theorem */
    if(!self::$round)
      {
	$numMyRegions = 0;
	foreach(Storage::$chosenStartingRegions as $region)
	  {
	    if(!isset(self::$regions[$region]))
	      {
		self::$regions[$region] = array(
						'bot' => Storage::$botName['opponent_bot'],
						'armies' => Hardcode::$numInitialArmies
						);
		stderr("Opponent found @$region !\n");
	      }
	    else if(self::$regions[$region]['bot'] != Storage::$botName['opponent_bot'])
	      $numMyRegions++;
	    if($numMyRegions >= Hardcode::$numInitialRegions)
	      break;
	  }
      }
  }

  public static function opponentMoves($moves)
  {
    /* count opponent spawn */
    $hisSpawn = 0;
    foreach($moves as $move)
      if($move['kind'] == 'place_armies')
	$hisSpawn += $move['armies'];
    if($hisSpawn > self::$hisSpawn)
      self::$hisSpawn = $hisSpawn;

    /* round ends after opponentMoves so increment it */
    self::$round++;
  }
}
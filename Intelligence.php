<?php

class Intelligence
{
  public static $round = 0;

  public static $ownership = array();
  public static $regions = array();

  public static $mySpawn = NULL;
  public static $hisSpawn = NULL;

  public static function updateMap($updates)
  {
    /* assume that enemy took all my regions to prevent update bug */
    foreach(self::$regions as $region => $data)
      if($data['bot'] == Storage::$botName['your_bot'])
	self::$regions[$region]['bot'] = Storage::$botName['opponent_bot'];

    /* updates */
    foreach($updates as $update)
      self::$regions[$update['region']] = array(
						'bot' => $update['bot'],
						'armies' => $update['armies']
						);

    /* look for enemy hidden in the fog by round0 theorem */
    if(!self::$round)
      {
	$numMyRegions = 0;
	foreach(Storage::$chosenStartingRegions as $region)
	  {
	    if(!array_key_exists($region,self::$regions))
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

Intelligence::$mySpawn = Hardcode::$numInitialSpawn;
Intelligence::$hisSpawn = Hardcode::$numInitialSpawn;
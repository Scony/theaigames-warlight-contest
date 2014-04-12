<?php

class SmartExpand extends Strategy
{
  public function place()
  {
    /* array of all spawns */
    $spawn = array();

    /* armies I can spawn whole round */
    $mySpawn = Intelligence::$mySpawn;

    /* find all sticky enemy regions and rate them (higher rate, higher need) */
    $stick = array();
    foreach(Intelligence::$regions as $region => $data)
      if($data['bot'] == Storage::$botName['your_bot'])
	foreach(Storage::$neighbourList[$region] as $check)
	  if(Intelligence::$regions[$check]['bot'] == Storage::$botName['opponent_bot'])
	    $stick[$check] = $this->regionRate($check);

    /* sort enemy regions (more interesting ones first) */
    arsort($stick);

    /* attack-oriented spawn phase (if needed) */
    if(count($stick))
      {
	/* spawn all-1 to my best field sticky to first el of $stick */
	foreach($stick as $region => $nvm)
	  {
	    $max = 0;
	    $id = NULL;
	    foreach(Storage::$neighbourList[$region] as $neighbour)
	      if(array_key_exists($neighbour,Intelligence::$regions) &&
		 Intelligence::$regions[$neighbour]['bot'] == Storage::$botName['your_bot'] &&
		 Intelligence::$regions[$neighbour]['armies'] > $max)
		{
		  $max = Intelligence::$regions[$neighbour]['armies'];
		  $id = $neighbour;
		}
	    $spawn[] = array(
			     'region' => $id,
			     'armies' => $mySpawn - 1
			     );
	    $mySpawn -= $mySpawn - 1;
	    break;
	  }
      }

    /* expand-oriented spawn phase (if still have some spawn remaining) */
    if($mySpawn > 0)
      {
	/* make ordered list of free super regions that I am in*/
	/* and check how much armies missing */
	$freeSuperRegions = array();
	foreach(Storage::$superRegions as $superRegion => $nvm)
	  if($this->isInSuperRegion($superRegion,Storage::$botName['your_bot']) &&
	     !$this->isInSuperRegion($superRegion,Storage::$botName['opponent_bot']) &&
	     !$this->isWholeSuperRegionTaken($superRegion,Storage::$botName['your_bot']))
	    $freeSuperRegions[$superRegion] = $this->numArmiesMissingToTakeSuperRegion($superRegion,Storage::$botName['your_bot']);

	/* if there are no free super regions */
	if(!count($freeSuperRegions))
	  {
	    /* NAIVE: spawn all to the biggest of my fields */
	    /* UNOPT! */
	    $maxRegion = NULL;
	    $max = 0;
	    foreach(Storage::$superRegions as $superRegion => $data)
	      {
		foreach($data['regions'] as $region)
		  if(array_key_exists($region,Intelligence::$regions) &&
		     Intelligence::$regions[$region]['bot'] == Storage::$botName['your_bot'] &&
		     Intelligence::$regions[$region]['armies'] > $max)
		    {
		      $maxRegion = $region;
		      $max = Intelligence::$regions[$region]['armies'];
		    }
	      }
	    $spawn[] = array(
			     'region' => $maxRegion,
			     'armies' => $mySpawn
			     );
	    $mySpawn -= $mySpawn;
	  }
	else			/* if there are free super regions */
	  {
	    /* try to satisfy em */
	    foreach($freeSuperRegions as $superRegion => $missing)
	      if($missing > 0 && $mySpawn >= $missing)
		{
		  /* NAIVE: spawn to biggest in super region */
		  $maxRegion = NULL;
		  $max = 0;
		  foreach(Storage::$superRegions[$superRegion]['regions'] as $region)
		    if(array_key_exists($region,Intelligence::$regions) &&
		       Intelligence::$regions[$region]['bot'] == Storage::$botName['your_bot'] &&
		       !$this->isRegionDoomed($region) &&
		       Intelligence::$regions[$region]['armies'] > $max)
		      {
			$maxRegion = $region;
			$max = Intelligence::$regions[$region]['armies'];
		      }
		  if($max > 0)
		    {
		      $spawn[] = array(
				       'region' => $maxRegion,
				       'armies' => $missing
				       );
		      $freeSuperRegions[$superRegion] = 0;
		      $mySpawn -= $missing;
		    }
		  else		/* all regions doomed */
		    {
		      /* TODO: here + in move */
		      stderr('UNIMPLEMENTED: ALL_DOOMED');
		    }
		}

	    /* if smth remainging put it where lowest amount missing */
	    if($mySpawn > 0)
	      {
		$minSuperRegion = NULL;
		$min = PHP_INT_MAX;
		foreach($freeSuperRegions as $superRegion => $missing)
		  if($missing > 0 && $missing < $min)
		    {
		      $minSuperRegion = $superRegion;
		      $min = $missing;
		    }
		if($min != PHP_INT_MAX)
		  {
		    /* NAIVE: spawn to biggest in super region */
		    $maxRegion = NULL;
		    $max = 0;
		    foreach(Storage::$superRegions[$minSuperRegion]['regions'] as $region)
		      if(array_key_exists($region,Intelligence::$regions) &&
			 Intelligence::$regions[$region]['bot'] == Storage::$botName['your_bot'] &&
			 Intelligence::$regions[$region]['armies'] > $max)
			{
			  $maxRegion = $region;
			  $max = Intelligence::$regions[$region]['armies'];
			}
		    $spawn[] = array(
				     'region' => $maxRegion,
				     'armies' => $mySpawn
				     );
		    $mySpawn -= $mySpawn;
		  }
	      }
	  }
      }

    /* if smth still remaining put it to borders */
    if($mySpawn > 0)
      {
	/* lookup fully taken regions */
	$fullyTaken = array();
	foreach(Storage::$superRegions as $superRegion => $data)
	  if($this->isWholeSuperRegionTaken($superRegion,Storage::$botName['your_bot']))
	    $fullyTaken[] = $superRegion;

	/* make list of border-regions (sticky to doors) */
	$borders = array();
	foreach($fullyTaken as $superRegion)
	  {
	    foreach(Storage::$superRegions[$superRegion]['regions'] as $region)
	      {
		$doors = 0;
		foreach(Storage::$neighbourList[$region] as $neighbour)
		  if(Intelligence::$regions[$neighbour]['bot'] != Storage::$botName['your_bot'])
		    $doors++;
		if($doors > 0)
		  $borders[$region] = $doors;
	      }
	  }

	/* sort by number of doors */
	arsort($borders);

	/* if there are borders */
	if($borders != array())
	  {
	    /* NAIVE: put all to the border with best branching */
	    foreach($borders as $region => $nvm)
	      {
		$spawn[] = array(
				 'region' => $region,
				 'armies' => $mySpawn
				 );
		$mySpawn -= $mySpawn;
		break;
	      }
	  }
	else			/* if there are no borders */
	  {
	    /* NAIVE: spawn all to the biggest of my fields */
	    /* UNOPT! */
	    $maxRegion = NULL;
	    $max = 0;
	    foreach(Storage::$superRegions as $superRegion => $data)
	      {
		foreach($data['regions'] as $region)
		  if(array_key_exists($region,Intelligence::$regions) &&
		     Intelligence::$regions[$region]['bot'] == Storage::$botName['your_bot'] &&
		     Intelligence::$regions[$region]['armies'] > $max)
		    {
		      $maxRegion = $region;
		      $max = Intelligence::$regions[$region]['armies'];
		    }
	      }
	    $spawn[] = array(
			     'region' => $maxRegion,
			     'armies' => $mySpawn
			     );
	    $mySpawn -= $mySpawn;
	  }

      }

    /* update Intelligence::$regions */
    foreach($spawn as $data)
      Intelligence::$regions[$data['region']]['armies'] += $data['armies'];

    /* return placement */
    return $spawn;
  }

  public function move()
  {
    $moves = array();

    /* iterate through my fields */
    foreach(Intelligence::$regions as $region => $data)
      if($data['bot'] == Storage::$botName['your_bot'])
	{
	  $localMoves = array();

	  $opponents = array();
	  $neutrals = array();
	  foreach(Storage::$neighbourList[$region] as $neighbour)
	    {
	      if(Intelligence::$regions[$neighbour]['bot'] == Storage::$botName['opponent_bot'])
		$opponents[$neighbour] = Intelligence::$regions[$neighbour]['armies'];
	      if(Intelligence::$regions[$neighbour]['bot'] == 'neutral')
		$neutrals[$neighbour] = Intelligence::$regions[$neighbour]['armies'];
	    }
	  asort($opponents);
	  asort($neutrals);
	  $remaining = $data['armies'] - 1;
	  if(count($opponents) > 0) /* fight opponents or do nothin */
	    {
	      /* TODO: add ALL_DOOMED case */

	      /* check approx power needed */
	      $power = 0;
	      foreach($opponents as $opponent => $armies)
		$power += $armies + Intelligence::$hisSpawn + 1;

	      /* if I am overpowered */
	      if($remaining >= $power)
		{
		  foreach($opponents as $opponent => $armies)
		    {
		      $localMoves[] = array(
					    'from' => $region,
					    'to' => $opponent,
					    'armies' => $armies + Intelligence::$hisSpawn + 1
					    );
		      $remaining -= $armies + Intelligence::$hisSpawn + 1;
		    }
		}
	      else		/* I am not overpowered */
		{
		  foreach($opponents as $opponent => $armies)
		    {
		      if($data['armies'] - 1 >= $armies + Intelligence::$hisSpawn - 1)
			{
			  $localMoves[] = array(
						'from' => $region,
						'to' => $opponent,
						'armies' => $remaining
						);
			  $remaining -= $remaining;
			}
		      break;
		    }
		}
	    }
	  else	       /* expand */
	    {
	      if($remaining <= 0)
		continue;

	      /* whole super region taken */
	      if($this->isWholeSuperRegionTaken(Storage::$regions[$region],Storage::$botName['your_bot']))
		{
		  /* there are sticky neutrals */
		  if(count($neutrals) > 0)
		    {
		      foreach($neutrals as $neutral => $armies)
			if($armies < $remaining)
			  {
			    $localMoves[] = array(
						  'from' => $region,
						  'to' => $neutral,
						  'armies' => $armies + 1
						  );
			    $remaining -= $armies + 1;
			  }
			else
			  break;
		    }
		  else	/* there are no sticky neutrals (go and find some) */
		    {
		      $distances = Storage::$floyd[$region];
		      asort($distances);
		      $target = NULL;
		      foreach($distances as $one => $nvm)
			if(Intelligence::$regions[$one]['bot'] != Storage::$botName['your_bot'])
			  {
			    $target = $one;
			    break;
			  }
		      $neighbours = array();
		      foreach(Storage::$neighbourList[$region] as $neighbour)
			$neighbours[$neighbour] = Storage::$floyd[$neighbour][$target];
		      asort($neighbours);
		      foreach($neighbours as $neighbour => $nvm)
			{
			  $localMoves[] = array(
						'from' => $region,
						'to' => $neighbour,
						'armies' => $remaining
						);
			  $remaining -= $remaining;
			  break;
			}		      
		    }
		}
	      else 		/* not whole super region taken */
		{
		  /* filter neutrals from other super regions */
		  foreach($neutrals as $neutral => $nvm)
		    if(Storage::$regions[$region] != Storage::$regions[$neutral])
		      unset($neutrals[$neutral]);

		  /* if I can take some directly */
		  if($neutrals != array() && reset($neutrals) < $remaining)
		    {
		      foreach($neutrals as $neutral => $armies)
			if($armies < $remaining)
			  {
			    $localMoves[] = array(
						  'from' => $region,
						  'to' => $neutral,
						  'armies' => $armies + 1
						  );
			    $remaining -= $armies + 1;
			  }
			else
			  break;
		    }
		  else		/* I can't take anything by myself or there is nothing to take*/
		    {
		      /* NAIVE: !!*/
		      /* lookup fellas */
		      $fellas = array();
		      foreach(Storage::$neighbourList[$region] as $neighbour)
			if(Storage::$regions[$neighbour] == Storage::$regions[$region] &&
			   Intelligence::$regions[$neighbour]['bot'] == Storage::$botName['your_bot'])
			  $fellas[$neighbour] = Intelligence::$regions[$neighbour]['armies'];
		      arsort($fellas);

		      /* if I have fellas */
		      if(count($fellas) > 0)
			{
			  /* if I am local biggest */
			  $keys = array_keys($fellas);
			  if($remaining > reset($fellas) - 1 || ($remaining == reset($fellas) - 1 && $keys[0] < $region))
			    {
			      /* if no neutrals around then go to closest one*/
			      if($neutrals == array())
				{
				  $superRegionNotmines = array();
				  foreach(Storage::$superRegions[Storage::$regions[$region]]['regions'] as $reg)
				    if(!array_key_exists($reg,Intelligence::$regions) ||
				       Intelligence::$regions[$reg]['bot'] != Storage::$botName['your_bot'])
				      $superRegionNotmines[$reg] = Storage::$floyd[$region][$reg];
				  asort($superRegionNotmines);
				  foreach($superRegionNotmines as $target => $nvm)
				    {
				      $nerbys = array();
				      foreach($fellas as $fello => $nvm)
					$nerbys[$fello] = Storage::$floyd[$fello][$target];
				      asort($nerbys);
				      foreach($nerbys as $nerby => $nvm)
					{
					  $localMoves[] = array(
								'from' => $region,
								'to' => $nerby,
								'armies' => $remaining,
								);
					  $remaining -= $remaining;
					  break;
					}
				      break;
				    }
				}
			    }
			  else	/* I am not local biggest = send to bigger */
			    {
 			      $localMoves[] = array(
						    'from' => $region,
						    'to' => $keys[0],
						    'armies' => $remaining,
						    'forwardable' => true
						    );
			      $remaining -= $remaining;
			    }
			}
		    }
		}
	    }

	  /* smth still remaining and I performed some moves */
	  if($remaining > 0 && count($localMoves) > 0)
	    {
	      while($remaining > 0)
		foreach($localMoves as $key => $nvm)
		  if($remaining > 0)
		    {
		      $localMoves[$key]['armies']++;
		      $remaining--;
		    }
		  else
		    break;
	    }

	  /* merge localMoves and moves */
	  $moves = array_merge($moves,$localMoves);
	} /* end of foreach (my field) */

    /* forwarding */
    for($i = 0; $i < 5; $i++)
      {
	foreach($moves as $key => $move)
	  if(array_key_exists('forwardable',$move))
	    foreach($moves as $key2 => $move2)
	      if($move['to'] == $move2['from'] &&
		 !array_key_exists('forwardable',$move2) &&
		 (in_array($move2['to'],Storage::$neighbourList[$move['from']]) || $move2['to'] == $move['from']))
		{
		  $moves[$key]['to'] = $move2['to'];
		  unset($moves[$key]['forwardable']);
		  if($move2['to'] == $move['from'])
		    unset($moves[$key]);
		}
      }

    return $moves;
  }

  protected function remainingInSuperRegion($superRegion,$bot)
  {
    $remaining = 0;
    foreach(Storage::$superRegions[$superRegion]['regions'] as $region)
      if(!array_key_exists($region,Intelligence::$regions) || Intelligence::$regions[$region]['bot'] != $bot)
	$remaining++;
    return $remaining;
  }

  protected function isWholeSuperRegionTaken($superRegion,$bot)
  {
    return $this->remainingInSuperRegion($superRegion,$bot) > 0 ? false : true;
  }

  protected function isRegionDoomed($region)
  {
    foreach(Storage::$neighbourList[$region] as $neighbour)
      if(Intelligence::$regions[$neighbour]['bot'] == Storage::$botName['opponent_bot'])
	return true;
    return false;
  }

  protected function numArmiesMissingToTakeSuperRegion($superRegion,$bot)
  {
    $missing = 0;
    foreach(Storage::$superRegions[$superRegion]['regions'] as $region)
      if(!array_key_exists($region,Intelligence::$regions))
	$missing += 3;
      else if(Intelligence::$regions[$region]['bot'] != $bot)
	$missing += Intelligence::$regions[$region]['armies'] + 1;
      else if(!$this->isRegionDoomed($region))
	$missing -= Intelligence::$regions[$region]['armies'] - 1;
    return $missing;
  }

  protected function isInSuperRegion($superRegion,$bot)
  {
    /* foreach(Storage::$superRegions[$superRegion]['regions'] as $region) */
    /*   if(array_key_exists($region,Intelligence::$regions) && Intelligence::$regions[$region]['bot'] == Storage::$botName['opponent_bot']) */
    /* 	return false; */
    /* return true; */
    return $this->remainingInSuperRegion($superRegion,$bot) == count(Storage::$superRegions[$superRegion]['regions']) ? false : true;
  }

  protected function regionRate($region)
  {
    return count(Storage::$superRegions) - array_search(Storage::$regions[$region],array_keys(Storage::$superRegions));
  }
}
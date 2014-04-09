<?php

class SimpleExpand extends Strategy
{
  public function place()
  {
    /* array of all spawns */
    $spawn = array();

    /* armies I can spawn whole round */
    $myspawn = Intelligence::$myspawn;

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
			   Intelligence::$regions[$region]['armies'] > $max)
			  {
			    $maxRegion = $region;
			    $max = Intelligence::$regions[$region]['armies'];
			  }
		  $spawn[] = array(
				   'region' => $maxRegion,
				   'armies' => $missing
				   );
		  $freeSuperRegions[$superRegion] = 0;
		  $mySpawn -= $missing;
		}

	    /* if smth remainging put it where lowest amount missing */
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

	    /* NAIVE: if smth still remaingin put it to all equally */
	    while($mySpawn > 0)
	      {
		foreach($spawn as $key => $data)
		  {
		    if($mySpawn <= 0)
		      break;
		    $spawn[$key]['armies'] += 1;
		    $mySpawn -= 1;
		  }
	      }
	  }
      }

    /* return placement */
    return $spawn;
  }

  public function move()
  {
    $moves = array();

    foreach(Intelligence::$regions as $region => $data)
      if($data['bot'] == Storage::$botName['your_bot'])
	{
	  $opponents = array();
	  foreach(Storage::$neighbourList[$region] as $neighbour)
	    if(Intelligence::$regions[$neighbour]['bot'] == Storage::$botName['opponent_bot'])
	      $opponents[$neighbour] = Intelligence::$regions[$neighbour]['armies'];
	  asort($opponents);
	  if(count($opponents) > 0) /* fight opponents or do nothin */
	    {
	      foreach($opponents as $opponent => $armies)
		{
		  if($data['armies'] - 1 > $armies)
		    $moves[] = array(
				     'from' => $region,
				     'to' => $opponent,
				     'armies' => $data['armies'] - 1
				     );
		  break;
		}
	    }
	  else	       /* expand */
	    {
	      $remaining = $data['armies'] - 1;
	      if($remaining <= 0)
		continue;
	      $neutrals = array();
	      foreach(Storage::$neighbourList[$region] as $neighbour)
		if(Intelligence::$regions[$neighbour]['bot'] != Storage::$botName['your_bot'])
		  $neutrals[$neighbour] = Intelligence::$regions[$neighbour]['armies'];
	      asort($neutrals);
	      foreach($neutrals as $neutral => $armies)
		if($armies + 1 <= $remaining)
		  {
		    $moves[] = array(
				     'from' => $region,
				     'to' => $neutral,
				     'armies' => $armies + 1
				     );
		    $remaining -= $armies + 1;
		  }
		else
		  break;

	      /* if some armies remaining after attacks */
	      if($remaining > 0)
		{
		  if($remaining == $data['armies'] - 1)	/* if no attack has been performed */
		    {
		      $fellas = array();
		      foreach(Storage::$neighbourList[$region] as $neighbour)
			if(Intelligence::$regions[$neighbour]['bot'] == Storage::$botName['your_bot'])
			  $fellas[$neighbour] = Intelligence::$regions[$neighbour]['armies'];
		      arsort($fellas);

		      /* if I am big and have no neutrals around */
		      $keys = array_keys($fellas);
		      if(($remaining > reset($fellas) || ($remaining == reset($fellas) && $keys[0] < $region)) && !count($neutrals)) /* go towards neutrals */
			{
			  /* check if whole bonus is done */
			  if(!$this->wholeSuperRegionTaken(Storage::$regions[$region],Storage::$botName['your_bot'])) /* go to remaining */
			    {
			      $targets = array();
			      foreach($this->remainingInSuperRegionArray(Storage::$regions[$region],Storage::$botName['your_bot']) as $target)
				$targets[$target] = Storage::$floyd[$region][$target];
			      asort($targets);
			      $tkeys = array_keys($targets);
			      $neighbours = array();
			      foreach(Storage::$neighbourList[$region] as $neighbour)
				$neighbours[$neighbour] = Storage::$floyd[$neighbour][$tkeys[0]];
			      asort($neighbours);
			      foreach($neighbours as $neighbour => $nvm)
				{
				  $moves[] = array(
						   'from' => $region,
						   'to' => $neighbour,
						   'armies' => $remaining
						   );
				  break;
				}
			    }
			  else	/* go to the closest border */
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
				  $moves[] = array(
						   'from' => $region,
						   'to' => $neighbour,
						   'armies' => $remaining
						   );
				  break;
				}
			    }
			}
		      else 	/* send armies to supervisors */
			{
			  foreach($fellas as $fello => $armies)
			    if($armies > $remaining || ($armies == $remaining && $fello > $region))
			      {
				$moves[] = array(
						 'from' => $region,
						 'to' => $fello,
						 'armies' => $remaining
						 );
				break;
			      }
			}
		    }
		  else	   /* there were attacks but smth remaining */
		    {
		      $moves[count($moves)-1]['armies'] += $remaining;
		    }
		}
	    }
	}

    return $moves;
  }

  protected function remainingInSuperRegionArray($superRegion,$bot)
  {
    $remaining = array();
    foreach(Storage::$superRegions[$superRegion]['regions'] as $region)
      if(!array_key_exists($region,Intelligence::$regions) || Intelligence::$regions[$region]['bot'] != $bot)
	$remaining[] = $region;
    return $remaining;
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

  protected function numArmiesMissingToTakeSuperRegion($superRegion,$bot)
  {
    /* TODO */
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
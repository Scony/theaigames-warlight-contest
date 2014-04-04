<?php

class SimpleExpand extends Strategy
{
  public function place()
  {
    /* array of all spawns */
    $spawn = array();

    /* find all sticky enemy regions and rate them (higher rate, higher need) */
    $stick = array();
    foreach(Intelligence::$regions as $region => $data)
      if($data['bot'] == Storage::$botName['your_bot'])
	foreach(Storage::$neighbourList[$region] as $check)
	  if(Intelligence::$regions[$check]['bot'] == Storage::$botName['opponent_bot'])
	    $stick[$check] = $this->regionRate($check);

    /* sort enemy regions (more interesting ones first) */
    arsort($stick);

    /* decide what to do */
    if(!count($stick))		/* if 0 then expand */
      {
	/* lookup super regions I am in and rate em */
	$superRegions = array();
	foreach(Intelligence::$regions as $region => $data)
	  if($data['bot'] == Storage::$botName['your_bot'] &&
	     !array_key_exists(Storage::$regions[$region],$superRegions) &&
	     !$this->wholeSuperRegionTaken(Storage::$regions[$region],Storage::$botName['your_bot']))
	    $superRegions[Storage::$regions[$region]] = $this->remainingInSuperRegion(Storage::$regions[$region],Storage::$botName['your_bot']);
	
	/* sort super regions in order it's efficient to take em */
	asort($superRegions);

	/* check if I have all my fields on taken super regions */
	if(count($superRegions) > 0)
	  {
	    /* take best and spawn to best point (most neutrals nerby) */
	    foreach($superRegions as $superRegion => $nvm)
	      {
		$max = 0;
		$id = NULL;
		foreach(Storage::$superRegions[$superRegion]['regions'] as $region)
		  if(array_key_exists($region,Intelligence::$regions) &&
		     Intelligence::$regions[$region]['bot'] == Storage::$botName['your_bot'])
		    {
		      $notTakenNeighboursInSuperRegion = 0;
		      foreach(Storage::$neighbourList[$region] as $neighbour)
			if(Storage::$regions[$neighbour] == $superRegion &&
			   Intelligence::$regions[$neighbour]['bot'] != Storage::$botName['your_bot'])
			  $notTakenNeighboursInSuperRegion++;
		      if($notTakenNeighboursInSuperRegion > $max)
			{
			  $max = $notTakenNeighboursInSuperRegion;
			  $id = $region;
			}
		    }
		$spawn[] = array(
				 'region' => $id,
				 'armies' => Intelligence::$mySpawn
				 );
		break;
	      }
	  }
	else
	  {
	    /* spawn to lowest border */
	    $borders = array();
	    foreach(Intelligence::$regions as $region => $data)
	      if($data['bot'] == Storage::$botName['your_bot'])
		$borders[$region] = $data['armies'];
	    asort($borders);
	    foreach($borders as $region => $armies)
	      {
		$spawn[] = array(
				 'region' => $$region,
				 'armies' => Intelligence::$mySpawn
				 );
		break;
	      }
	  }
      }
    else			/* else attack */
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
			     'armies' => Intelligence::$mySpawn - 1
			     );
	    break;
	  }

	/* spawn remaining 1 army to the best, safe region */
	$safes = array();
	foreach(Intelligence::$regions as $region => $data)
	  if($data['bot'] == Storage::$botName['your_bot'])
	    {
	      $safe = true;
	      foreach(Storage::$neighbourList[$region] as $check)
		if(Intelligence::$regions[$check]['bot'] == Storage::$botName['opponent_bot'])
		  {
		    $safe = false;
		    break;
		  }
	      if($safe)
		$safes[$region] = $data['armies'];
	    }
	arsort($safes);
	foreach($safes as $region => $armies)
	  {
	    $spawn[] = array(
			     'region' => $region,
			     'armies' => 1
			     );
	    break;
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
		if($armies * 2 <= $remaining)
		  {
		    $moves[] = array(
				     'from' => $region,
				     'to' => $neutral,
				     'armies' => $armies * 2
				     );
		    $remaining -= $armies * 2;
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
			      foreach($neighbours as $neighbour)
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
			      foreach($neighbours as $neighbour)
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

  protected function wholeSuperRegionTaken($superRegion,$bot)
  {
    return $this->remainingInSuperRegion($superRegion,$bot) > 0 ? false : true;
  }

  protected function numSuperRegionDoors($superRegion)
  {
    $doors = 0;
    foreach(Storage::$superRegions[$superRegion]['regions'] as $region)
      {
	foreach(Storage::$neighbourList[$region] as $neighbour)
	  if(!in_array($neighbour,Storage::$superRegions[$superRegion]['regions']))
	    $doors++;
      }
    return $doors;
      
  }

  protected function regionRate($region)
  {
    return -$this->numSuperRegionDoors(Storage::$regions[$region]);
  }
}
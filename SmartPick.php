<?php

class SmartPick extends Strategy
{
  public function pick()
  {
    /* append keys (to preserve) */
    foreach(Storage::$superRegions as $superRegion => $data)
      Storage::$superRegions[$superRegion]['key'] = $superRegion;

    /* sort super regions */
    foreach(Storage::$superRegions as $superRegion => $data)
      {
	$values[$superRegion] = $data['value'];
	$doors[$superRegion] = count($data['doors']);
      }
    array_multisort($values,SORT_ASC,$doors,SORT_DESC,Storage::$superRegions);

    /* restore keys and tidy */
    $keys = array();
    foreach(Storage::$superRegions as $superRegion => $data)
      $keys[] = $data['key'];
    Storage::$superRegions = array_combine($keys,Storage::$superRegions);
    foreach(Storage::$superRegions as $superRegion => $data)
      unset(Storage::$superRegions[$superRegion]['key']);

    /* for first 3 */
    $re = array();
    $i = 0;
    foreach(Storage::$superRegions as $superRegion => $data)
      {
	if($i++ >= 3)
	  break;

	/* make set of furthest */
	$furthest = array();
	foreach($data['regions'] as $region)
	  {
	    $distance = 0;
	    foreach($data['doors'] as $door)
	      $distance += Storage::$floyd[$region][$door];
	    $furthest[$region] = $distance;
	  }
	arsort($furthest);
	$max = reset($furthest);
	foreach($furthest as $region => $distance)
	  if($distance < $max)
	    unset($furthest[$region]);

	/* make set of possibilities and count distance from furthest*/
	$picks = array();
	foreach($data['picks'] as $pick)
	  {
	    $picks[$pick] = 0;
	    foreach($furthest as $region => $distance)
	      $picks[$pick] += Storage::$floyd[$pick][$region];
	  }

	/* prefer furthest or anti-furthest */
	if(in_array(0,$picks))
	  asort($picks);
	else
	  arsort($picks);

	$re = array_merge($re,array_keys($picks));
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
}
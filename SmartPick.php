<?php

class SmartPick extends Strategy
{
  public function pick()
  {
    /* make super region list */
    $superRegions = array();
    foreach(Storage::$startingRegions as $region)
      if(!array_key_exists(Storage::$regions[$region],$superRegions))
	$superRegions[Storage::$regions[$region]] = array(
							  'superRegion' => Storage::$regions[$region],
							  'picks' => array($region),
							  'value' => Storage::$superRegions[Storage::$regions[$region]]['value'],
							  'doors' => $this->numSuperRegionDoors(Storage::$regions[$region])
							  );
      else
	$superRegions[Storage::$regions[$region]]['picks'][] = $region;

    /* sort it */
    foreach($superRegions as $region => $data)
      {
	$value[$region] = $data['value'];
	$doors[$region] = $data['doors'];
      }
    array_multisort($value,SORT_ASC,$doors,SORT_DESC,$superRegions);
    /* var_dump($superRegions); */

    /* for first 3 */
    $re = array();
    for($i = 0; $i < 3; $i++)
      {
	/* make set of doors (regions from other super regions but sticky to this one) */
	$doors = array();
	foreach(Storage::$superRegions[$superRegions[$i]['superRegion']]['regions'] as $region)
	  {
	    foreach(Storage::$neighbourList[$region] as $neighbour)
	      if(!in_array($neighbour,Storage::$superRegions[$superRegions[$i]['superRegion']]['regions']))
		$doors[] = $neighbour;
	  }
	/* var_dump($doors); */

	/* make set of furthest */
	$furthest = array();
	foreach(Storage::$superRegions[$superRegions[$i]['superRegion']]['regions'] as $region)
	  {
	    $distance = 0;
	    foreach($doors as $door)
	      $distance += Storage::$floyd[$region][$door];
	    $furthest[$region] = $distance;
	  }
	arsort($furthest);
	$max = reset($furthest);
	foreach($furthest as $region => $distance)
	  if($distance < $max)
	    unset($furthest[$region]);
	/* var_dump($furthest); */

	/* make set of possibilities and count distance from furthest*/
	$picks = array();
	foreach($superRegions[$i]['picks'] as $pick)
	  {
	    $picks[$pick] = 0;
	    foreach($furthest as $region => $distance)
	      $picks[$pick] += Storage::$floyd[$pick][$region];
	  }
	/* var_dump($picks); */

	/* prefer furthest or anti-furthest */
	if(in_array(0,$picks))
	  asort($picks);
	else
	  arsort($picks);
	/* var_dump($picks); */
	$re = array_merge($re,array_keys($picks));
      }
    /* var_dump($re); */

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
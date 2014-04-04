<?php

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
  public static $floyd = array();

  public static $chosenStartingRegions = array();

  public static function floyd()
  {
    /* prepare matrix */
    foreach(self::$regions as $region => $nvm)
      {
	self::$floyd[$region] = array();
	foreach(self::$regions as $region2 => $nvm2)
	  if($region == $region2)
	    self::$floyd[$region][$region2] = 0;
	  else if(in_array($region2,self::$neighbourList[$region]))
	    self::$floyd[$region][$region2] = 1;
	  else
	    self::$floyd[$region][$region2] = (int)(PHP_INT_MAX / 3);
      }

    /* pure floyd */
    foreach(array_keys(self::$floyd) as $k)
      {
    	foreach(array_keys(self::$floyd) as $i)
    	  {
    	    foreach(array_keys(self::$floyd) as $j)
    	      if(self::$floyd[$i][$j] > self::$floyd[$i][$k] + self::$floyd[$k][$j])
    		self::$floyd[$i][$j] = self::$floyd[$i][$k] + self::$floyd[$k][$j];
    	  }
      }
  }
}
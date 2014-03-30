<?php

class DecisionMaker
{
  private static $strategy = NULL;
  public static $started = false;

  public static function getStrategy()
  {
    if(self::$started)
      {
    	self::$strategy = new StackSpread;
    	self::$started = false;
      }
    if(!self::$strategy)
      {
	self::$strategy = new SimpleExpand;
	self::$started = true;
      }

    return self::$strategy;
  }
}
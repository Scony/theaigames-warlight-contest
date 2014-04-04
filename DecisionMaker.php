<?php

class DecisionMaker
{
  private static $strategy = NULL;

  public static function getStrategy()
  {
    if(get_class(self::$strategy) == 'SimplePick')
      self::$strategy = new SimpleExpand;
    if(!self::$strategy)
      self::$strategy = new SimplePick;
    if(Intelligence::$hisSpawn >= Intelligence::$mySpawn * 2 && get_class(self::$strategy) != 'RunForrestI')
      self::$strategy = new RunForrestI;

    $str = get_class(self::$strategy);
    $rnd = Intelligence::$round;
    stderr("Round $rnd: $str\n");
    return self::$strategy;
  }
}
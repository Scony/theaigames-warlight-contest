<?php

class DecisionMaker
{
  private static $strategy = NULL;

  public static function getStrategy()
  {
    if(!self::$strategy)
      self::$strategy = new StackSpread;

    return self::$strategy;
  }
}
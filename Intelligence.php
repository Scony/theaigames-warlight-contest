<?php

class Intelligence
{
  public static $ownership = array();
  public static $regions = array();

  public static $mySpawn = NULL;
  public static $hisSpawn = NULL;

  public static function update($updates)
  {
    foreach($updates as $update)
      self::$regions[$update['region']] = array(
						'bot' => $update['bot'],
						'armies' => $update['armies']
						);
  }
}
<?php

abstract class Strategy
{
  public function pick()
  {
    return array();
  }
  abstract function place();
  abstract function move();
}
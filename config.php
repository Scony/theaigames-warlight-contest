<?php

function __autoload($name)
{
  require_once($name.'.php');
}

function stdout($s)
{
  echo $s;
}

function stderr($s)
{
  file_put_contents('php://stderr',$s);
}

<?php
@include('__main__');
include('config.php');

$strategy = DecisionMaker::getStrategy();

while($line = fgets(STDIN))
  {
    $re = Parser::parseLine($line);
    switch($re)
      {
      case 1:
	stdout(Parser::makePick($strategy->pick()));
	break;
      case 2:
	$strategy = DecisionMaker::getStrategy();
	stdout(Parser::makePlace($strategy->place()));
	break;
      case 3:
	stdout(Parser::makeMove($strategy->move()));
	break;
      }
  }

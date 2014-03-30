<?php
@include('__main__');
include('config.php');

$strategy = DecisionMaker::getStrategy();

/* main loop */
while($line = fgets(STDIN))
  {
    $re = Parser::parseLine($line);
    switch($re)
      {
      case 1:
	file_put_contents('php://stderr',"TROLOLO\n");
	echo Parser::makePick($strategy->pick());
	break;
      case 2:
	echo Parser::makePlace($strategy->place());
	break;
      case 3:
	echo Parser::makeMove($strategy->move());
	break;
      }
  }

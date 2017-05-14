<?php
require_once ('MongoBuilder.php');

$args = $argv;
unset($args[0]);
$query = implode(" ", $args);
$builder = new \MEV\MongoBuilder();
$cursor = $builder->query($query);
$builder->showResult($cursor);
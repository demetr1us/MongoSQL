<?php

define('BASE_PATH', realpath(dirname(__FILE__)));
function my_autoloader($class)
{
    $filename = BASE_PATH . '/' . str_replace('\\', '/', $class) . '.php';
    include($filename);
}
spl_autoload_register('my_autoloader');

$args = $argv;
unset($args[0]);
$query = implode(" ", $args);
$builder = new \MEV\MongoBuilder();
$cursor = $builder->query($query);
$builder->showResult($cursor);
<?php
/**
 * Created by PhpStorm.
 * User: tamaskovacs
 * Date: 2017. 04. 07.
 * Time: 14:30
 */

require_once '../vendor/autoload.php';
Predis\Autoloader::register();

$redisConfig = [
	'scheme' => 'tcp',
	'host' => 'redis',
	'port' => 6379
];
$redisConfigJson = json_encode($redisConfig);

define('ITERATIONS', $argv[1]);

echo "Running " . ITERATIONS . " iterations for the SET command: " . PHP_EOL;

// PREDIS
$predis = new Predis\Client($redisConfig);
$start = microtime(true);

for ($i = 0; $i < ITERATIONS; $i++) {
	$predis->set('test:' . $i, 'test:value:' . $i);
	//$predisValue = $predis->get('test:' . $i);
}

$finish = microtime(true);
$predisRunTime = ($finish - $start);

echo PHP_EOL . "Predis finished in:   " . number_format($predisRunTime * 1000, 2) . " ms" . PHP_EOL;

// PHPREDIS
$redis = new Redis();
$redis->connect($redisConfig['host'], $redisConfig['port']);

$start = microtime(true);

for ($i = 0; $i < ITERATIONS; $i++) {
	$redis->set('test:' . $i, 'test:value:' . $i);
	//$redisValue = $redis->get('test:' . $i);
}


$finish = microtime(true);
$redisRunTime = ($finish - $start);

echo "Phpredis finished in: " . number_format($redisRunTime * 1000, 2) . " ms" . PHP_EOL;
echo "-----------------------------------" . PHP_EOL;
echo "Predis is " . number_format(($predisRunTime / $redisRunTime), 2) . " times slower" . PHP_EOL;
//echo "Phpredis is " . number_format(100 - ($redisRunTime / $predisRunTime) * 100, 2) . "% faster" . PHP_EOL;




// PREDIS PIPELINE
$pipe = $predis->pipeline();

for ($i = 0; $i < ITERATIONS; $i++) {
	$pipe->set('test:' . $i, 'test:value:' . $i);
}

$pipe->execute();

$finish = microtime(true);
$predisRunTime = ($finish - $start);

echo PHP_EOL . "Predis (pipeline) finished in:   " . $predisRunTime . " s" . PHP_EOL;


// PHPREDIS PIPELINE
$start = microtime(true);

$pipe = $redis->multi(Redis::PIPELINE);

for ($i = 0; $i < ITERATIONS; $i++) {
	$pipe->set('test:' . $i, 'test:value:' . $i);
}

$pipe->exec();

$finish = microtime(true);
$redisRunTime = ($finish - $start);

echo "Phpredis (pipeline) finished in: " . $redisRunTime . " s" . PHP_EOL;
echo "-----------------------------------" . PHP_EOL;
echo "Predis (pipeline) is " . number_format(($predisRunTime / $redisRunTime), 2) . " times slower" . PHP_EOL . PHP_EOL;
//echo "Phpredis (pipeline) is " . number_format(100 - ($redisRunTime / $predisRunTime) * 100, 2) . "% faster" . PHP_EOL . PHP_EOL;



// CLOSE CONNECTIONS
$predis->disconnect();
$redis->close();


<?php

/**
 * New type of rldl routing. File to manager admin routing. Beta Version
 */
require dirname(__FILE__) . '/../../classes/Slim/Slim.php';
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim(array(
	'debug' => true
		));

$app->group('/admin', function () use ($app) {
	$app->group('/client', function () use ($app) {
		include('admin/client.php');
	});

	$app->group('/template', function () use ($app) {
		include('admin/template.php');
	});
	
	$app->group('/admin', function () use ($app) {
		include('admin/admin.php');
	});
});

$app->run();

<?php

$app->get('/:id', function ($id) {
	$objClient = new \RLDL2\Client\Model\Client($id);
	$response['data'] = $objClient->getOne();
});

$app->post('/search', function() use ($app) {
	$objClient = new \RLDL2\Client\Model\Client();
	
	$whereArray = $app->request->post();
	array_push($whereArray, array(
		'user_id' => $objClient->auth->userId()
	));
	
	$response['data'] = $objClient->getAll($whereArray);
});

$app->post('/add', function() use ($app) {
	$objClient = new \RLDL2\Client\Model\Client();
	$id = $objClient->insert($app->request->post());
	$response['client_id'] = $id;
});

$app->post('/assign', function() use ($app) {
	$clientService = new \RLDL2\Client\Service\ClientManagement();
	$clientService->assignClient($app->request->post());
	
});

$app->put('/:id', function ($id) {
	echo $id;
});

$app->delete('/:id', function ($id) {
	$objClient = new \RLDL2\Client\Model\Client($id);
	$objClient->delete();
});
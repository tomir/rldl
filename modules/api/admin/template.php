<?php

$app->get('/:id', function ($id) {
	$objTemplate = new \RLDL2\Api\Model\ClientTemplate($id);
	$response['data'] = $objTemplate->getOne();
});

$app->get('/', function ($app) {
	$objTemplate = new \RLDL2\Client\Model\ClientTemplate();
	
	$whereArray = array(
		'template_user_id' => $objTemplate->auth->userId()
	);
	$res1 = $objTemplate->getAll($whereArray);
	
	$whereArray = array(
		'template_public' => 1
	);
	$res2 = $objTemplate->getAll($whereArray);
	$response['data'] = array_merge($res1, $res2);
});

$app->post('/search', function ($app) {
	$objTemplate = new \RLDL2\Client\Model\ClientTemplate();
	$response['data'] = $objTemplate->getAll($app->request->post());
});

$app->post('/add', function ($app) {
	$objTemplate = new \RLDL2\Client\Model\ClientTemplate();
	$id = $objTemplate->insert($app->request->post());
	$response['template_id'] = $id;
});

$app->put('/:id', function ($id) {
	echo $id;
});

$app->delete('/:id', function ($id) {
	$objTemplate = new \RLDL2\Client\Model\ClientTemplate($id);
	$objTemplate->delete();
});
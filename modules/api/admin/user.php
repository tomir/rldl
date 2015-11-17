<?php

$app->get('/:id', function ($id) {
	$objUser = new \RLDL2\User\Model\User($id);
	$response['data'] = $objUser->getOne();
});

$app->get('/', function ($id) {
	$objUser = new \RLDL2\User\Model\User($id);
	$response['data'] = $objUser->getAll();
});

$app->post('/add', function() use ($app) {
	$objUser = new \RLDL2\User\Model\User();
	$id = $objUser->insert($app->request->post());
	$response['user_id'] = $id;
});

$app->delete('/:id', function ($id) {
	$objUser = new \RLDL2\User\Model\User($id);
	$objUser->delete();
});
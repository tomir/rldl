<?php

$app->get('/:id', function ($id) {
	$objUser = new \RLDL2\User\Model\User($id);
	$response['data'] = $objUser->getOne();
});

$app->get('/adminType', function ($id) {
	$objAdmin = new \RLDL2\Api\Model\AdminType();
	$response['data'] = $objAdmin->getAll();
});

$app->post('/add', function() use ($app) {
	$objAdmin = new \RLDL2\Client\Model\Admin();
	$id = $objAdmin->insert($app->request->post());
	$response['admin_id'] = $app->request->post()['user_id'];
});

$app->delete('/:id', function ($id) {
	$objAdmin = new \RLDL2\Client\Model\Admin($id);
	$objAdmin->delete();
});
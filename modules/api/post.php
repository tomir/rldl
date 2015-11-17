<?php
$id=$route->getParam();
$post=new RLDL\Post($id);

switch ($route->method()) {
	case 'get':
		$response=$post->get();
	break;
	case 'post':
		$response=array_merge(array('sent'=>$post->send()), $post->get());
	break;
	case 'put':
		$post->update(array(
			'post_message'=>$route->request('post_message'),
			'post_link'=>$route->request('post_link', 'url'),
			'post_image'=>$route->request('post_image', 'url')
		));
		if ($route->request('send','bool')) {
			$response=array_merge(array('sent'=>$post->send()), $post->get());
		}
		else {
			$response=$post->get();
		}
	break;
	default:
		throw new \Exception(
			'Bad request.',
			400
		);
}
?>
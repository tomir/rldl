<?php


$image_types=array('cover', 'cover_alternative', 'background', 'logo', 'map', 'widget');

$method = $route->method();
$id = $route->getParam();

switch ($route->getParam()) {
	case 'announcements':
		switch ($method) {
			case 'get':
				$campaign = RLDL\Campaign::getItem($id);
				$response['data'] = $campaign->getAnnouncements();
				break;
			default:
				throw new \Exception(
				'Bad request.', 400
				);
		}
		break;
	case 'announcement':
		$campaign = RLDL\Campaign::getItem($id);
		switch ($method) {
			case 'get':
				$response = $campaign->getAnnouncement($route->getParam());
				break;
			case 'post':
				$response['announcement_id'] = $campaign->createAnnouncement($route->request('announcement_time', 'string'), $route->request('announcement_options', 'array'));
				break;
			case 'delete':
				$announcement_id = $route->getParam();
				if ($campaign->deleteAnnouncement($announcement_id)) {
					$response['announcement_id'] = $announcement_id;
				}
				break;
			default:
				throw new \Exception(
				'Bad request.', 400
				);
		}
		break;
	case 'stats':
		switch ($method) {
			case 'get':
				$campaign = RLDL\Campaign::getItem($id);
				foreach (array('count', 'count_in_days', 'best_days', 'best_hours', 'best_deals', 'from_mobile', 'gender', 'number_of_downloads', 'followers_count', 'followers_count_in_days', 'best_users', 'affiliations', 'views_in_days', 'downloads_in_days') as $key) {
					if (in_array($key, array('count_in_days', 'followers_count_in_days'))) {
						if ($route->request($key, 'int') !== null && $route->request($key, 'int') >= 0) {
							$response[$key] = $campaign->stats($key, $route->request($key, 'int'));
						}
					} else if (in_array($key, array('best_users')) && $route->request($key, 'bool') && $route->request('with_details', 'bool')) {
						$response[$key] = array();
						foreach ($campaign->stats($key) as $id => $value) {
							try {
								$response[$key][] = RLDL\User::getUser($id)->get();
							} catch (Exception $e) {
								
							}
						}
					} else if (in_array($key, array('affiliations')) && $route->request($key, 'bool') && $route->request('with_details', 'bool')) {
						$client = RLDL\Client::getItem($campaign->get()['client_id']);
						$response[$key] = array();
						foreach ($campaign->stats($key) as $id => $value) {
							try {
								$response[$key][] = array(
									'affiliation' => $client->getAffiliation($id),
									'count' => $value
								);
							} catch (Exception $e) {
								
							}
						}
					} else if ($route->request($key, 'bool')) {
						$response[$key] = $campaign->stats($key);
					}
				}
				break;
			default:
				throw new \Exception(
				'Bad request.', 400
				);
		}
		break;
	case 'affiliation':
		$campaign = RLDL\Campaign::getItem($id);
		switch ($method) {
			case 'post':
				$campaign->setAffiliation($route->request('string', 'string'));
				$response['affiliation_id'] = $campaign->getAffiliation();
				break;
			case 'delete':
				$campaign->setAffiliation(null);
				$response['affiliation_id'] = null;
				break;
			case 'get':
				$response['affiliation_id'] = $campaign->getAffiliation();
				break;
			default:
				throw new \Exception(
				'Bad request.', 400
				);
		}
		break;
	case 'follow':
		$campaign = RLDL\Campaign::getItem($id);
		switch ($method) {
			case 'post':
				$response['follow'] = $campaign->follow();
				break;
			case 'delete':
				$response['follow'] = $campaign->unfollow();
				break;
			default:
				throw new \Exception(
				'Bad request.', 400
				);
		}
		break;
	case 'order':
		$campaign = RLDL\Campaign::getItem($id);
		switch ($method) {
			case 'put':
				$response['deals'] = $campaign->setDealsOrder($route->request('deals', 'array'));
				break;
			default:
				throw new \Exception(
				'Bad request.', 400
				);
		}

		break;
	case 'image':
		$image = $route->getParam();
		if (!in_array($image, $image_types)) {
			throw new \Exception(
			'Bad request.', 400
			);
		}
		$campaign = RLDL\Campaign::getItem($id);
		switch ($method) {
			case 'get':
				$response = $campaign->getImage($image);
				break;
			case 'put':
			case 'post':
				$file = $route->file();
				if (!is_null($file)) {
					$campaign->uploadImage($image, $file);
				} else {
					$campaign->setImage($image, $route->request('url', 'url'));
				}
				$response = $campaign->getImage($image);
				break;
			case 'delete':
				$campaign->deleteImage($image);
				break;
			default:
				throw new \Exception(
				'Bad request.', 400
				);
		}
		break;
	case 'video':
		$campaign = RLDL\Campaign::getItem($id);
		switch ($method) {
			case 'get':
				$response = $campaign->getImage($image);
				break;
			case 'put':
			case 'post':
				$url = $route->request('url', 'url');
				if ($url != null) {
					$campaign->setVideo($route->request('url', 'url'));
				}
				$response = $campaign->getVideo();
				break;
			case 'delete':
				$campaign->deleteVideo();
				break;
			default:
				throw new \Exception(
				'Bad request.', 400
				);
		}
		break;
	case 'merge':
		switch ($method) {
			case 'put':
				$campaign = RLDL\Campaign::getItem($id);

				$campaign_id = $route->request('campaign_id', 'int');
				$campaign_alias = $route->request('campaign_alias', 'string');
				$campaign->mergeWith($campaign_alias != null ? $campaign_alias : $campaign_id, $route->request('create_alias', 'bool'));
				$response = RLDL\Campaign::getItem($campaign_alias != null ? $campaign_alias : $campaign_id)->get();
				break;
			default:
				throw new \Exception(
				'Bad request.', 400
				);
		}
		break;
	case 'alias':
		$campaign = RLDL\Campaign::getItem($id);
		switch ($method) {
			case 'delete':
				$alias = $route->getParam();
				$campaign->deleteAlias($alias);
				$response['alias'] = $alias;
				break;
			case 'post':
				$alias = $route->request('campaign_alias', 'string');
				$campaign->createAlias($alias);
				$response['alias'] = $alias;
				break;
			default:
				throw new \Exception(
				'Bad request.', 400
				);
		}
		break;
	case 'tags':
		$campaign = RLDL\Campaign::getItem($id);
		switch ($method) {
			case 'get':
				$tags = $campaign->getTags();
				$response['tags'] = $tags;
				break;
			case 'delete':
				$tags = $route->request('tags','array');
				$campaign->deleteTags($tags);
				break;
			case 'put':
				$tags = $route->request('tags','array');
				$campaign->updateTags($tags);
				break;
			case 'post':
<<<<<<< HEAD
				$tags = $route->request();
=======
				$tags = $route->request('tags','array');
>>>>>>> 9cada7a4033eeb10a0972769665d8a33d4b7622e
				$campaign->createTags($tags);
				break;
			default:
				throw new \Exception(
				'Bad request.', 400
				);
		}
		break;
	case 'deals':
		case 'get':
			$campaign = RLDL\Campaign::getItem($id);
			$response['data']=array();
			foreach ($campaign->deals($route->request('all', 'bool')) as $item) {
				$deal = $item->get();
				if ($route->request('variants', 'bool')) {
					$deal['variants'] = $item->getVariants();
				}

				if ($route->request('images', 'bool')) {
					$deal['images'] = $item->getImages();
				}
				$response['data'][] = $deal;
			}
		break;
		default:
			throw new \Exception(
			'Bad request.', 400
			);
	break;
	case null:
		switch ($method) {
			case 'get':
				$hint = false;
				if ($route->request('hint', 'bool')) {
					try {
						$campaign = RLDL\Campaign::getItem($id);
					} catch (Exception $e) {
						$campaigns = RLDL\Campaign::findItems($id, 1);

						if (count($campaigns) !== 1) {
							throw new Exception(
							'Campaign not exists.', 404
							);
						} else {
							$campaign = $campaigns[0];
							$hint = true;
						}
					}
				} else {
					$campaign = RLDL\Campaign::getItem($id);
				}
				$response = $campaign->get();

				if ($hint) {
					$response['campaign_hint'] = true;
				}

				if ($route->request('terms', 'bool')) {
					$terms = $campaign->getTerms();
					if (is_array($terms)) {
						$response = array_merge($terms, $response);
					}
				}
				if ($route->request('images', 'bool')) {
					$response['images'] = $campaign->getImages();
				}
				if ($route->request('avatars', 'bool')) {
					$avatars = $campaign->getAvatars();
					if (count($avatars) > 0) {
						$response['avatars'] = $avatars;
					}
				}
				if ($route->request('aliases', 'bool')) {
					$response['aliases'] = $campaign->getAliases();
				}
				if ($route->request('deals', 'bool')) {
					foreach ($campaign->deals($route->request('all', 'bool')) as $item) {
						$deal = $item->get();
						if ($route->request('variants', 'bool')) {
							$deal['variants'] = $item->getVariants();
						}

						if ($route->request('images', 'bool')) {
							$deal['images'] = $item->getImages();
						}
						$response['deals'][] = $deal;
					}
				}
				if ($route->request('tags', 'bool')) {
					$response['tags'] = $campaign->getTags();
				}
				break;
			case 'put':
			case 'post':
				if ($method == 'put') {
					$campaign = RLDL\Campaign::getItem($id);
					$campaign->update($route->request());
					
				} else {
					$campaign = RLDL\Campaign::create($route->request(), $route->request('force', 'bool'));
				}
				$images = $route->request('images', 'array');

				if (array_key_exists('video', $images)) {
					if (strlen($images['video']) == 0) {
						if ($method == 'put')
							$campaign->deleteVideo();
					}
					else if (filter_var($images['video'], FILTER_VALIDATE_URL)) {
						try {
							$campaign->setVideo($images['video']);
						} catch (Exception $e) {
							
						}
					}
				};

				foreach ($image_types as $img_name) {
					$file = $route->file($img_name);
					if ($file != null) {
						try {
							$campaign->uploadImage($img_name, $file);
						} catch (Exception $e) {
							
						}
					} else if (array_key_exists($img_name, $images)) {
						try {
							if (strlen($images[$img_name]) == 0) {
								if ($method == 'put')
									$campaign->deleteImage($img_name);
							}
							else {
								$campaign->setImage($img_name, $images[$img_name]);
							}
						} catch (Exception $e) {
							
						}
					}
				}
				
				$tags=$route->request('tags', 'array');
				if(is_array($tags) && !empty($tags)) {
					if ($method == 'put') {
						$campaign->updateTags($tags);
					}
					else {
						$campaign->createTags($tags);
					}
				}
				
				$response = $campaign->get();
				break;
			case 'delete':
				$campaign = new RLDL\Campaign($id);
				$campaign->delete();
				$response['campaign_id'] = $id;
				break;
			default:
				throw new \Exception(
				'Bad request.', 400
				);
		}
		break;
	default:
		throw new \Exception(
		'Bad request.', 400
		);
}
?>
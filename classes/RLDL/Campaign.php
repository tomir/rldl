<?php

namespace RLDL;

class Campaign {

	static protected $sql;
	static protected $auth;
	static protected $config;
	protected $id;
	protected $permission;

	protected $cache=null;
	protected $affiliation=null;
	
	static protected $allowed_img_names=array('cover', 'cover_alternative', 'background', 'logo', 'widget');

	protected static $items;

	public static function findItems($id, $limit = 1) {
		if (!is_int($limit) || $limit < 0 || $limit > 100) {
			$limit = 1;
		}

		$sql = \MySQL::getInstance();

		$campaigns = array();

		foreach ($sql->QueryArray('SELECT campaign_id, campaign_alias, `LEVENSHTEIN_RATIO`(' . \MySQL::SQLValue($id) . ', campaign_alias) as ratio FROM `[Client]Campaigns` ORDER BY ratio ASC LIMIT ' . $limit) as $campaign) {
			array_push($campaigns, self::getItem($campaign['campaign_id']));
		}
		return $campaigns;
	}

	public static function getItem($id) {
		if (self::$items == false) {
			self::$items = array();
		}
		if (!array_key_exists($id, self::$items)) {
			self::$items[$id] = new self($id);
		}
		return self::$items[$id];
	}

	public static function getItemsByClient($id) {
		$auth = Auth::getInstance();
		$sql = \MySQL::getInstance();

		if ($auth->getPermission($id) < 1) {
			throw new \Exception(
			'Not authorized for this operation.', 403
			);
		}

		$campaigns = array();
		foreach ($sql->SelectArray('[Client]Campaigns', array('campaign_old_alias IS NULL', 'client_id' => \MySQL::SQLValue($id, 'int'))) as $campaign) {
			array_push($campaigns, self::getItem($campaign['campaign_id']));
		}
		return $campaigns;
	}
	
	public static function getItemsByTag($tag, $start=null, $limit=100) {
		if (!is_string($tag)) {
			throw new \Exception(
				'Wrong tag.'
			);
		}
		
		$sql = \MySQL::getInstance();
		
		if (!is_numeric($start) || $start<0) {
			$start=null;
		}
		if (!is_numeric($limit) || $limit==0) {
			$limit=20;
		}
		
		$order=($limit>0 ? '' : '-').'tag_id';
		
		if ($start!=null) {
			$where[]='tag_id'.($limit>0 ? '>=' : '<=').$start;
		}
		$where['tag_name']=\MySQL::SQLValue($tag);
		$where['tag_hide']=0;
		
		$items=$sql->SelectArray('[Campaign]Tags', $where, null, $order, (abs($limit)+1));
		
		if ($items===false) {
			throw new \Exception(
				'DB error.'
			);
		}
		
		if (count($items)>abs($limit)) {
			$last_item=array_pop($items);
		}
		
		foreach ($items as &$item) {
			$item=self::getItem($item['campaign_id']);
		}
		
		return array_merge($items, (isset($last_item) ? array('next'=>array($last_item['tag_id'], $limit)) : array()));
	}

	public static function getRecentlyDownloadedItems($limit = 5) {
		$sql = \MySQL::getInstance();

		if ($limit <= 0)
			$limit = 1;
		else if ($limit > 5)
			$limit = 5;

		$campaigns = array();
		foreach ($sql->QueryArray('SELECT campaign_id FROM `[User]Variants` GROUP BY campaign_id ORDER BY MAX(get_time) DESC LIMIT ' . $limit) as $campaign) {
			array_push($campaigns, self::getItem($campaign['campaign_id']));
		}
		return $campaigns;
	}

	public function __construct($id = null) {
		if ((!is_numeric($id) && strlen($id) < 1) || (is_numeric($id) && $id < 1)) {
			throw new \InvalidArgumentException(
			'Wrong camapign id.', 1005
			);
		}

		self::$auth = Auth::getInstance();

		self::$sql = \MySQL::getInstance();

		self::$config = Config::getInstance();

		if (!is_numeric($id)) {
			if (($data = self::$sql->SelectSingleRowArray(
					'[Campaign]Aliases', array('campaign_alias' => \MySQL::SQLValue($id)), array('campaign_id')
					)) !== false) {
				$id = $data['campaign_id'];
			} else {
				throw new \InvalidArgumentException(
				'Campaign not exists.', 404
				);
			}
		}

		$this->id = (string) $id;
	}

	public function id() {
		return $this->id;
	}

	public function alias() {
		return $this->get()['campaign_alias'];
	}

	public function get() {
		if (is_null($this->cache)) {
			if (($data = self::$sql->SelectSingleRowArray(
					'[Client]Campaigns_with_status', array('campaign_id' => \MySQL::SQLValue($this->id), 'campaign_old_alias IS NULL'), array(
				'campaign_id',
				'client_id',
				'campaign_alias',
				'campaign_brand',
				'campaign_name',
				'campaign_description',
				'campaign_active',
				'campaign_locale',
				'campaign_balance',
				'campaign_custom_url'
					)
					)) !== false) {
				$data['campaign_active'] = Client::getItem($data['client_id'])->isActive() ? ($data['campaign_active'] == '1' ? true : false) : false;
				$data['campaign_terms_url'] = str_replace('{{campaign_id}}', $data['campaign_alias'], self::$config->get('url_terms'));
				$this->cache = $data;
			} else {
				throw new \Exception(
				'Campaign not exists.', 404
				);
			}
		}
		return $this->cache;
	}

	public function getAffiliation() {
		if ($this->affiliation != null) {
			return $this->affiliation;
		}

		$affiliation = Route::getCookie('AID_' . $this->id);

		if ($affiliation != null) {
			return explode('-', base64_decode($affiliation));
		}

		return null;
	}

	public function setAffiliation($name = null, $id = null) {
		if ($name === null) {
			Route::unsetCookie('AID_' . $this->id);

			return true;
		}

		if ($id == null) {
			$id = explode('-', $name);
		} else {
			$id = array($name, $id);
		}

		if (count($id) != 2 || !in_array($id[0], array('user', 'ad', 'custom')) || strlen($id[1]) < 1) {
			throw new \InvalidArgumentException(
			'Wrong affiliation id.'
			);
		}

		$this->affiliation = $id;

		Route::setCookie('AID_' . $this->id, base64_encode(implode('-', $id)), '+1 month');

		return true;
	}

	public function getTerms() {
		if (($data = self::$sql->SelectSingleRowArray(
				'[Campaign]Terms', array('campaign_id' => \MySQL::SQLValue($this->id))
				)) !== false) {
			return $data;
		}
		return null;
	}

	public function getAvatars() {
		return Avatar::random('campaign', $this->id, 20);
	}

	public function getImages() {
		return Image::all('campaign', $this->id);
	}

	public function setDealsOrder($order = array()) {
		$items = array();

		if (!is_array($order)) {
			$order = array();
		}
		$deals = $this->deals(true);
		foreach ($deals as $id => $deal) {
			$position = array_search($deal->id(), $order);
			$deal->setOrder($position !== false && $position < count($deals) ? $position : $id + count($deals), false);
			array_push($items, $deal->get());
		}

		Log::add('Change deals order.', array(
			'client_id' => $this->get()['client_id'],
			'campaign_id' => $this->id(),
			'user_id' => self::$auth->userId()
		));
		return $items;
	}

	public function getDeals($all = false) {
		$items = array();
		foreach ($this->deals($all) as $deal) {
			array_push($items, $deal->get());
		}
		return $items;
	}

	public function deals($all = false) {
		return Deal::getItemsByCampaign($this->id, $all);
	}

	public static function create($campaign, $aliasForce = false) {
		$auth = Auth::getInstance();
		$sql = \MySQL::getInstance();

		$campaign = array_merge(array(
			'client_id' => null,
			'campaign_alias' => null
				), $campaign);

		if (!$auth->isAuthorized()) {
			throw new \Exception(
			'Unautorized.', 401
			);
		}

		if (!is_numeric($campaign['client_id']) || $campaign['client_id'] < 1) {
			throw new \InvalidArgumentException(
			'Wrong client id.', 1003
			);
		}

		if ($auth->getPermission($campaign['client_id']) < 2) {
			throw new \Exception(
			'Not authorized for this operation.', 403
			);
		}
		if (
				!preg_match('/^[A-Za-z0-9-]+$/', $campaign['campaign_alias'])
		) {
			throw new \InvalidArgumentException(
			'Wrong campaign alias.', 1004
			);
		}

		if (
				!array_key_exists('campaign_alias', $campaign) ||
				is_numeric($campaign['campaign_alias']) ||
				!preg_match('/[A-Za-z-]+/', $campaign['campaign_alias']) ||
				substr($campaign['campaign_alias'], 0, 1) == '-' ||
				strlen($campaign['campaign_alias']) < 2
		) {
			throw new \InvalidArgumentException(
			'Wrong campaign alias.', 1001
			);
		}

		if ($aliasForce) {
			if (!self::checkAlias($campaign['campaign_alias'], false)) {
				$aliasRandom = '0';
				$aliasRandomLength = 1;
				while (!self::checkAlias($campaign['campaign_alias'] . '-' . $aliasRandom, false)) {
					$aliasRandom = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $aliasRandomLength);
					$aliasRandomLength++;
				}
				$campaign['campaign_alias'] = $campaign['campaign_alias'] . '-' . $aliasRandom;
			}
		} else if (!self::checkAlias($campaign['campaign_alias'])) {
			return false;
		}

		$sql->TransactionBegin();

		if ($sql->InsertRow('[Client]Campaigns', array(
					'client_id' => \MySQL::SQLValue($campaign['client_id'], 'int'),
					'campaign_alias' => \MySQL::SQLValue($campaign['campaign_alias'])
				)) === false) {
			throw new \InvalidArgumentException(
			$sql->Error(), 500
			);
		}

		$obj = self::getItem($sql->GetLastInsertID());

		$obj->update($campaign, false);

		$sql->TransactionEnd();

		Log::add('Add campaign.', array(
			'client_id' => $obj->get()['client_id'],
			'campaign_id' => (string) $obj->get()['campaign_id'],
			'user_id' => $auth->userId()
		));

		return $obj;
	}

	public function update($campaign, $log = true) {
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
			'Unautorized.', 401
			);
		}

		$data = $this->get();

		$campaign = array_merge($data, $campaign);

		if (self::$auth->getPermission($data['client_id']) < 2) {
			throw new \Exception(
			'Not authorized for this operation.', 403
			);
		}

		$f = array(
			'campaign_name' => \MySQL::SQLValue($campaign['campaign_name']),
			'campaign_description' => \MySQL::SQLValue($campaign['campaign_description']),
			'campaign_brand' => \MySQL::SQLValue($campaign['campaign_brand']),
			'campaign_balance' => \MySQL::SQLValue($campaign['campaign_balance'], 'int')
		);

		if ($data['campaign_alias'] != $campaign['campaign_alias'] && self::checkAlias($campaign['campaign_alias'])) {
			$f['campaign_alias'] = \MySQL::SQLValue($campaign['campaign_alias']);
		}

		if (strlen($campaign['campaign_locale']) == 5 && in_array($campaign['campaign_locale'], self::$config->get('locales'))) {
			$f['campaign_locale'] = \MySQL::SQLValue($campaign['campaign_locale']);
		} else {
			$f['campaign_locale'] = \MySQL::SQLValue(self::$config->get('default_locale'));
		}

		if (self::$sql->UpdateRow('[Client]Campaigns', $f, array(
					'campaign_id' => $this->id
				)) === false) {
			throw new \InvalidArgumentException(
			self::$sql->Error(), 500
			);
		}

		if (array_key_exists('campaign_alias', $f)) {
			$this->createAlias($data['campaign_alias']);
		}


		if (isset($campaign['terms_data'])) {
			$terms = trim($campaign['terms_data']);
			if ($terms == '') {
				self::$sql->DeleteRows('[Campaign]Terms', array(
					'campaign_id' => $this->id
				));
			} else {
				if (filter_var($terms, FILTER_VALIDATE_URL) && strpos($terms, 'http') === 0) {
					$type = 1;
				} else {
					$type = 2;
				}
				self::$sql->AutoInsertUpdate('[Campaign]Terms', array(
					'campaign_id' => $this->id,
					'terms_type' => \MySQL::SQLValue($type, 'int'),
					'terms_data' => \MySQL::SQLValue($terms)
						), array(
					'campaign_id' => $this->id
				));
			}
		} else {
			self::$sql->DeleteRows('[Campaign]Terms', array(
				'campaign_id' => $this->id
			));
		}

		if (array_key_exists('map', $campaign)) {
			if ($campaign['map'] == '') {
				$this->deleteImage('map');
			} else {
				$points = explode('|', preg_replace("/[^0-9\.|,-]+/", "", $campaign['map']));

				foreach ($points as &$point) {
					$point = explode(',', $point);
				}

				$map = Maps::create(array('markers' => $points));

				Image::set($map->staticImage(650, 366), 'campaign', 'map', $this->id(), $map->data());
			}
		}

		if ($log) {

			Log::add('Update campaign.', array(
				'client_id' => $this->get()['client_id'],
				'campaign_id' => $this->get()['campaign_id'],
				'user_id' => self::$auth->userId()
			));
		}

		return true;
	}

	public function createTags($tags) {

		$sql = \MySQL::getInstance();

		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
			'Unautorized.', 401
			);
		}

		if (is_string($tags)) {
			$tags = array($tags);
		} else if (!is_array($tags)) {
			throw new \Exception(
			'Wrong tag array structure'
			);
		}

		$data = $this->get();

		if (self::$auth->getPermission($data['client_id']) < 2) {
			throw new \Exception(
			'Not authorized for this operation.', 403
			);
		}

		$errorArray = array();
		foreach ($tags as $tag) {
			if (is_string($tag)) {
				$tag=array(
					'tag_name'=>$tag,
					'tag_hide'=>0
				);	
			}
			
			if (
					!preg_match('/^[A-Za-z0-9- ]+$/', $tag['tag_name'])
			) {
				array_push($errorArray, 'Wrong tag name ' . $tag['tag_name']);
				continue;
			}

			if (
					!array_key_exists('tag_name', $tag) ||
					is_numeric($tag['tag_name']) ||
					!preg_match('/[A-Za-z-]+/', $tag['tag_name']) ||
					substr($tag['tag_name'], 0, 1) == '-' ||
					strlen($tag['tag_name']) < 2
			) {
				array_push($errorArray, 'Wrong tag name ' . $tag['tag_name']);
				continue;
			}

			if (!self::checkTags($tag['tag_name'], $this->id())) {
				array_push($errorArray, 'Duplicated tag name for tag ' . $tag['tag_name']);
				continue;
			}

			if (self::$sql->InsertRow('[Campaign]Tags', array(
						'campaign_id' => \MySQL::SQLValue($this->id(), 'int'),
						'tag_name' => \MySQL::SQLValue(trim($tag['tag_name'])),
						'tag_hide' => \MySQL::SQLValue($tag['tag_hide'], 'int')
					)) === false) {
				array_push($errorArray, 'Create tag error ' . $tag['tag_name']);
				continue;
			}
		}

		return true;
	}

	public function deleteTags($tags) {
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
			'Unautorized.', 401
			);
		}

		$data = $this->get();

		if (self::$auth->getPermission($data['client_id']) < 2) {
			throw new \Exception(
			'Not authorized for this operation.', 403
			);
		}

		if (is_string($tags)) {
			$tags = array($tags);
		} else if (!is_array($tags)) {
			throw new \Exception(
			'Wrong tags.'
			);
		}


		foreach ($tags as $tag) {
			if (is_string($tag)) {
				$tag=array(
					'tag_name'=>$tag
				);	
			}
			
			if (!self::$sql->DeleteRows('[Campaign]Tags', array(

				'tag_name' => \MySQL::SQLValue($tag['tag_name']),
				'tag_hide' => 0,
				'campaign_id' => \MySQL::SQLValue($this->id(), 'int')

			))) {
				
			}
		}

		return true;
	}
	
	public function updateTags($tags) {
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
			'Unautorized.', 401
			);
		}

		$data = $this->get();

		if (self::$auth->getPermission($data['client_id']) < 2) {
			throw new \Exception(
			'Not authorized for this operation.', 403
			);
		}

		if (is_string($tags)) {
			$tags = array($tags);
		} else if (!is_array($tags)) {
			throw new \Exception(
			'Wrong tags.'
			);
		}
		
		self::$sql->DeleteRows('[Campaign]Tags', array(
			'tag_hide' => 0,
			'campaign_id' => \MySQL::SQLValue($this->id(), 'int')
		));
			
		return $this->createTags($tags);
	}

	public static function checkTags($tagName, $campaignId) {

		if (count(\MySQL::getInstance()->SelectArray('[Campaign]Tags', array(
							'tag_name' => \MySQL::SQLValue($tagName),
							'campaign_id' => \MySQL::SQLValue($campaignId, 'int'),
				))) > 0) {
			return false;
		}
		return true;
	}

	public function getTags() {
		$tags = self::$sql->SelectArray('[Campaign]Tags', array(
			'campaign_id' => \MySQL::SQLValue($this->id(), 'int'),
			'tag_hide' => 0
				), array('tag_name'));

		if (!is_array($tags))
			return array();

		return $tags;
	}

	public function getAliases() {
		$aliases = self::$sql->SelectArray('[Campaign]Alias', array('campaign_id' => \MySQL::SQLValue($this->id(), 'int')), array('campaign_alias'));

		if (!is_array($aliases))
			return array();

		foreach ($aliases as &$alias) {
			$alias = $alias['campaign_alias'];
		}

		return $aliases;
	}

	public function createAlias($alias) {
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
			'Unautorized.', 401
			);
		}

		if (
				is_numeric($alias) ||
				!preg_match('/[A-Za-z-]+/', $alias) ||
				substr($alias, 0, 1) == '-' ||
				strlen($alias) < 2
		) {
			throw new \InvalidArgumentException(
			'Wrong campaign alias.', 1001
			);
		}

		$data = $this->get();

		if (self::$auth->getPermission($data['client_id']) < 2) {
			throw new \Exception(
			'Not authorized for this operation.', 403
			);
		}

		if (!self::checkAlias($alias)) {
			return false;
		}

		if (self::$sql->AutoInsertUpdate('[Campaign]Alias', array(
					'campaign_id' => \MySQL::SQLValue($this->id(), 'int'),
					'campaign_alias' => \MySQL::SQLValue($alias)
						), array(
					'campaign_alias' => \MySQL::SQLValue($alias)
				)) === false) {
			throw new \Exception(
			self::$sql->Error(), 500
			);
		}

		return true;
	}

	public function deleteAlias($alias) {
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
			'Unautorized.', 401
			);
		}

		$data = $this->get();

		if (self::$auth->getPermission($data['client_id']) < 2) {
			throw new \Exception(
			'Not authorized for this operation.', 403
			);
		}

		if (!self::$sql->DeleteRows('[Campaign]Alias', array(
					'campaign_id' => $this->id(),
					'campaign_alias' => \MySQL::SQLValue($alias)
				))) {
			throw new \Exception(
			self::$sql->Error(), 500
			);
		}

		return true;
	}

	public function mergeWith($campaign_id, $create_alias = false) {
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
			'Unautorized.', 401
			);
		}

		self::$sql->TransactionBegin();

		Deal::moveToCampaign($this->id(), $campaign_id);

		$followers = array();

		$alias = $this->alias();
		$id = Campaign::getItem($campaign_id)->id();

		foreach (self::$sql->SelectArray('[Campaign]Followers', array('campaign_id' => \MySQL::SQLValue($id, 'int')), array('user_id')) as $row) {
			$followers[] = $row['user_id'];
		}


		$where = array(
			'campaign_id' => \MySQL::SQLValue($this->id(), 'int')
		);
		if (count($followers) > 0) {
			$where[] = '`user_id` NOT IN (' . implode(',', $followers) . ')';
		}

		if (!self::$sql->UpdateRow('[Campaign]Followers', array('campaign_id' => \MySQL::SQLValue($id, 'int')), $where)) {
			self::$sql->TransactionRollback();
			throw new \Exception(
			self::$sql->Error(), 500
			);
		}

		if (!self::$sql->DeleteRows('[Campaign]Followers', array(
					'campaign_id' => $this->id()
				))) {
			self::$sql->TransactionRollback();
			throw new \Exception(
			self::$sql->Error(), 500
			);
		}

		if (!self::$sql->UpdateRow('[Campaign]Alias', array('campaign_id' => \MySQL::SQLValue($id, 'int')), array('campaign_id' => \MySQL::SQLValue($this->id(), 'int')))) {
			throw new \Exception(
			self::$sql->Error(), 500
			);
		}



		if ($this->delete(false)) {
			if ($create_alias) {
				if (self::$sql->AutoInsertUpdate('[Campaign]Alias', array(
							'campaign_id' => \MySQL::SQLValue($id, 'int'),
							'campaign_alias' => \MySQL::SQLValue($alias)
								), array(
							'campaign_alias' => \MySQL::SQLValue($alias)
						)) === false) {
					$self::$sql->TransactionRollback();
					throw new \Exception(
					self::$sql->Error(), 500
					);
				}
			}

			self::$sql->TransactionEnd();
			return true;
		}

		return false;
	}

	public function getImage($name) {
		if (!in_array($name, self::$allowed_img_names)) {
			throw new \Exception(
			'Wrong image name.', 1010
			);
		}
		return Image::get('campaign', $name, $this->id());
	}

	public function setImage($name, $url = null) {
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
			'Unautorized.', 401
			);
		}

		$obj = $this->get();

		if (self::$auth->getPermission($obj['client_id']) < 2) {
			throw new \Exception(
			'Not authorized for this operation.', 403
			);
		}

		if (!in_array($name, self::$allowed_img_names)) {
			throw new \Exception(
			'Wrong image name.', 1010
			);
		}

		if (is_null($url)) {
			return Image::delete('campaign', $name, $this->id());
		}

		return Image::set($url, 'campaign', $name, $this->id());
	}

	public function uploadImage($name, $file) {
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
			'Unautorized.', 401
			);
		}

		$obj = $this->get();

		if (self::$auth->getPermission($obj['client_id']) < 2) {
			throw new \Exception(
			'Not authorized for this operation.', 403
			);
		}

		if (!in_array($name, self::$allowed_img_names)) {
			throw new \Exception(
			'Wrong image name.', 1010
			);
		}

		return Image::upload($file, 'campaign', $name, $this->id());
	}

	public function deleteImage($name) {
		return $this->setImage($name);
	}

	public function getVideo() {
		return Image::getVideo('campaign', 'video', $this->id());
	}

	public function setVideo($url = null) {
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
			'Unautorized.', 401
			);
		}

		$obj = $this->get();

		if (self::$auth->getPermission($obj['client_id']) < 2) {
			throw new \Exception(
			'Not authorized for this operation.', 403
			);
		}

		return Image::setVideo($url, 'campaign', 'video', $this->id());
	}

	public function deleteVideo() {
		return Image::deleteVideo('campaign', 'video', $this->id());
	}

	public function follow($log = true) {
		if (!self::$auth->isUser()) {
			throw new \Exception(
			'Allowed only for users.', 401
			);
		}
		if (self::$sql->AutoInsertUpdate('[Campaign]Followers', array(
					'campaign_id' => $this->id,
					'user_id' => \MySQL::SQLValue(self::$auth->user()->id(), 'int')
						), array(
					'campaign_id' => $this->id,
					'user_id' => \MySQL::SQLValue(self::$auth->user()->id(), 'int')
				)) !== false) {
			if ($log) {
				Log::add('Follow campaign.', array(
					'client_id' => $this->get()['client_id'],
					'campaign_id' => $this->get()['campaign_id'],
					'user_id' => self::$auth->userId()
				));
			}

			return true;
		}
		return false;
	}

	public function unfollow() {
		if (!self::$auth->isUser()) {
			throw new \Exception(
			'Allowed only for users.', 401
			);
		}

		try {
			Log::add('Unfollow campaign.', array(
				'client_id' => $this->get()['client_id'],
				'campaign_id' => $this->get()['campaign_id'],
				'user_id' => self::$auth->userId()
			));
		} catch (\Exception $e) {
			
		}

		return self::$sql->DeleteRows('[Campaign]Followers', array(
					'campaign_id' => $this->id,
					'user_id' => \MySQL::SQLValue(self::$auth->user()->id(), 'int')
		));
	}

	public function delete($log = true) {
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
			'Unautorized.', 401
			);
		}
		$data = $this->get();

		if (self::$auth->getPermission($data['client_id']) < 2) {
			throw new \Exception(
			'Not authorized for this operation.', 403
			);
		}

		self::$sql->TransactionBegin();

		try {
			Deal::deleteByCampaign($this->id());
		} catch (\Exception $e) {
			self::$sql->TransactionRollback();
			throw $e;
		}
		
		self::$sql->DeleteRows('[Campaign]Tags', array(
			'campaign_id' => \MySQL::SQLValue($this->id(), 'int')
		));

		if (!self::$sql->UpdateRow('[Client]Campaigns', array('campaign_alias' => \MySQL::SQLValue('_' . $this->id()), 'campaign_old_alias' => \MySQL::SQLValue($data['campaign_alias'])), array('campaign_id' => \MySQL::SQLValue($this->id(), 'int')))) {
			self::$sql->TransactionRollback();
			throw new \Exception(
			$sql->Error(), 500
			);
		}

		self::$sql->TransactionEnd();

		if (self::$auth->isUser() && $log) {
			Log::add('Delete campaign.', array(
				'client_id' => $data['client_id'],
				'campaign' => $data,
				'user_id' => self::$auth->userId()
			));
		}

		return true;
	}

	public function stats($type, $param = null) {
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
			'Unautorized.', 401
			);
		}
		$data = $this->get();

		if (self::$auth->getPermission($data['client_id']) < 1) {
			throw new \Exception(
			'Not authorized for this operation.', 403
			);
		}

		switch ($type) {
			case 'views_in_days':
				$rows = Stats\GA::getPageviews('-7 days', '-1 day', array_merge($this->getAliases(), array($this->alias(), $this->id())));
				return $rows;
				break;
			case 'downloads_in_days':
				$rows = Stats::countInDays('campaign_id', $this->id(), '-7 days', '-1 day');
				return $rows;
				break;
			case 'best_users':
				return Stats::bestUsers('campaign_id', $this->id());
				break;
			case 'best_hours':
				return Stats::bestHours('campaign_id', array($this->id()))[$this->id()];
				break;
			case 'best_days':
				return Stats::bestDays('campaign_id', array($this->id()))[$this->id()];
				break;
			case 'best_deals':
				$deals = $this->deals(true);
				$deal_ids = array();
				foreach ($deals as $deal) {
					$deal_ids[] = $deal->id();
				}
				$stats = array();
				$id = 0;
				$data = Stats::count('deal_id', $deal_ids);
				arsort($data, SORT_NUMERIC);

				foreach ($data as $deal_id => $value) {
					$stats[$id]['deal_id'] = $deal_id;
					$stats[$id]['count'] = $value;
					$stats[$id]['compared_to_best'] = round($stats[$id]['count'] / ($stats[0]['count'] > 0 ? $stats[0]['count'] : 1), 2);
					$id++;
					if ($id > 5) {
						break;
					}
				};
				return $stats;
				break;
			case 'from_mobile':
				return Stats::fromMobile('campaign_id', array($this->id()))[$this->id()];
				break;
			case 'gender':
				return Stats::gender('campaign_id', array($this->id()))[$this->id()];
				break;
			case 'number_of_downloads':
				return Stats::numberOfDownloads('campaign_id', array($this->id()))[$this->id()];
				break;
			case 'followers_count':
				return $this->followersCount();
				break;
			case 'count_in_days':
				return Stats::count('campaign_id', array($this->id()), (int) $param)[$this->id()];
				break;
			case 'followers_count_in_days':
				return $this->followersCount((int) $param);
				break;
			case 'affiliations':
				return Stats::bestAffiliations('campaign_id', $this->id());
				break;
			default:
				return Stats::count('campaign_id', array($this->id()))[$this->id()];
		}
	}

	protected function followersCount($days = false) {
		return self::$sql->QuerySingleRowArray('SELECT count(*) AS `count` FROM `[Campaign]Followers` WHERE campaign_id=' . \MySQL::SQLValue($this->id(), 'int') . ((is_numeric($days) && $days >= 0) ? " AND DATE(follow_time)>=DATE_SUB(CURRENT_DATE, INTERVAL " . $days . " DAY)" : ''))['count'];
	}

	public function createAnnouncement($time = null, $options = array()) {
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
			'Unautorized.', 401
			);
		}

		if (!is_array($options)) {
			$options = array();
		}

		$data = $this->get();

		if (self::$auth->getPermission($data['client_id']) < 2) {
			throw new \Exception(
			'Not authorized for this operation.', 403
			);
		}

		if ($time == null) {
			$date = 'NOW()';
		} else {
			$date = \MySQL::SQLValue($time, 'datetime');
			if ($date == 'NULL') {
				$date = 'NOW()';
			}
		}

		$announcement_options = array();

		foreach ($options as $key => $option) {
			if (is_string($option)) {
				switch ($key) {
					case 'title':
					case 'txt':
					case 'notify':
						if ($option == strip_tags($option)) {
							$announcement_options[$key] = $option;
						}
						break;
					case 'html':
						if ($option != strip_tags($option)) {
							$announcement_options[$key] = $option;
						}
						break;
				}
			}
		}

		if (self::$sql->InsertRow('[Campaign]Announcements', array(
					'campaign_id' => \MySQL::SQLValue($this->id(), 'int'),
					'announcement_time' => $date,
					'announcement_options' => (count($announcement_options) > 0 ? \MySQL::SQLValue(json_encode($announcement_options)) : 'NULL')
				)) === false) {
			throw new \Exception(
			$sql->Error(), 500
			);
		}

		return self::$sql->GetLastInsertID();
	}

	public function getAnnouncements($ids = null) {
		if ($ids != null && !is_array($ids)) {
			throw new \InvalidArgumentException(
			'Wrong announcement id.'
			);
		}

		if (!self::$auth->isAuthorized() && !self::$auth->isCron()) {
			throw new \Exception(
			'Unautorized.', 401
			);
		}

		$data = $this->get();

		if (!self::$auth->isCron() && self::$auth->getPermission($data['client_id']) < 1) {
			throw new \Exception(
			'Not authorized for this operation.', 403
			);
		}

		$where = array(
			'campaign_id' => \MySQL::SQLValue($this->id(), 'int'),
			'announcement_status!=2'
		);

		if (is_array($ids) && count($ids) > 0) {
			foreach ($ids as &$id) {
				$id = \MySQL::SQLValue($id, 'int');
			}

			$where[] = '(announcement_id=' . implode(' OR announcement_id=', $ids) . ')';
		}

		$data = self::$sql->SelectArray('[Campaign]Announcements', $where, null, '-announcement_time', 100);

		if ($data === false) {
			throw new \Exception(
			$sql->Error(), 500
			);
		}

		foreach ($data as &$line) {
			if (strlen($line['announcement_options'])) {
				$line['announcement_options'] = json_decode($line['announcement_options'], true);
			}
		}

		return $data;
	}

	public function getAnnouncement($id) {
		$announcement = $this->getAnnouncements(array($id));

		if (count($announcement) != 1) {
			throw new \Exception(
			'Announcement not found.', 404
			);
		}
		return array_pop($announcement);
	}

	public function deleteAnnouncement($id) {
		if (!is_numeric($id) || $id < 1) {
			throw new \InvalidArgumentException(
			'Wrong announcement id.'
			);
		}

		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
			'Unautorized.', 401
			);
		}

		$data = $this->get();

		if (self::$auth->getPermission($data['client_id']) < 2) {
			throw new \Exception(
			'Not authorized for this operation.', 403
			);
		}

		if (self::$sql->UpdateRow('[Campaign]Announcements', array(
					'announcement_status' => 2
						), array(
					'campaign_id' => \MySQL::SQLValue($this->id(), 'int'),
					'announcement_id' => \MySQL::SQLValue($id, 'int'),
					'announcement_status' => 0,
				)) === false) {
			throw new \Exception(
			self::$sql->Error(), 500
			);
		}
		return true;
	}

	public static function getAllAnnouncements($limit = 100) {
		if (!Auth::getInstance()->isSystem()) {
			throw new \Exception(
			'Only for System.'
			);
		}

		$where = array(
			'announcement_status' => 0,
			'`announcement_time`<NOW()'
		);

		$data = \MySQL::getInstance()->SelectArray('[Campaign]Announcements', $where, null, '-announcement_time', ((!is_numeric($limit) || $limit < 0 || $limit > 1000) ? : $limit));

		if ($data === false) {
			throw new \Exception(
			$sql->Error(), 500
			);
		}

		foreach ($data as &$line) {
			if (strlen($line['announcement_options']) > 0) {
				$line['announcement_options'] = json_decode($line['announcement_options'], true);
			}
		}

		return $data;
	}

	public function updateAnnouncementStatus($id, $announcement) {
		if (!is_numeric($id) || $id < 1) {
			throw new \InvalidArgumentException(
			'Wrong announcement id.'
			);
		}

		if (!self::$auth->isSystem()) {
			throw new \Exception(
			'Only for System.'
			);
		}

		$announcement = array_merge($this->getAnnouncement($id), (is_array($announcement) ? $announcement : array()));

		if (self::$sql->UpdateRow('[Campaign]Announcements', array(
					'announcement_status' => \MySQL::SQLValue(($announcement['announcement_status'] == 1 ? 1 : 0), 'int'),
					'announcement_count' => \MySQL::SQLValue(($announcement['announcement_count'] > 0 ? $announcement['announcement_count'] : 0), 'int')
						), array(
					'announcement_id' => \MySQL::SQLValue($id, 'int')
				)) === false) {
			throw new \Exception(
			self::$sql->Error(), 500
			);
		}
		return true;
	}

	public static function checkAlias($alias, $throw = true) {
		if (count(\MySQL::getInstance()->SelectArray('[Other]Aliases', array('alias' => \MySQL::SQLValue($alias)))) > 0) {
			if ($throw) {
				throw new \InvalidArgumentException(
				'Campaign alias exists.', 1002
				);
			}
			return false;
		}

		return true;
	}

}

?>
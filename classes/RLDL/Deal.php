<?php
namespace RLDL;

class Deal {
	protected $sql;
	protected static $auth;
	protected $config;
	protected $id;
	protected $cache=null;
	
	protected static $items;
	static protected $allowed_img_names=array('ico', 'ico_alternative');
	
	public static function getItem($id) {
		if (self::$items==false) {
			self::$items=array();
		}
		if (!array_key_exists($id, self::$items)) {
			self::$items[$id]=new self($id);
		}
		return self::$items[$id];
	}
	
	public static function getItemsByCampaign($id, $all=false) {
		$auth=Auth::getInstance();
		$sql=\MySQL::getInstance();
		
		$items=array();
		$where=array('campaign_id'=>\MySQL::SQLValue($id,'int'), 'deal_status<4');
		
		if (!$all) {
			array_push($where, 'deal_status=1');
			array_push($where, '(deal_valid_from<=NOW() OR deal_valid_from IS NULL)');
			array_push($where, '(deal_valid_to>=NOW() OR deal_valid_to IS NULL)');
		}
		else {
			$campaign=Campaign::getItem($id);
			
			if (!$auth->isAuthorized()) {
				throw new \Exception(
					'Unautorized.',
					401
				);
			}
			
			if ($auth->getPermission($campaign->get()['client_id'])<1) {
				throw new \Exception(
					'Not authorized for this operation.',
					403
				);
			}
		}
		
		foreach ($sql->SelectArray('[Client]Deals', $where, array('deal_id'), array('order','-deal_id')) as $item) {
			array_push($items, self::getItem($item['deal_id']));
		}
		
		return $items;
	}
	
	public static function moveToCampaign($old_campaign_id, $new_campaign_id) {
		$auth=Auth::getInstance();
		$sql=\MySQL::getInstance();
		
		if (!$auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		$campaign=Campaign::getItem($old_campaign_id);
		
		$new_campaign=Campaign::getItem($new_campaign_id);
		
		if ($auth->getPermission($campaign->get()['client_id'])<1) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		if ($campaign->get()['client_id']!=$new_campaign->get()['client_id']) {
			throw new \Exception(
				'Can`t merge to other client account.',
				1013
			);
		}
		
		if (!$sql->UpdateRow('[Campaign]Deals', array('campaign_id'=>\MySQL::SQLValue($new_campaign->id(),'int')), array('campaign_id'=>\MySQL::SQLValue($campaign->id(),'int')))) {
			throw new \Exception(
				$sql->Error(),
				500
			);
		}
		
		return true;
	}
	
	public function __construct($id=null) {
		if (!is_numeric($id) || $id<1) {
			throw new \InvalidArgumentException(
				'Wrong deal id.'
			);
		}
		
		$this->sql=\MySQL::getInstance();
		
		self::$auth=Auth::getInstance();
		
		$this->config=Config::getInstance();
		
		$this->id=(string)$id;
	}
	
	public static function findByVariant($variant_id) {
		if (!is_numeric($variant_id) || $variant_id<1) {
			throw new \InvalidArgumentException(
				'Wrong variant id.'
			);
		}
		$variant=\MySQL::getInstance()->SelectSingleRowArray('[Deal]Variants', array('variant_id'=>\MySQL::SQLValue($variant_id,'int')));
		
		if ($variant==false) {
			throw new \Exception(
				'Deal not exists.',
				404
			);
		}
		
		return self::getItem($variant['deal_id']);
	}
	
	public function id() {
		return $this->id;
	}
	
	public function get() {
		if (is_null($this->cache)) {
			if (($data=$this->sql->SelectSingleRowArray(
				'[Client]Deals',
				array('deal_id'=>\MySQL::SQLValue($this->id,'int'), 'deal_status<4')
			))!==false) {
				if (($currency=$this->sql->SelectSingleRowArray(
					'[Deal]Currency',
					array('deal_id'=>$this->id),
					array(
						'currency_name',
						'currency_symbol',
						'currency_code'
					)
				))!==false) {
					if ($currency['currency_name']!=null) {
						$data['currency']=$currency;
					}
				}
				$data['deal_limited']=($data['deal_limited']=='1' ? true : false);
				$data['deal_online']=($data['deal_online']=='1' ? true : false);
				
				$q=$this->sql->SelectSingleRowArray('[Deal]URL', array('deal_id'=>\MySQL::SQLValue($this->id,'int')), array('deal_url', 'deal_cart_url', 'deal_url_text'));
				if (isset($q['deal_url'])) {
					$data=array_merge($data, $q);
				}
				else {
					$data['deal_online']=false;
				}
				
				$this->cache=$data;
			}
			else {
				throw new \Exception(
					'Deal not exists.',
					404
				);
			}
		}
		return $this->cache;
	}
	
	public function getImages() {
		return Image::all('deal',$this->id);
	}
	public function getVariants($variant_id=null) {
		$this->get();
		if (!array_key_exists('variants', $this->cache)) {
			$data=$this->sql->SelectArray('[Deal]Variants', array('deal_id'=>$this->id));
			
			$variants=array();
			
			for ($i = 0; $i < 2; $i++) {
				$data[$i]['variant_hidden']=($data[$i]['variant_hidden']=='1' ? true : false);
				if (is_null($data[$i]['variant_cta'])) {
					unset($data[$i]['variant_cta']);
				};
				if ($data[$i]['variant_hidden']==false) {
					$data[$i]['variant_sharing']=($data[$i]['variant_value']>$data[abs($i-1)]['variant_value'] ? true : false);
					$data[$i]['variant_available']=($this->variantAvailable($data[$i]['variant_id']) ? true : false);				
					array_push($variants, $data[$i]);
				}
			}
			
			$this->cache['variants']=$variants;
		}
		
		if (is_numeric($variant_id) && $variant_id>0) {
			foreach ($this->cache['variants'] as &$variant) {
				if ($variant['variant_id']==$variant_id) {
					return $variant;
				}
			}
		}
		
		return $this->cache['variants'];
	}
	
	public function getVariant($variant_id) {
		return $this->getVariants($variant_id);
	}
	
	public function post($action=false) {
		if (self::$auth->isUser()) {
			try {
				$campaign=Campaign::getItem($this->get()['campaign_id']);
				if (!$action) {
					$post=Post::newItem(array(
						'post_uid'=>str_replace(
							array('{{campaign_id}}', '{{deal_id}}', '{{user_id}}'),
							array($this->get()['campaign_id'], $this->id(), self::$auth->user()->id()),
							$this->config->get('post_uid_campaign')
						),
						'post_link'=>str_replace(
							'{{campaign_id}}',
							$campaign->alias(),
							$this->config->get('url_campaign')
						),
						'post_type'=>'post',
						'post_info'=>array(
							'client_id'=>$this->get()['client_id'],
							'campaign_id'=>$this->get()['campaign_id'],
							'deal_id'=>$this->id()
						)
					));
				}
				else {
					$post=Post::newItem(array(
						'post_uid'=>str_replace(
							array('{{deal_id}}', '{{user_id}}'),
							array($this->id(), self::$auth->user()->id()),
							$this->config->get('post_uid_deal')
						),
						'post_link'=>str_replace(
							array('{{campaign_id}}', '{{deal_id}}'),
							array($campaign->alias(), $this->id()),
							$this->config->get('url_deal')
						),
						'post_type'=>'action',
						'post_action_type'=>($this->get()['deal_type']=='1' ? 'voucher' : 'offer'),
						'post_info'=>array(
							'client_id'=>$this->get()['client_id'],
							'campaign_id'=>$this->get()['campaign_id'],
							'deal_id'=>$this->id()
						)
					));
				}
				
				return $post->get();
			}
			catch (\Exception $e) {
			}
		}
		else {
			throw new \Exception(
				'Allowed only for users.',
				401
			);
		}
		return false;
	}
	
	public function postAction() {
		return $this->post(true);
	}
	
	public function getCode($variant_id=null, $user_obj=null, $log=true) {
		if (self::$auth->isUser() || (self::$auth->isSystem() && $user_obj!=null)) {
			if (self::$auth->isUser()) {
				$user=self::$auth->user();
			}
			else {
				$user=$user_obj;
			}
			
			$selected=null;
			
			if ($variant_id===null) $variant_id=false;
			
			foreach ($this->getVariants() as $variant) {
				if ($variant_id===true || $variant_id===false) {
					if ($variant_id==$variant['variant_sharing']) {
						$selected=$variant;
					}
				}
				else if ($variant_id==$variant['variant_id']) {
					$selected=$variant;
				}
			}
			if ($selected==null) {
				throw new \Exception(
					'Variant not exists.',
					404
				);
			}
			
			if ($selected['variant_sharing']==true && $user->permissions()['publish']==false && self::$auth->isUser()) {
				throw new \Exception(
					'Publish permission required.',
					405
				);
			}
			
			$deal=$this->get();
			
			$q=$this->sql->SelectSingleRowArray('[User]Variants', array('variant_id'=>\MySQL::SQLValue($selected['variant_id'],'int'), 'user_id'=>\MySQL::SQLValue($user->id(),'int')), array('code_value'));
			if (isset($q['code_value'])) {
				$selected['code_value']=$q['code_value'];
				$selected['code_redownload']=true;
				
				if ($user_obj==null) {
					Log::add('Get offer again.', array(
						'client_id'=>$this->get()['client_id'],
						'campaign_id'=>$this->get()['campaign_id'],
						'deal_id'=>$this->id(),
						'user_id'=>$user->id()
					));
					
					Campaign::getItem($this->get()['campaign_id'])->follow(false);
					
				}
			}
			else if ($user_obj==null) {
				if ($selected['variant_available']==false) {
					throw new \Exception(
						'Variant not available.'
					);
				}
				
				$affiliation=(new Campaign($deal['campaign_id']))->getAffiliation();
				
				if ($this->sql->TransactionBegin()) {
					if ($deal['deal_limited']==true) {
						if ($this->sql->Query('UPDATE `[Variant]Codes` SET `user_id`='.\MySQL::SQLValue($user->id(),'int').' WHERE `variant_id`='.\MySQL::SQLValue($selected['variant_id'],'int').' AND `user_id` IS NULL ORDER BY `code_id` LIMIT 1')) {
							$q=$this->sql->SelectSingleRowArray('[Variant]Codes', array('variant_id'=>\MySQL::SQLValue($selected['variant_id'],'int'),  'user_id'=>\MySQL::SQLValue($user->id(),'int')), array('code_value', 'code_id'));
						}
						else {
							$this->sql->TransactionRollback();
							throw new \Exception(
								'Database error.'
							);
						}
					}
					else {
						$q=$this->sql->SelectSingleRowArray('[Variant]Codes', array('variant_id'=>\MySQL::SQLValue($selected['variant_id'],'int'), '`user_id` IS NULL'), array('code_value', 'code_id'));
					}
					if (isset($q['code_value'])) {
						if ($selected['variant_commission']>0) {
							if ($this->sql->UpdateRow('[Client]Campaigns', array('campaign_balance'=>'`campaign_balance`-'.$selected['variant_commission']), array('`campaign_balance`>='.$selected['variant_commission'], 'campaign_id'=>\MySQL::SQLValue($deal['campaign_id'],'int')))) {
								$r=$this->sql->QueryArray('SELECT ROW_COUNT() AS `count`');
								if ($r[0]['count']!=1) {
									$this->sql->TransactionRollback();
									throw new \Exception(
										'Deal unavailable.'
									);
								}
							}
							else {
								$this->sql->TransactionRollback();
								throw new \Exception(
									'Database error.'
								);
							}	
						}
						
					
						
						if ($this->sql->InsertRow('[User]Deals', array(
							'user_id'=>\MySQL::SQLValue($user->id(),'int'),
							'code_id'=>\MySQL::SQLValue($q['code_id'],'int'),
							'affiliation_id'=>\MySQL::SQLValue($affiliation[1]),
							'affiliation_type'=>\MySQL::SQLValue($affiliation[0]),
							'get_ip'=>\MySQL::SQLValue(Route::getInstance()->getIP())
						))) {
							Campaign::getItem($deal['campaign_id'])->follow(false);
							$this->sql->TransactionEnd();
							$selected['code_value']=$q['code_value'];
							
						if ($log) {
							
							Log::add(($selected['variant_sharing']==true ? 'Get and share offer.': 'Get offer.'), array(
								'client_id'=>$this->get()['client_id'],
								'campaign_id'=>$this->get()['campaign_id'],
								'deal_id'=>$this->id(),
								'user_id'=>$user->id()
							));
						}
							
						}
						else {
							$this->sql->TransactionRollback();
							throw new \Exception(
								'Database error.'
							);
						}
					}
					else {
						$this->sql->TransactionRollback();
						throw new \Exception(
							'Deal unavailable.'
						);
					}
				}
			}
			if (array_key_exists('deal_url', $deal)) {
				$selected['code_url']=$this->parseUrlWithCode($selected);
				$selected['code_go_url']=str_replace('{{data}}', str_replace('=', '', base64_encode(json_encode(array(
					'user_id'=>$user->id(),
					'variant_id'=>$selected['variant_id'],
					'code_value'=>$selected['code_value']
				)))), Config::getInstance()->get('url_deal_frame'));
				
				if (substr($selected['code_url'], 0, 5)!='https') {
					$selected['code_go_url']=str_replace('https://', 'http://', $selected['code_go_url']);
				}
			}
			if (array_key_exists('deal_url_text', $deal)) {
				$selected['code_url_text']=$deal['deal_url_text'];
			}
			
			return $selected;
		}
		else {
			throw new \Exception(
				'Allowed only for users.',
				401
			);
		}
	}
	
	public function parseUrlWithCode($code, $cart=false) {
		$m=new \Mustache_Engine;
		
		return $m->render($cart ? $this->get()['deal_cart_url'] : $this->get()['deal_url'], array(
			'deal'=>$code,
			'md5' => function($str, \Mustache_LambdaHelper $helper){
				return md5($helper->render($str));
			}
		));
	}
	
	private function variantAvailable($variant) {
		$q=$this->sql->SelectSingleRowArray('[Variant]Codes', array('variant_id'=>\MySQL::SQLValue($variant,'int'), '`user_id` IS NULL'), array('code_value'));
		if (isset($q['code_value'])) {
			return true;
		}
		return false;
	}
	
	public function setOrder($position=0, $log=true) {
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		if (!is_numeric($position) || $position<0) {
			throw new \InvalidArgumentException(
				'Wrong position.'
			);
		}
		
		$data=$this->get();
		
		if (self::$auth->getPermission($data['client_id'])<2) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		if ($this->sql->UpdateRow('[Campaign]Deals', array('order'=>\MySQL::SQLValue($position,'int')), array('deal_id'=>\MySQL::SQLValue($this->id(),'int')))) {
			if ($log) {
			
				Log::add('Change deal order.', array(
					'client_id'=>$this->get()['client_id'],
					'campaign_id'=>$this->get()['campaign_id'],
					'deal_id'=>$this->id(),
					'user_id'=>self::$auth->userId()
				));
			
			}
			
			$this->cache['order']=$position;
			
			return true;
		}
		return false;
	}
	
	public static function create($deal) {
		$auth=Auth::getInstance();
		$sql=\MySQL::getInstance();
		
		if (!$auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		$deal=array_merge(array(
			'campaign_id'=>null,
			'deal_type'=>"0",
			'deal_limited'=>true,
			'deal_online'=>true,
			'currency_code'=>null,
			'deal_status'=>0,
			'deal_name'=>'',
			'deal_description'=>'',
			'deal_info'=>'',
			'deal_valid_from'=>null,
			'deal_valid_to'=>null,
			'deal_url'=>'',
			'deal_cart_url'=>'',
			'deal_url_text'=>'',
			'variants'=>array()
		), $deal);
		
		$campaign=Campaign::getItem($deal['campaign_id'])->get();
		
		if ($auth->getPermission($campaign['client_id'])<2) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		if (!is_array($deal['variants'])) {
			throw new \InvalidArgumentException(
				'Wrong deal variants.',
				1006
			);
		}
		
		$deal['client_id']=$campaign['client_id'];
		
		$type=array(
			(is_string($deal['deal_url']) && filter_var($deal['deal_url'], FILTER_VALIDATE_URL) && strpos($deal['deal_url'], 'http')===0) ? 1 : 0,
			$deal['deal_limited']!='false' && (bool)$deal['deal_limited']==true ? 1 : 0,
			(bool)$deal['deal_type']==true ? 1 : 0
		);
		
		$deal['variants']=array_values($deal['variants']);
		
		switch (count($deal['variants'])) {
			case 1:
				if ($type[1]==0 && (
					!array_key_exists('variant_codes', $deal['variants'][0]) ||
					!is_array($deal['variants'][0]['variant_codes']) ||
					count($deal['variants'][0]['variant_codes'])!=1
				)) {
					throw new \InvalidArgumentException(
						'Wrong variant code value.',
						1008
					);
				}
				
				$deal['variants'][1]=$deal['variants'][0];
				$deal['variants'][1]['variant_hidden']=1;
				$deal['variants'][1]['variant_hidden']=0;
				if (array_key_exists('variant_cta', $deal['variants'][0]) && strlen($deal['variants'][0]['variant_cta'])>0) {
					$deal['variants'][0]['variant_value']=array_key_exists('variant_sharing', $deal['variants'][0]) && (bool)$deal['variants'][0]['variant_sharing']==true ? 1 : 0;
					$deal['variants'][1]['variant_value']=(int)!$deal['variants'][0]['variant_value'];
				}
				else if (array_key_exists('variant_value', $deal['variants'][0]) && is_numeric($deal['variants'][0]['variant_value']) && $deal['variants'][0]['variant_value']>=1) {
					$deal['variants'][1]['variant_value']=array_key_exists('variant_sharing', $deal['variants'][0]) && (bool)$deal['variants'][0]['variant_sharing']==true ? $deal['variants'][0]['variant_value']-1 : $deal['variants'][0]['variant_value']+1;
					$deal['variants'][0]['variant_cta']=$deal['variants'][1]['variant_cta']='';
				}
				else {
					throw new \InvalidArgumentException(
						'Wrong variant value.',
						1007
					);
				}
			break;
			case 2:
				if (
					$type[1]==0 && (
						(
							!array_key_exists('variant_codes', $deal['variants'][0]) ||
							!is_array($deal['variants'][0]['variant_codes']) ||
							count($deal['variants'][0]['variant_codes'])!=1
						) ||
						(
							!array_key_exists('variant_codes', $deal['variants'][1]) ||
							!is_array($deal['variants'][1]['variant_codes']) ||
							count($deal['variants'][1]['variant_codes'])!=1
						)
					)
				) {
					throw new \InvalidArgumentException(
						'Wrong variant code value.',
						1008
					);
				}
				
				$deal['variants'][0]['variant_hidden']=$deal['variants'][1]['variant_hidden']=0;
				
				if (
					array_key_exists('variant_cta', $deal['variants'][0]) && strlen($deal['variants'][0]['variant_cta'])>0 &&
					array_key_exists('variant_cta', $deal['variants'][1]) && strlen($deal['variants'][1]['variant_cta'])>0
				) {
					$deal['variants'][0]['variant_value']=array_key_exists('variant_sharing', $deal['variants'][0]) && (bool)$deal['variants'][0]['variant_sharing']==true ? 1 : 0;
					$deal['variants'][1]['variant_value']=(int)!$deal['variants'][0]['variant_value'];
				}
				else if (
					array_key_exists('variant_cta', $deal['variants'][0]) ||
					array_key_exists('variant_cta', $deal['variants'][1]) ||
					!array_key_exists('variant_value', $deal['variants'][0]) ||
					!is_numeric($deal['variants'][0]['variant_value']) ||
					$deal['variants'][0]['variant_value']<1 ||
					!is_numeric($deal['variants'][1]['variant_value']) ||
					$deal['variants'][1]['variant_value']<1
				) {
					throw new \InvalidArgumentException(
						'Wrong variant value.',
						1007
					);
				}
			break;
			default:
				throw new \InvalidArgumentException(
					'Wrong deal variants.',
					1006
				);
		}
		
		foreach ($deal['variants'] as &$variant) {
			$variant=array_merge(array(
				'variant_value'=> 0,
				'variant_commission'=> 0,
				'variant_hidden'=> 0,
				'variant_cta'=> ''
			), $variant);
			$variant['variant_value']=(int)$variant['variant_value'];
			$variant['variant_commission']=!is_numeric($variant['variant_commission']) ? 0 : (int)$variant['variant_commission'];
			$variant['variant_hidden']=(bool)$variant['variant_hidden']==true ? 1 : 0;
			$variant['variant_cta']=(string)$variant['variant_cta'];
		}
		
		$deal['deal_status']=in_array((string)$deal['deal_status'], array('1', '3')) ? $deal['deal_status'] : 0;
		
		$valid_to=strtotime($deal['deal_valid_to']);
		
		if (!$campaign['campaign_active'] && $deal['deal_status']=='1' && ($valid_to==false || $valid_to>time()) && Client::getItem($campaign['client_id'])->activationIsAllowed()!=true) {
			throw new \Exception(
				'Too many active campaigns.',
				1012
			);
		}
				
		$sql->TransactionBegin();
		
		if ($sql->InsertRow('[Deal]Deals', array(
			'deal_name'=>\MySQL::SQLValue($deal['deal_name']),
			'deal_description'=>\MySQL::SQLValue($deal['deal_description']),
			'deal_info'=>\MySQL::SQLValue($deal['deal_info']),
			'deal_valid_from'=>\MySQL::SQLValue($deal['deal_valid_from'],'datetime'),
			'deal_valid_to'=>\MySQL::SQLValue($deal['deal_valid_to'],'datetime'),
			'deal_type'=>\MySQL::SQLValue(base_convert(implode('', $type), 2, 10),'int'),
			'deal_status'=>\MySQL::SQLValue($deal['deal_status']),
		))===false) {
			$sql->TransactionRollback();
			throw new \Exception(
				$sql->Error(),
				500
			);
		}
		
		$deal['deal_id']=$sql->GetLastInsertID();
		
		if ($type[0]==1) {
			if ($sql->InsertRow('[Deal]URL', array(
				'deal_id'=>\MySQL::SQLValue($deal['deal_id'],'int'),
				'deal_url'=>\MySQL::SQLValue($deal['deal_url']),
				'deal_cart_url'=>filter_var($deal['deal_cart_url'], FILTER_VALIDATE_URL) ? \MySQL::SQLValue($deal['deal_cart_url']) : 'NULL',
				'deal_url_text'=>\MySQL::SQLValue($deal['deal_url_text'])
			))===false) {
				$sql->TransactionRollback();
				throw new \Exception(
					$sql->Error(),
					500
				);
			}
		}
		
		if ($sql->InsertRow('[Campaign]Deals', array(
			'deal_id'=>\MySQL::SQLValue($deal['deal_id'],'int'),
			'campaign_id'=>\MySQL::SQLValue($campaign['campaign_id'],'int')
		))===false) {
			$sql->TransactionRollback();
			throw new \Exception(
				$sql->Error(),
				500
			);
		}
		
		$currency=self::currencyByCode($deal['currency_code']);
		
		if ($currency!=null && strlen($deal['currency_code'])>0 && strlen($deal['variants'][0]['variant_cta'])<1) {
			if ($sql->InsertRow('[Deal]Currencies', array(
				'deal_id'=>\MySQL::SQLValue($deal['deal_id'],'int'),
				'currency_id'=>\MySQL::SQLValue($currency,'int')
			))) {
				$sql->TransactionRollback();
				throw new \Exception(
					$sql->Error(),
					500
				);
			}
		}
		
		foreach ($deal['variants'] as &$variant) {
			if ($sql->InsertRow('[Deal]Variants', array(
				'deal_id'=>\MySQL::SQLValue($deal['deal_id'],'int'),
				'variant_value'=>\MySQL::SQLValue($variant['variant_value'],'int'),
				'variant_hidden'=>\MySQL::SQLValue($variant['variant_hidden'],'int'),
				'variant_commission'=>\MySQL::SQLValue($variant['variant_commission'],'int'),
				'variant_cta'=>strlen($variant['variant_cta'])>0 ? \MySQL::SQLValue($variant['variant_cta']) : 'NULL'
			))===false) {
				$sql->TransactionRollback();
				throw new \Exception(
					$sql->Error(),
					500
				);
			}
			
			$variant['variant_id']=$sql->GetLastInsertID();
			
			if (array_key_exists('variant_codes', $variant) && is_array($variant['variant_codes']) && count($variant['variant_codes'])>0) {
				foreach ($variant['variant_codes'] as $code) {
					$code=htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);
					if (strlen($code)>0) {
						if ($sql->InsertRow('[Variant]Codes', array(
							'variant_id'=>\MySQL::SQLValue($variant['variant_id'],'int'),
							'code_value'=>\MySQL::SQLValue($code)
						))===false) {
							$sql->TransactionRollback();
							throw new \Exception(
								$sql->Error(),
								500
							);
						}
					}
				}
			}
		}
		
		$sql->TransactionEnd();
		
		Log::add('Add offer.', array(
			'client_id'=>$deal['client_id'],
			'campaign_id'=>$deal['campaign_id'],
			'deal_id'=>(string)$deal['deal_id'],
			'user_id'=>$auth->userId()
		));
		
		$obj=self::getItem($deal['deal_id']);
		
		return $obj;
	}
	
	public function update($deal) {
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		$obj=$this->get();
		$deal=array_merge($obj, $deal);
		
		if (self::$auth->getPermission($obj['client_id'])<2) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		if (!is_array($deal['variants'])) {
			throw new \InvalidArgumentException(
				'Wrong deal variants.',
				1006
			);
		}
		
		$type=array(
			(array_key_exists('deal_url', $deal) && is_string($deal['deal_url']) && filter_var($deal['deal_url'], FILTER_VALIDATE_URL) && strpos($deal['deal_url'], 'http')===0) ? 1 : 0,
			(bool)$obj['deal_limited']==true ? 1 : 0,
			(bool)$obj['deal_type']==true ? 1 : 0
		);
		
		$deal['deal_status']=in_array((string)$deal['deal_status'], array('1', '3')) ? $deal['deal_status'] : 0;
		
		$valid_to=strtotime($deal['deal_valid_to']);
		
		if ($obj['deal_status']!='1' && $deal['deal_status']=='1' && ($valid_to==false || $valid_to>time()) && Client::getItem($obj['client_id'])->activationIsAllowed()!=true && Campaign::getItem($obj['campaign_id'])->get()['campaign_active']!=true) {
			throw new \Exception(
				'Too many active campaigns.',
				1012
			);
		}
		
		$this->sql->TransactionBegin();
		
		if (array_key_exists('variants', $deal) && is_array($deal['variants']) && count($deal['variants'])>0) {
			foreach ($this->getVariants() as $variant) {
				foreach ($deal['variants'] as $new_variant) {
					$c1=(array_key_exists('variant_cta', $new_variant) && is_string($new_variant['variant_cta']) && strlen($new_variant['variant_cta'])>0);
					$c2=(array_key_exists('variant_commission', $new_variant) && is_numeric($new_variant['variant_commission']) && $new_variant['variant_commission']>=0);
					if (
						array_key_exists('variant_sharing', $new_variant) && ($new_variant['variant_sharing']=='true' || $new_variant['variant_sharing']=='1' || $new_variant['variant_sharing']===true)==$variant['variant_sharing'] && ($c1 || $c2)
					) {
						$s=array();
						if ($c1) {
							$s['variant_cta']=\MySQL::SQLValue($new_variant['variant_cta']);
						}
						if ($c2) {
							$s['variant_commission']=\MySQL::SQLValue($new_variant['variant_commission'], 'int');
						}
						if ($this->sql->UpdateRow('[Deal]Variants', $s, array(
							'variant_id'=>\MySQL::SQLValue($variant['variant_id'],'int')
						))===false) {
							$this->sql->TransactionRollback();
							throw new \Exception(
								$this->sql->Error(),
								500
							);
						}
					}
				}
			}
		}
		
		if ($this->sql->UpdateRow('[Deal]Deals', array(
			'deal_name'=>\MySQL::SQLValue($deal['deal_name']),
			'deal_description'=>\MySQL::SQLValue($deal['deal_description']),
			'deal_info'=>\MySQL::SQLValue($deal['deal_info']),
			'deal_valid_from'=>\MySQL::SQLValue($deal['deal_valid_from'],'datetime'),
			'deal_valid_to'=>\MySQL::SQLValue($deal['deal_valid_to'],'datetime'),
			'deal_type'=>\MySQL::SQLValue(base_convert(implode('', $type), 2, 10),'int'),
			'deal_status'=>\MySQL::SQLValue($deal['deal_status'])
		), array(
			'deal_id'=>\MySQL::SQLValue($this->id(), 'int')
		))===false) {
			$this->sql->TransactionRollback();
			throw new \Exception(
				$this->sql->Error(),
				500
			);
		}
		
		if ($type[0]==1) {
			if ($this->sql->AutoInsertUpdate('[Deal]URL', array(
				'deal_id'=>\MySQL::SQLValue($this->id(),'int'),
				'deal_url'=>\MySQL::SQLValue($deal['deal_url']),
				'deal_cart_url'=>filter_var($deal['deal_cart_url'], FILTER_VALIDATE_URL) ? \MySQL::SQLValue($deal['deal_cart_url']) : 'NULL',
				'deal_url_text'=>\MySQL::SQLValue($deal['deal_url_text'])
			), array(
				'deal_id'=>\MySQL::SQLValue($this->id(), 'int')
			))===false) {
				$this->sql->TransactionRollback();
				throw new \Exception(
					$this->sql->Error(),
					500
				);
			}
		}
		else {
			$this->sql->DeleteRows('[Deal]URL', array(
				'deal_id'=>\MySQL::SQLValue($this->id(), 'int')
			));
		}
		
		$this->sql->TransactionEnd();
		
		Log::add('Update offer.', array(
			'client_id'=>$deal['client_id'],
			'campaign_id'=>$deal['campaign_id'],
			'deal_id'=>$deal['deal_id'],
			'user_id'=>self::$auth->userId()
		));
		
		return true;
	}
	
	private function changeStatus($status) {
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		if (!in_array((string)$status, array('1', '3', '4'))) {
			$status='3';
		}
		
		$obj=$this->get();
		
		if (self::$auth->getPermission($obj['client_id'])<2) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		$valid_to=strtotime($obj['deal_valid_to']);
		
		if ($obj['deal_status']!='1' && $status=='1' && ($valid_to==false || $valid_to>time()) && Client::getItem($obj['client_id'])->activationIsAllowed()!=true) {
			throw new \Exception(
				'Too many active campaigns.',
				1012
			);
		}
		
		if (!$this->sql->UpdateRow('[Deal]Deals', array('deal_status'=>\MySQL::SQLValue($status)), array(
			'deal_id'=>\MySQL::SQLValue($this->id(),'int')
		))) {
			throw new \Exception(
				$this->sql->Error(),
				500
			);
		}
		
		$this->cache['deal_status']=$status;
		
		return true;
	}
	
	public function activate() {
		$action=$this->changeStatus(1);
		
		$obj=$this->get();
		
		if ($action && self::$auth->isUser()) {
			Log::add('Activate offer.', array(
				'client_id'=>$obj['client_id'],
				'campaign_id'=>$obj['client_id'],
				'deal_id'=>$this->id(),
				'user_id'=>self::$auth->userId()
			));
		}
		
		return $action;
	}
	
	public function hide() {
		$action=$this->changeStatus(3);
		
		$obj=$this->get();
		
		if ($action && self::$auth->isUser()) {
			Log::add('Hide offer.', array(
				'client_id'=>$obj['client_id'],
				'campaign_id'=>$obj['client_id'],
				'deal_id'=>$this->id(),
				'user_id'=>self::$auth->userId()
			));
		}
		
		return $action;
	}
	
	public function getImage($name) {
		if (!in_array($name, self::$allowed_img_names)) {
			throw new \Exception(
				'Wrong image name.',
				1010
			);
		}
		return Image::get('deal',$name,$this->id());
	}
	
	
	public function setImage($name, $url=null){
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		$obj=$this->get();
		
		if (self::$auth->getPermission($obj['client_id'])<2) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		if (!in_array($name, self::$allowed_img_names)) {
			throw new \Exception(
				'Wrong image name.',
				1010
			);
		}
		
		if (is_null($url)) {
			return Image::delete('deal',$name,$this->id());
		}
		
		return Image::set($url,'deal',$name,$this->id());
	}
	
	public function uploadImage($name, $file){
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		$obj=$this->get();
		
		if (self::$auth->getPermission($obj['client_id'])<2) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		if (!in_array($name, self::$allowed_img_names)) {
			throw new \Exception(
				'Wrong image name.',
				1010
			);
		}
		
		return Image::upload($file, 'deal', $name, $this->id());
	}
	
	public function deleteImage($name) {
		return $this->setImage($name);
	}
	
	public function getCodes($variant_id=null, $start=null, $limit=100) {
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		$obj=$this->get();
		
		if (self::$auth->getPermission($obj['client_id'])<1) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		if ($variant_id===null) $variant_id=false;
		
		foreach ($this->getVariants() as $variant) {
			if ($variant_id===true || $variant_id===false) {
				if ($variant_id==$variant['variant_sharing']) {
					$selected=$variant;
				}
			}
			else if ($variant_id==$variant['variant_id']) {
				$selected=$variant;
			}
		}
		if ($selected==null) {
			throw new \Exception(
				'Variant not exists.',
				404
			);
		}
		
		if ($obj['deal_limited']==false) {
			return $this->sql->SelectSingleRowArray('[Variant]Codes', array('variant_id'=>$selected['variant_id']), array('code_value'))['code_value'];
		}
		
		if (!is_numeric($start) || $start<0) {
			$start=null;
		}
		if (!is_numeric($limit) || $limit==0) {
			$limit=20;
		}
		
		$order=($limit>0 ? '' : '-').'code_id';
		
		$where=array('variant_id'=>$selected['variant_id']);
		
		if ($start!=null) {
			$where[]='code_id'.($limit>0 ? '>=' : '<=').$start;
		}
		
		$items=$this->sql->SelectArray('[Variant]Codes', $where, null, $order, (abs($limit)+1));
		
		if ($items===false) {
			throw new \Exception(
				'DB error.'
			);
		}
		
		if (count($items)>abs($limit)) {
			$last_item=array_pop($items);
		}
		
		$code_ids=array();
		$items_by_id=array();
		
		foreach ($items as $id=>$item) {
			$code_ids[]=$item['code_id'];
			$items_by_id[$item['code_id']]=$id;
		}
		
		if (count($code_ids)>0) {
			foreach ($this->sql->QueryArray('SELECT * FROM `[User]Deals` WHERE code_id IN ('.implode(', ', $code_ids).')') as $row) {
				$items[$items_by_id[$row['code_id']]]['get_time']=$row['get_time'];
				$items[$items_by_id[$row['code_id']]]['use_time']=$row['use_time'];
				$items[$items_by_id[$row['code_id']]]['reminder_time']=$row['reminder_time'];
			}
		}
		
		return array_merge($items, (isset($last_item) ? array('next'=>array($last_item['code_id'], $limit)) : array()));;
	}
	
	public function deleteCodes($variant_id=null, $codes=array()) {
		if (!is_array($codes) || count($codes)==0) {
			return false;
		}
		
		foreach ($codes as &$code) {
			$code=\MySQL::SQLValue($code);
		}
		
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		$obj=$this->get();
		
		if (self::$auth->getPermission($obj['client_id'])<2) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		if ($variant_id===null) $variant_id=false;
		
		foreach ($this->getVariants() as $variant) {
			if ($variant_id===true || $variant_id===false) {
				if ($variant_id==$variant['variant_sharing']) {
					$selected=$variant;
				}
			}
			else if ($variant_id==$variant['variant_id']) {
				$selected=$variant;
			}
		}
		if ($selected==null) {
			throw new \Exception(
				'Variant not exists.',
				404
			);
		}
		
		if ($obj['deal_limited']==false) {
			return false;
		}
		
		$this->sql->DeleteRows('[Variant]Codes', array(
			'variant_id'=>\MySQL::SQLValue($selected['variant_id'], 'int'),
			'code_value IN ('.implode(', ', $codes).')'
		));
		
		return true;
	}
	
	public function markCodesUsed($variant_id=null, $codes=array()) {
		if (!is_array($codes) || count($codes)==0) {
			return false;
		}
		
		foreach ($codes as &$code) {
			$code=\MySQL::SQLValue($code);
		}
		
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		$obj=$this->get();
		
		if (self::$auth->getPermission($obj['client_id'])<2) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		if ($variant_id===null) $variant_id=false;
		
		foreach ($this->getVariants() as $variant) {
			if ($variant_id===true || $variant_id===false) {
				if ($variant_id==$variant['variant_sharing']) {
					$selected=$variant;
				}
			}
			else if ($variant_id==$variant['variant_id']) {
				$selected=$variant;
			}
		}
		if ($selected==null) {
			throw new \Exception(
				'Variant not exists.',
				404
			);
		}
		
		if ($obj['deal_limited']==false) {
			return false;
		}
		
		$code_ids=array();
		
		foreach ($this->sql->SelectArray('[Variant]Codes',array(
			'variant_id'=>\MySQL::SQLValue($selected['variant_id'], 'int'),
			'code_value IN ('.implode(', ', $codes).')'
		)) as $row) {
			$code_ids[]=$row['code_id'];
		};
		
		$this->sql->UpdateRow('[User]Deals', array(
			'use_time'=>'NOW()'
		), array(
			'code_id IN ('.implode(', ', $code_ids).')'
		));
		
		return count($code_ids);
	}
	
	public function addCodeData($code_id) {
		// for futute use
		return true;
	}
	
	public function addCodes($variant_id=null, $codes=array()) {
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		$obj=$this->get();
		
		if (self::$auth->getPermission($obj['client_id'])<2) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		if ($variant_id===null) $variant_id=false;
		
		foreach ($this->getVariants() as $variant) {
			if ($variant_id===true || $variant_id===false) {
				if ($variant_id==$variant['variant_sharing']) {
					$selected=$variant;
				}
			}
			else if ($variant_id==$variant['variant_id']) {
				$selected=$variant;
			}
		}
		if ($selected==null) {
			throw new \Exception(
				'Variant not exists.',
				404
			);
		}
		
		if ($obj['deal_limited']==false && $this->variantAvailable($selected['variant_id'])) {
			throw new \Exception(
				'Can`t add more than one code to unlimited deal.',
				1009
			);
		}
		
		$this->sql->TransactionBegin();
		
		$added=0;
		
		foreach ($codes as $code) {
			$code=htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);
			if (strlen($code)>0) {
				if ($this->sql->InsertRow('[Variant]Codes', array(
					'variant_id'=>\MySQL::SQLValue($selected['variant_id'],'int'),
					'code_value'=>\MySQL::SQLValue($code)
				))) {
					$added++;
				}
			}
		}
		
//		Log::add('Update offer.', array(
//			'client_id'=>$obj['client_id'],
//			'campaign_id'=>$obj['campaign_id'],
//			'deal_id'=>$obj['deal_id'],
//			'variant_id'=>$selected['variant_id'],
//			'codes_count'=>$added,
//			'user_id'=>self::$auth->userId()
//		));
		
		$this->sql->TransactionEnd();
		
		return $added;
	}
	
	public function delete(){
		$action=$this->changeStatus(4);
		
		$obj=$this->get();
		
		if ($action && self::$auth->isUser()) {
			Log::add('Delete offer.', array(
				'client_id'=>$obj['client_id'],
				'campaign_id'=>$obj['client_id'],
				'deal'=>$obj,
				'user_id'=>self::$auth->userId()
			));
		}
		
		return $action;
	}
	
	public static function deleteByCampaign($campaign_id) {
		$auth=Auth::getInstance();
		$sql=\MySQL::getInstance();
		
		if (!$auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		if (!is_numeric($campaign_id) || $campaign_id<1) {
			throw new \InvalidArgumentException(
				'Wrong campaign id.',
				1005
			);
		}
		
		$obj=Campaign::getItem($campaign_id)->get();
		
		if ($auth->getPermission($obj['client_id'])<2) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		if (!$sql->UpdateRow('[Deal]Deals', array('deal_status'=>\MySQL::SQLValue(4,'int')), 'WHERE `deal_id` IN (SELECT `deal_id` FROM `[Campaign]Deals` WHERE `campaign_id`='.$campaign_id.')')) {
			throw new \Exception(
				$sql->Error(),
				500
			);
		}
	}
	
	private static function currencyByCode($code) {
		$sql=\MySQL::getInstance();
		if (($currency=$sql->SelectSingleRowArray(
			'[App]Currencies',
			array('currency_code'=>\MySQL::SQLValue(strtoupper($code))),
			array(
				'currency_id'
			)
		))!==false) {
			return $currency['currency_id'];
		}
		return null;
	}
	
	public function stats($type, $param=null) {
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		$data=$this->get();
		
		if (self::$auth->getPermission($data['client_id'])<1) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		switch ($type) {
			case 'best_users':
				return Stats::bestUsers('deal_id', $this->id());
			break;
			case 'best_hours':
				return Stats::bestHours('deal_id', array($this->id()))[$this->id()];
			break;
			case 'best_days':
				return Stats::bestDays('deal_id', array($this->id()))[$this->id()];
			break;
			case 'with_sharing':
				$variants=$this->getVariants();
				if (count($variants)!=2) {
					return null;
				}
				$stats=Stats::count('variant_id', array($variants[0]['variant_id'], $variants[1]['variant_id']));
				
				$sum=$stats[$variants[0]['variant_id']]+$stats[$variants[1]['variant_id']];
				
				if ($sum==0) {
					return null;
				}
				
				return round(($variants[0]['variant_sharing'] ? $stats[$variants[0]['variant_id']] : $stats[$variants[1]['variant_id']])/$sum,2);
			break;
			case 'from_mobile':
				return Stats::fromMobile('deal_id', array($this->id()))[$this->id()];
			break;
			case 'gender':
				return Stats::gender('deal_id', array($this->id()))[$this->id()];
			break;
			case 'number_of_downloads':
				return Stats::numberOfDownloads('deal_id', array($this->id()))[$this->id()];
			break;
			case 'count_in_days':
				return Stats::count('deal_id', array($this->id()), (int)$param)[$this->id()];
			break;
			case 'affiliations':
				return Stats::bestAffiliations('deal_id', $this->id());
			break;
			default:
				return Stats::count('deal_id', array($this->id()))[$this->id()];
		}
	}
	
	public function createReminder($time=null, $options=array()) {
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		if (!is_array($options)) {
			$options=array();
		}
		
		$data=$this->get();
		
		if (self::$auth->getPermission($data['client_id'])<2) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		if ($time==null) {
			$date='NOW()';
		}
		else {
			$date=\MySQL::SQLValue($time,'datetime');
			if ($date=='NULL') {
				$date='NOW()';
			}
		}
		
		$reminder_options=array();
		
		foreach ($options as $key=>$option) {
			if (is_string($option)) {
				switch ($key) {
					case 'title':
					case 'txt':
					case 'notify':
						if($option==strip_tags($option)) {
						    $reminder_options[$key]=$option;
						}
					break;
					case 'html':
						if($option!=strip_tags($option)) {
						    $reminder_options[$key]=$option;
						}
					break;
				}
			}
		}
		
		if ($this->sql->InsertRow('[Deal]Reminders', array(
			'deal_id'=>\MySQL::SQLValue($this->id(),'int'),
			'reminder_time'=>$date,
			'reminder_options'=>(count($reminder_options)>0 ? \MySQL::SQLValue(json_encode($reminder_options)) : 'NULL')
		))===false) {
			throw new \Exception(
				$sql->Error(),
				500
			);
		}
		
		return $this->sql->GetLastInsertID();
	}
	
	public function getReminders($ids=null) {
		if ($ids!=null && !is_array($ids)) {
			throw new \InvalidArgumentException(
				'Wrong reminder id.'
			);
		}
		
		if (!self::$auth->isAuthorized() && !self::$auth->isCron()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		$data=$this->get();
		
		if (!self::$auth->isCron() && self::$auth->getPermission($data['client_id'])<1) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		$where=array(
			'deal_id'=>\MySQL::SQLValue($this->id(),'int'),
			'reminder_status!=2'
		);
		
		if (is_array($ids) && count($ids)>0) {
			foreach ($ids as &$id) {
				$id=\MySQL::SQLValue($id,'int');
			}
			
			$where[]='(reminder_id='.implode(' OR reminder_id=', $ids).')';
		}
		
		$data=$this->sql->SelectArray('[Deal]Reminders', $where, null, '-reminder_time', 100);
		
		if ($data===false) {
			throw new \Exception(
				$sql->Error(),
				500
			);
		}
		
		foreach ($data as &$line) {
			if (strlen($line['reminder_options'])) {
				$line['reminder_options']=json_decode($line['reminder_options'], true);
			}
		}
		
		return $data;
	}
	
	public function getReminder($id) {
		$reminder=$this->getReminders(array($id));
		
		if (count($reminder)!=1) {
			throw new \Exception(
				'Reminder not found.',
				404
			);
		}
		return array_pop($reminder);
	}
	
	public function deleteReminder($id) {
		if (!is_numeric($id) || $id<1) {
			throw new \InvalidArgumentException(
				'Wrong reminder id.'
			);
		}
		
		if (!self::$auth->isAuthorized()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		$data=$this->get();
		
		if (self::$auth->getPermission($data['client_id'])<2) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		if ($this->sql->UpdateRow('[Deal]Reminders', array(
			'reminder_status'=>2
		), array(
			'deal_id'=>\MySQL::SQLValue($this->id(),'int'),
			'reminder_id'=>\MySQL::SQLValue($id, 'int'),
			'reminder_status'=>0,
		))===false) {
			throw new \Exception(
				$this->sql->Error(),
				500
			);
		}
		return true;
	}
	
	public static function getAllReminders($limit=100) {
		if (!Auth::getInstance()->isSystem()) {
			throw new \Exception(
				'Only for System'
			);
		}
		
		$where=array(
			'reminder_status'=>0,
			'`reminder_time`<NOW()'
		);
		
		$data=\MySQL::getInstance()->SelectArray('[Deal]Reminders', $where, null, '-reminder_time', ((!is_numeric($limit) || $limit<0 || $limit>1000) ? : $limit));
		
		if ($data===false) {
			throw new \Exception(
				$sql->Error(),
				500
			);
		}
		
		foreach ($data as &$line) {
			if (strlen($line['reminder_options'])>0) {
				$line['reminder_options']=json_decode($line['reminder_options'], true);
			}
		}
		
		return $data;
	}
	
	public function updateReminderStatus($id, $reminder) {
		if (!is_numeric($id) || $id<1) {
			throw new \InvalidArgumentException(
				'Wrong reminder id.'
			);
		}
		
		if (!self::$auth->isSystem()) {
			throw new \Exception(
				'Only for System.'
			);
		}
		
		$reminder=array_merge($this->getReminder($id), (is_array($reminder) ? $reminder : array()));
		
		if ($this->sql->UpdateRow('[Deal]Reminders', array(
			'reminder_status'=>\MySQL::SQLValue(($reminder['reminder_status']==1 ? 1 : 0),'int'),
			'reminder_count'=>\MySQL::SQLValue(($reminder['reminder_count']>0 ? $reminder['reminder_count'] : 0),'int')
		), array(
			'reminder_id'=>\MySQL::SQLValue($id, 'int')
		))===false) {
			throw new \Exception(
				$this->sql->Error(),
				500
			);
		}
		return true;
	}
}
?>
<?php
namespace RLDL\Mail;

class NewItem {
	private $info=array();
	private $elements=array();
	private $mails=array();
	
	public function __construct($address=null, $bodyHTML=null, $bodyTXT=null) {
		if (!is_null($address)) {
			$this->setAddress($address);
		}
		if (!is_null($bodyHTML)) {
			$this->setHtml($bodyHTML);
		}
		if (!is_null($bodyTXT)) {
			$this->setTxt($bodyTXT);
		}
	}
	
	public function setInfo($params) {
		if (!is_array($params)) {
			throw new \InvalidArgumentException(
				'Wrong mail info data.'
			);
		}
		$this->info=$params;
	}
	
	public function setAddress($params) {
		if (!is_array($params)) {
			throw new \InvalidArgumentException(
				'Wrong mail addresses.'
			);
		}
		
		foreach ($params as $type => $value) {
			if (!array_key_exists($type, $this->elements) || !is_array($this->elements[$type])) {
				$this->elements[$type]=array();
			}
			if (in_array($type, array('to', 'from', 'cc', 'bcc', 'Reply-To'))) {
				switch (gettype($value)) {
					case 'string':
						if (filter_var($value, FILTER_VALIDATE_EMAIL) && !in_array(strtolower($value), $this->mails)) {
							array_push($this->mails, strtolower($value));
							
							$name=array();
							
							foreach (explode(' ', ucwords(str_replace(array('.','-','_'), ' ', strtolower(substr($value, 0, strpos($value, '@')))))) as $name_f) {
								if (strlen($name_f)==1) {
									$name_f.='.';
								}
								$name[]=$name_f;
							}
							array_push($this->elements[$type], array(
								'name'=>implode(' ', $name),
								'address'=>$value
							));
						}
					break;
					case 'array':
						if (array_key_exists('address', $value) && !in_array(strtolower($value['address']), $this->mails)) {
							array_push($this->mails, strtolower($value['address']));
							array_push($this->elements[$type], array(
								'name'=>(array_key_exists('name', $value) ? $value['name'] : substr($value, 0, strpos($value, '@'))),
								'address'=>$value['address']
							));
						}
						else if (!array_key_exists('address', $value)) {
							foreach ($value as $line) {
								$this->setAddress(array($type=>$line));
							}
						}
					break;
					case 'object':
						if (strpos(get_class($value), 'RLDL\User')===0 && !in_array(strtolower($value->email()), $this->mails)) {
							array_push($this->mails, strtolower($value->email()));
							array_push($this->elements[$type], array(
								'name'=>$value->name(),
								'address'=>$value->email()
							));
						}
					break;
				}
			}
		}
	}
	
	public function setTitle($params) {
		if (!is_string($params)) {
			throw new \InvalidArgumentException(
				'Wrong mail title.'
			);
		}
		$this->elements['title']=$params;
	}
	
	public function setHtml($params) {
		if (!is_string($params)) {
			throw new \InvalidArgumentException(
				'Wrong mail html body.'
			);
		}
		$this->elements['html']=$params;
	}
	
	public function setTxt($params) {
		if (!is_string($params)) {
			throw new \InvalidArgumentException(
				'Wrong mail txt body.'
			);
		}
		$this->elements['txt']=$params;
	}
	
	public function createFromTemplates($templates, $view=array()) {
		if (!is_array($templates)) {
			throw new \InvalidArgumentException(
				'Wrong mail templates.'
			);
		}
		if (!is_array($view) && !is_object($view)) {
			$view=array();
		}
		
		$m=new \RLDL\Mustache();
		
		$m->setView($view);
		
		foreach ($templates as $name => $template) {
			if (in_array($name, array('html', 'txt', 'title'))) {
				$m->setTemplateFile($name, $template);
				
				$template=$m->render($name);
				
				switch ($name) {
					case 'html':
						$this->setHtml($template);
					break;
					case 'txt':
						$this->setTxt($template);
					break;
					case 'title':
						$this->setTitle($template);
					break;
				}
			}
		}
	}
	
	public function create() {
		if (!array_key_exists('to', $this->elements) || count($this->elements['to'])<1 || (!array_key_exists('html', $this->elements) && !array_key_exists('txt', $this->elements))) {
			throw new \InvalidArgumentException(
				'Wrong mail data.'.print_r($this->elements, true)
			);
		}
		
		$sql=\MySQL::getInstance();
		
		if ($sql->InsertRow('[Mail]Mails', array(
			'mail_info'=>\MySQL::SQLValue(json_encode($this->info)),
			'mail_data'=>\MySQL::SQLValue(json_encode($this->elements))
		))===false) {
			throw new \Exception(
				'Database error.'
			);
		}
		
		return new \RLDL\Mail($sql->GetLastInsertID());
	}
	
}
?>
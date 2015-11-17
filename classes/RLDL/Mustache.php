<?php
namespace RLDL;

class Mustache {
	private $engine;
	
	private static $dir='other/templates';
	
	private static $config=null;
	
	private static $sets=null;
	
	private static $auth=null;
	
	private static $i18n=null;
	
	private static $global_view=null;
	
	private static $global_helpers=null;
	
	private $view=array();
	
	private $loader;

	public function __construct($sets=array()) {
		if (self::$config==null) {
			self::$config=Config::getInstance();
			self::$auth=Auth::getInstance();
			self::$sets=self::$config->get('mustache');
			self::$i18n=I18n::getInstance();
			self::$global_view=array(
				'i18n'=>function($s, \Mustache_LambdaHelper $helper) {
					return self::$i18n->_($helper->render($s));
				},
				'config'=>function($s) {
					return self::$config->get($s);
				}
			);
			self::$global_helpers=array(
				'image'=>function($s, \Mustache_LambdaHelper $helper) {
					$str=explode('?', $s);
					
					parse_str($str[1], $data);
					$search=array('{{w}}', '{{h}}', '{{s}}');
					$search_translated=array('[[w]]', '[[h]]', '[[s]]');
					
					if (!isset($data['w'])) {
						$data['w']=$data['s'];
					}
					if (!isset($data['h'])) {
						$data['h']=$data['s'];
					}
					
					$replace=array($data['w'], $data['h'], $data['s']);
					
					$url=str_replace($search, $replace , str_replace($search_translated, $search, $helper->render(str_replace($search, $search_translated, $str[0]))));
					
					return $url;
				},
				'barcode'=>function($s, \Mustache_LambdaHelper $helper) {
					$j=json_decode($helper->render($s), true);
					if (is_array($j) && array_key_exists('url', $j) && array_key_exists('code', $j)) { 
						return str_replace('%', $j['code'], $j['url']);
					}
					return null;
				},
				'html' => function($str, \Mustache_LambdaHelper $helper){
					$text=$helper->render($str);
					if (trim($text) !== '') {
						$text = preg_replace('|<br[^>]*>\s*<br[^>]*>|i', "\n\n", $text . "\n");
						$text = preg_replace("/\n\n+/", "\n\n", str_replace(array("\r\n", "\r"), "\n", $text));
						$texts = preg_split('/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
						$text = '';
						foreach ($texts as $txt) {
							$text .= '<p>' . nl2br(trim($txt, "\n")) . "</p>\n";
						}
						$text = preg_replace('|<p>\s*</p>|', '', $text);
					}
					return $text;
				}
			);
		}
		
		$this->loader=new Mustache\Loader();
		
		$this->engine=new \Mustache_Engine(array_merge(array(
			'loader'=>$this->loader,
			'partials_loader'=>$this->loader,
			'template_class_prefix'=>'RLDL_',
			'escape'=>function($value) {
				return @htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
			},
			'charset'=>'UTF-8'
		), (is_array($sets) ? $sets : array())));
	}
	
	public function setView($view=array()) {
		if (!is_array($view)) {
			throw new \InvalidArgumentException(
				'Wrong view data.'
			);
		}
		$this->view=array_merge($this->view, $view);
	}
	
	public function render($template_name) {
		return $this->engine->render($template_name, array_merge(self::$global_helpers, $this->view, self::$global_view));
	}
	
	public function setTemplateFile($name,$url=null) {
		if ($url==null) {
			$url=str_replace('_', '/', $name);
		}
		return $this->loader->setFile($name,self::$dir.'/'.$url.'.mustache');
	}
	
	public function setTemplateString($name,$string) {
		return $this->loader->set($name,$string);
	}
}
?>
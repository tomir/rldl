<?php
namespace RLDL\Mail;

class System extends \RLDL\Mail {
	private static $sender=null;
	
	public static function getItem($id) {
		return new self($id);
	}
	
	public function __construct($id) {
		parent::__construct($id);
		
		if (!$this->auth->isSystem()) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		if (!array_key_exists($this->id, parent::$cache)) {
			if (($data=self::$sql->SelectSingleRowArray(
				'[Mail]Mails',
				array('mail_id'=>\MySQL::SQLValue($this->id))
			))!==false) {
				$data['mail_info']=json_decode($data['mail_info'], true);
				$data['mail_data']=json_decode($data['mail_data'], true);
				
				parent::$cache[$this->id]=$data;
			}
			else {
				throw new \InvalidArgumentException(
					'Mail not exists.',
					404
				);
			}
		}
	}
	
	public function sendMail($mark=true) {
		$mail=&self::$sender;
		
		if ($mail==null) {
			$mail=new \PHPMailer;
			
			$mail->IsSendmail();
			$mail->CharSet='UTF-8';
			
			$mail->DKIM_domain=$this->config->get('dkim_domain');
			$mail->DKIM_private=$this->config->get('dkim_private_key');
			$mail->DKIM_selector=$this->config->get('dkim_selector');
		}
		
		$d=$this->get()['mail_data'];
		
		$mail->From=$d['from'][0]['address'];
		$mail->FromName=$d['from'][0]['name'];
		
		foreach ($d['to'] as $u) {
			$mail->AddAddress($u['address'], $u['name']);
		}
		
		if (array_key_exists('Reply-To', $d)) {
			foreach ($d['Reply-To'] as $u) {
				$mail->AddReplyTo($u['address'], $u['name']);
			}
		}
		
		if (array_key_exists('cc', $d)) {
			foreach ($d['cc'] as $u) {
				$mail->AddCC($u['address'], $u['name']);
			}
		}
		
		if (array_key_exists('bcc', $d)) {
			foreach ($d['bcc'] as $u) {
				$mail->AddBCC($u['address'], $u['name']);
			}
		}
		
		if (array_key_exists('html', $d)) {
			$mail->MsgHTML($d['html']);
			
			if (array_key_exists('txt', $d)) {
				$mail->AltBody=$d['txt'];
			}
		}
		else if (array_key_exists('txt', $d)) {
			$mail->Body=$d['txt'];
		}
		
		if (array_key_exists('title', $d)) {
			$mail->Subject=$d['title'];
		}
		
		if ($mail->Send() && $mark) {
			$this->markAsSent();
		}
		else {
			$mail->ErrorInfo;
		}
		
		$mail->clearAllRecipients();
		$mail->IsHTML(false);
		$mail->Body='';
		$mail->AltBody='';
		$mail->From='';
		$mail->FromName='';
		$mail->Subject='';	
	}
	
	public static function sendMailList($start=null, $limit=1000, $filters=array()){
		if (!\RLDL\Auth::getInstance()->isSystem()) {
			throw new \Exception(
				'Not authorized for this operation.',
				403
			);
		}
		
		$mails=parent::itemsList($start, $limit, $filters, true);
		
		if (array_key_exists('next', $mails)) {
			unset($mails['next']);
		}
		
		$mails_ids=array();
		
		foreach ($mails as &$mail) {
			self::getItem($mail['mail_id'])->sendMail(false);
			$mails_ids[]=$mail['mail_id'];
		}
		
		self::markItemsAsSent($mails_ids);
		
		return true;
	}
	
	public function markAsSent() {
		return self::markItemsAsSent($this->id);
	}
	
	public static function markItemsAsSent($items) {
		$auth=\RLDL\Auth::getInstance();
		
		if (!is_array($items)) {
			throw new \InvalidArgumentException(
				'Wrong parametrs.'
			);
		}
		
		if (!$auth->isSystem()) {
			throw new \Exception(
				'Unautorized.',
				401
			);
		}
		
		foreach ($items as &$item) {
			
			if (!is_int($item)) {
				unset($item);
			}
			else {
				$item=\MySQL::SQLValue($item,'int');
			}
		}
		
		$sql=\MySQL::getInstance();
		
		if ($sql->UpdateRow('[Mail]Mails', array('mail_sent_time'=>'CURRENT_TIMESTAMP'), array('mail_id IN ('.implode(', ', $items).')'))) {
			return true;
		}
		
		return false;
	}
}
?>
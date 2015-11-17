<?php
/*
	coded by: Andrzej Marek (andrzej.marek@gmail.com)
	inspired by: 
		1) javascript Albumcolors (http://albumcolors.chengyinliu.com)
		2) csscolor.php (copyright Patrick Fitzgerald 2004, http://www.barelyfitz.com/projects/csscolor/)
*/
class SNX_IMG_colors {
	private $defaults = array(
		'colors' => array('ffffff','000000','7f7f7f'), // hex or array(r,g,b)
		'sample' => 4, // 1=20px, 10=200px
		'bucketPrecision' => 0.3, // 0 - 1
		'minDistance' => 0.3, // 0 - 1, 0 = off
		'minBrightnessDifference' => 0.4, // 0 - 1, 0 = off, works only when minDistance != 0
		'mainColor' => 10
	);
	 
	private $IMG = null;
	private $colors = array();
	private $bucket = array();
	private $bucketCount = array();
	private $result = false;
	
	public function __construct($url, $defColors=false) {
		if ($defColors) $this->defaults['colors'] = $defColors;
		$this->defaults['colors']=$this->hex2rgb($this->defaults['colors']);
		
		$this->defaults['sampleSize']=$this->defaults['sample']*20;
		
		$ext = strtolower(pathinfo($url,PATHINFO_EXTENSION));
		$ext = explode('?', $ext);
		$ext = $ext[0];
	
		switch ($ext) {
			case 'gif' :
				$im = imagecreatefromgif($url);
				break;
			case 'png' :
				$im = imagecreatefrompng($url);
				break;
			case 'jpg' :
			case 'jpeg' :
				$im = imagecreatefromjpeg($url);
				break;
			default:
				$this->result=$this->defaults['colors'];
				return false; // nie obrazek 
		}
	
		if (false !== $im) {
			list($width, $height) = getimagesize($url);
			
			
			$this->IMG = imagecreatetruecolor($this->defaults['sampleSize'], $this->defaults['sampleSize']);
			imagealphablending($this->IMG, true);
			imagefilledrectangle($this->IMG, 0 , 0, $this->defaults['sampleSize'], $this->defaults['sampleSize'], imagecolorallocate($this->IMG, $this->defaults['colors'][0][0], $this->defaults['colors'][0][1], $this->defaults['colors'][0][2]));
			
			if (imagecopyresampled($this->IMG, $im, 0, 0, 0, 0, $this->defaults['sampleSize'], $this->defaults['sampleSize'], $width, $height)) return true;
			else {
				$this->result=$this->defaults['colors'];
				return false;
			}
		}
		else {
			$this->result=$this->defaults['colors'];
			return false;
		}
	}
	
	public function colors() {
		if ($this->result) return $this->result;
		
		for ($i = 0; $i < $this->defaults['sampleSize']; $i++) {
			for ($j = 0; $j < $this->defaults['sampleSize']; $j++) {
				$rgb=imagecolorat($this->IMG, $i, $j);
				$this->colors[] = array(
					($rgb >> 16) & 0xFF,
					($rgb >> 8) & 0xFF,
					$rgb & 0xFF
				);
			}
		}
		
		if ($this->colorsBucket()) {
			$c=$this->mainColors($this->defaults['mainColor']);
			switch (count($c)) {
				case 1 :
					array_push($c, $c[0]);
				case 2 :
					array_push($c, $c[1]);
				default:
					$this->result=$this->chooseColors($c); 
					return $this->result;
			}
			
		}
		return $this->defaults['colors'];
	}
	
	private function chooseColors($c) {
		$cc = array($c[0]);
		$d = $this->getColorDistances($c);
		$ci = array(0,0);
		
		for ($i = 0; $i < count($c); $i++) {
			if ($d[0][$i] > $d[0][$ci[0]]) {
				$ci[0]=$i;
			}
		}
		$cc[1] = $cc[2] = $c[$ci[0]];
		
		for ($j = 0; $j < count($c); $j++) {
			if ($d[0][$j] > $d[0][$ci[1]] && $j!=$ci[0]) {
				$ci[1]=$j;
			}
		}
		
		$cc[2] = $c[$ci[1]];
		
		ksort($cc);
		
		if ($this->defaults['minDistance']>0) {
			$di=$this->defaults['minDistance']*2550;
			$db=$this->defaults['minBrightnessDifference']*255;
			
			$cL=false;
			
			for ($k = 1; $k <= 2; $k++) {
				
				$cd=$d[0][$k];
				foreach (array(1, 0.7, 0.5, 0.3, 0.1, 0) as $percent) {
			
					$darker = $this->darken($cc[$k], $percent);
					$lighter = $this->lighten($cc[$k], $percent);
					
					$darkerCd = $this->colorDistance($cc[0], $darker);
					$lighterCd = $this->colorDistance($cc[0], $lighter);
					
					if ($darkerCd > $lighterCd) {
						$nC = $darker;
						$nCd = $darkerCd;
					}
					else {
						$nC=$lighter;
						$nCd = $lighterCd;
					}
					
					if ($nCd>=$di && $this->getBrightnessDifference($cc[0], $nC)>$db) {
						$cc[$k]=$nC;
						$cL=true;
						break;
					}
				}
			}
			if (!$cL) $cc=$this->defaults['colors'];
		}
		
		return $cc;
	}
	
	private function brightness($c) {
		return ((($c[0] * 299) + ($c[1] * 587) + ($c[2] * 114)) / 1000);
	}
	
	function getBrightnessDifference($c1, $c2) {
		$b1 = $this->brightness($c1);
		$b2 = $this->brightness($c2);
		return abs($b1 - $b2);
	}
	
	private function colorDistance($c1, $c2) {
		// max 2550
		return round(sqrt(21 * pow($c1[0] - $c2[0], 2) + 72 * pow($c1[1] - $c2[1], 2) + 7 * pow($c1[2] - $c2[2], 2)));
	}
	
	private function getColorDistances($c) {
		$d = array();
		
		for ($i = 0; $i < count($c); $i++) {
			$d[$i] = array();
			for ($j = 0; $j < count($c); $j++) {
				$d[$i][$j]=$this->colorDistance($c[$i], $c[$j]);
			}
		}
		
		return $d;
	}
	
	private function lighten($c, $percent){
		return $this->mix($c, $percent, 255);
	}
	
	private function darken($c, $percent) {
		return $this->mix($c, $percent, 0);
	}
	
	private function mix($c, $percent, $mask) {
		if (!is_numeric($percent) || $percent < 0 || $percent > 1) {
			return false;
		}
		
		if (!is_int($mask) || $mask < 0 || $mask > 255) {
			return false;
		}
		
		if (!is_array($c)) {
			return false;
		}
		
		for ($i=0; $i<3; $i++) {
			$c[$i] = round($c[$i] * $percent) + round($mask * (1-$percent));
			if ($c[$i] > 255) $c[$i] = 255;
		}
		return $c;
	}
	
	private function mainColors($c) {
		$mc=array();
		foreach ($this->bucketCount as $k => $v) {
			$mc[]=$this->avargeColors($this->bucket[$k]);
		}
		return array_slice($mc, 0, $c);
	}
	
	private function avargeColors($c) {
		$r=$g=$b=0;
		$l=count($c);
		foreach ($c as $k => $v) {
			$r+=$v[0];
			$g+=$v[1];
			$b+=$v[2];
		}
		return array(round($r/$l), round($g/$l), round($b/$l));
	}
	
	private function colorsByBucket() {
		if (count($this->bucket)>0) return true;
		
		for ($i = 0; $i < count($this->colors); $i++) {
			$bucket=$this->getBucket($this->colors[$i]);
			$this->bucket[$this->bucketName($bucket)][]=$this->colors[$i];
		}
		return true;
	}
	
	private function colorsBucket() {
		if (count($this->bucketCount)==0) {
			if (count($this->bucket)==0) $this->colorsByBucket();
			
			foreach ($this->bucket as $k => $v) {
				$this->bucketCount[$k]=count($this->bucket[$k]);
			}
			
			arsort($this->bucketCount);
		}
		return true;
	}
	
	private function getBucket($c) {
		$bucket=array();
		$p=$this->defaults['bucketPrecision']*256;
		for ($i = 0; $i < count($c); $i++) {
			$bucket[]=round(($c[$i]+1)/$p)*$p-1;
		}
		return $bucket;
	}
	
	private function bucketName($color) {
		return implode(',', $color);
	}
	
	public function hex2rgb($hex) {
		if (is_string($hex)) $hex=array($hex);
		
		$d = '[a-fA-F0-9]';
		$r = array();
		
		foreach ($hex as $hexV) {
			if (preg_match("/^($d$d)($d$d)($d$d)\$/", $hexV, $rgb)) {
				$r[] = array (hexdec($rgb[1]), hexdec($rgb[2]), hexdec($rgb[3]));
			}
			else if (preg_match("/^($d)($d)($d)$/", $hexV, $rgb)) {
				$r[] = array(hexdec($rgb[1].$rgb[1]), hexdec($rgb[2].$rgb[2]), hexdec($rgb[3].$rgb[3]));
			}
			else if (is_array($hexV)) $r[] = $hexV;
			else return false;
		}
		
		if (count($r)==1) return $r[0];
		else return $r;
	}
};

?>
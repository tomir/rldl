<?php

namespace RLDL2\Client\Service;

use RLDL2\Client\Model\Settings;
/**
 * Description of SettingManagement
 *
 * @author tomi_weber
 */
class SettingManagement {

	protected function parseSetting($name, $value) {

		$settingsArray = array();
		if (preg_match('/\(([a-z]+)\)(.+)/i', $name, $m)) {
			$name = $m[2];
			if ($m[1] == 'json') {
				$value = json_decode($value, true);
			} else {
				settype($value, $m[1]);
			}
		}

		$key = null;
		if (preg_match('/(.+)\[(.+)\]/i', $name, $n)) {
			$name = $n[1];
			$key = $n[2];
		}

		if (is_null($key)) {
			$settingsArray[$name] = $value;
			return $settingsArray;
		} else {
			if (!isset($settingsArray[$name])) {
				$settingsArray[$name] = array();
			}

			if (is_array($settingsArray[$name])) {
				$settingsArray[$name][$key] = $value;
				return $settingsArray;
			}
		}

		throw new \InvalidArgumentException(
			'Wrong client settings.'
		);
	}

	public function getByName($name = null) {
		
		$objSetting = new Settings();
		$settingArray = $objSetting->getAll(array('client_id' => $this->id));
		foreach ($settingArray as $setting) {
			$settingArray = $this->parseSetting($setting['entity_name'], $setting['entity_value']);
		}
		
		if ($name == null) {
			return $settingArray;
		}

		if (is_string($name) && is_array($settingArray) && array_key_exists($name, $settingArray)) {
			return $settingArray[$name];
		} else if (is_array($name)) {
			$to_return = array();

			foreach ($name as $key) {
				if (is_string($key) && array_key_exists($key, $settingArray)) {
					$to_return[] = $settingArray[$name];
				}
			}

			return $to_return;
		}

		return null;
	}

}

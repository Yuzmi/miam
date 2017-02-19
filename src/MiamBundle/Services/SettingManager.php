<?php

namespace MiamBundle\Services;

use MiamBundle\Entity\User;

class SettingManager extends MainService {
	private function getDefaultSettings() {
		return array(
	        'DATE_FORMAT' => "dmy",
	        'FONT_FAMILY' => "open-sans",
	        'FONT_SIZE' => 10,
	        'HIDE_SIDEBAR' => false,
	        'SHOW_ITEM_DETAILS' => "onclick",
	        'SHOW_ITEM_PICTURES' => "always",
	        'THEME' => "basic",
	        'TIMEZONE' => date_default_timezone_get()
	    );
	}

	public function setDefaultUserSettings(User &$user) {
		$user->setSettings($this->getDefaultSettings());
	}

	public function setUserSetting(User &$user, $key, $value) {
		if($key == 'DATE_FORMAT') {
            if(in_array($value, array('dmy', 'mdy', 'ymd'))) {
                $user->setSetting('DATE_FORMAT', $value);
            }
        } elseif($key == 'FONT_FAMILY') {
            $families = array(
                'arial', 'courier-new', 'fira-sans', 'georgia',
                'lato', 'open-sans', 'times-new-roman', 'ubuntu', 'verdana'
            );
            if(in_array($value, $families)) {
                $user->setSetting('FONT_FAMILY', $value);
            }
        } elseif($key == 'FONT_SIZE') {
            $font_size = intval($value);
            if($font_size >= 7 && $font_size <= 18) {
                $user->setSetting('FONT_SIZE', $value);
            }
        } elseif($key == 'HIDE_SIDEBAR') {
            $value = boolval($value);
            $user->setSetting('HIDE_SIDEBAR', $value);
        } elseif($key == 'SHOW_ITEM_DETAILS') {
            if(in_array($value, array('always', 'onclick'))) {
                $user->setSetting('SHOW_ITEM_DETAILS', $value);
            }
        } elseif($key == 'SHOW_ITEM_PICTURES') {
            if(in_array($value, array('always', 'onclick', 'never'))) {
                $user->setSetting('SHOW_ITEM_PICTURES', $value);
            }
        } elseif($key == 'THEME') {
            if(in_array($value, array('basic', 'dark'))) {
                $user->setSetting('THEME', $value);
            }
        } elseif($key == 'TIMEZONE') {
            $timezones = \DateTimeZone::listIdentifiers();
            if(in_array($value, $timezones)) {
                $user->setSetting('TIMEZONE', $value);
            }
        }
	}

	public function fixUserSettings(User &$user) {
		$settings = $user->getSettings();

		$settings = array_merge($this->getDefaultSettings(), array_intersect_key($settings, $this->getDefaultSettings()));

		$user->setSettings($settings);
	}
}
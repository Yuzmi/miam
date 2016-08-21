<?php

namespace MiamBundle\Twig;

class TextExtension extends \Twig_Extension
{
	public function getFilters() {
		return array(
			new \Twig_SimpleFilter('shorten', array($this, 'shorten')),
            new \Twig_SimpleFilter('removePictures', array($this, 'removePictures'), array('is_safe' => array('html'))),
            new \Twig_SimpleFilter('clickForPictures', array($this, 'clickForPictures'), array('is_safe' => array('html'))),
		);
	}

	public function getFunctions() {
		return array();
	}

	public function getName() {
		return 'text_extension';
	}

	public function shorten($string, $maxLength) {
        if(strlen($string) > $maxLength) {
        	$newMaxLength = $maxLength - 3; // '...'

        	$newString = '';

            $words = explode(' ', $string);
            //$words = preg_split('/\s+/', $string);
            if(count($words) > 1) {
                $length = 0;

                foreach($words as $word) {
                    $length += strlen($word) + 1;
                    if($length <= $newMaxLength) {
                        $newString .= ' '.$word;
                    } else {
                        break;
                    }
                }
            }

            if(empty($newString)) {
            	$newString = substr($string, 0, $newMaxLength);
            }

            return $newString.'...';
        }

        return $string;
    }

    public function removePictures($html) {
        return preg_replace('#<img[^>]*>#isU', '', $html);
    }

    public function clickForPictures($html) {
        return preg_replace_callback('#<img([^>]*)>#isU', function($matches) {
            $img = $matches[0];

            $img = preg_replace('#class\s*=\s*".*"#isU', '', $img);
            $img = preg_replace('#data-[a-z]+\s*=\s*".*"#isU', '', $img);

            $img = preg_replace('#src\s*=#isU', 'class="clickToShow" data-src=', $img);
            $img = preg_replace('#srcset\s*=#isU', 'data-srcset=', $img);

            return $img;
        }, $html);
    }
}
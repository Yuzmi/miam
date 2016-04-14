<?php

namespace MiamBundle\Twig;

class TextExtension extends \Twig_Extension
{
	public function getFilters() {
		return array(
			new \Twig_SimpleFilter('shorten', array($this, 'shorten')),
            new \Twig_SimpleFilter('safeHtml', array($this, 'safeHtml'), array('is_safe' => array('html'))),
            new \Twig_SimpleFilter('removePictures', array($this, 'removePictures'), array('is_safe' => array('html'))),
            new \Twig_SimpleFilter('clickForPictures', array($this, 'clickForPictures'), array('is_safe' => array('html'))),
		);
	}

	public function getFunctions() {
		return array(
			new \Twig_SimpleFunction('lipsum', array($this, 'lipsum'), array('is_safe' => array('html'))),
		);
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

    public function lipsum($length = 0) {
        $string = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse molestie bibendum nulla, at dignissim mauris suscipit ut. 
        	Pellentesque convallis purus in arcu rutrum feugiat. Fusce viverra efficitur enim eget gravida. 
        	Etiam sed nisi id tellus egestas fringilla in in erat. Cras quis erat eget augue blandit sagittis quis varius risus. 
        	Maecenas faucibus ullamcorper risus. Quisque eu convallis purus. 
        	Suspendisse nec tincidunt tortor. Duis auctor consequat sapien vitae ultricies. 
        	Integer sit amet interdum magna, id porta justo. Suspendisse potenti. Proin feugiat massa eget faucibus varius. 
        	Pellentesque ullamcorper leo id ex facilisis faucibus. Proin finibus scelerisque libero, vel accumsan libero. 
        	Mauris sed placerat lacus. Integer vel massa vestibulum, vulputate purus et, faucibus dolor. 
        	Aliquam diam urna, commodo sit amet lectus et, faucibus consequat diam. Integer vel nulla hendrerit, ornare velit vel, dignissim felis. 
        	Mauris tempus viverra lectus in dictum. Sed vel cursus purus. Fusce est nisl, efficitur sit amet risus id, placerat pharetra nibh. 
        	Curabitur dapibus justo accumsan volutpat volutpat. Etiam eu accumsan nisl. Nulla ultricies turpis a blandit porttitor. 
        	Proin ac tempor massa. Cras consectetur, nisi eget malesuada bibendum, lorem dolor aliquam diam, eget semper felis nibh in nulla. 
        	Etiam ullamcorper pulvinar mi, vitae varius neque sodales non.
        ";

        if($length > 0) {
        	$nb = ceil($length / strlen($string));

        	$str = '';
        	for($i=0;$i<$nb;$i++) {
        		$str .= $string;
        	}
        	$string = $str;
        }

        return substr($string, 0, $length);
    }

    public function safeHtml($html) {
        $html = tidy_repair_string($html, array(
            "output-html" => true
        ), "utf8");
        
        // Seriously, Tidy, why do you add html and body tags...
        if(preg_match('#<body>(.*)</body>#is', $html, $matches)) {
            $html = $matches[1];
        } else {
            $html = "";
        }

        return $html;
    }

    public function removePictures($html) {
        return preg_replace('#<img[^>]*>#isU', '', $html);
    }

    public function clickForPictures($html) {
        return preg_replace_callback('#<img([^>]*)>#isU', function($matches) {
            $img = $matches[0];

            $img = preg_replace('#class=[\s]*".*"#isU', '', $img);
            $img = preg_replace('#src=#isU', 'class="clickToShow"  data-src=', $img);
            $img = preg_replace('#srcset=#isU', 'data-srcset=', $img);

            return $img;
        }, $html);
    }
}
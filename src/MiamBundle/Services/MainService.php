<?php

namespace MiamBundle\Services;

class MainService {
	protected function getRepo($entity) {
		return isset($this->em) ? $this->em->getRepository('MiamBundle:'.$entity) : null;
	}
}

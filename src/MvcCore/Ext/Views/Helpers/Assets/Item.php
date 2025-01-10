<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md
 */

namespace MvcCore\Ext\Views\Helpers\Assets;

/**
 * Asset item, extended from `\stdClass` to be able to add any additional property.
 */
class Item extends \stdClass {

	/**
	 * Asset file full path on hard drive in directory vsible for clients.
	 * @var string
	 */
	public $fullPath;

	/**
	 * Relative application file path inside main app or inside composer package.
	 * @var string
	 */
	public $path;

	/**
	 * If `TRUE`, file will never be minimalized.
	 * @var bool
	 */
	public $notMin;

	/**
	 * If `TRUE`, file exists inside composer package.
	 * @var bool
	 */
	public $vendor;

	/**
	 * Asset type, it could be `js` or `css`.
	 * @var string
	 */
	public $type;

	/**
	 * @param string $fullPath 
	 * @param string $path 
	 * @param bool   $notMin 
	 * @param bool   $vendor 
	 */
	public function __construct ($fullPath, $path, $notMin, $vendor) {
		$this->fullPath = $fullPath;
		$this->path = $path;
		$this->notMin = $notMin;
		$this->vendor = $vendor;
		$this->type = 'asset';
	}

}
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

namespace MvcCore\Ext\Views\Helpers\JsHelpers;

class JsItem extends \MvcCore\Ext\Views\Helpers\Assets\Item {
	
	/**
	 * If `TRUE`, file is defined as external URL to be downloaded into public temporary directory.
	 * @var bool
	 */
	public bool $external;

	/**
	 * If `TRUE`, script will be added into output with `async` HTML attribute.
	 * @var bool
	 */
	public bool $async;
	
	/**
	 * If `TRUE`, script will be added into output with `defer` HTML attribute.
	 * @var bool
	 */
	public bool $defer;

	/**
	 * Script `src` attribute, completed in rendering process in JS view helper by configured conditions.
	 * @var ?string
	 */
	public ?string $src;

	public function __construct (string $fullPath, string $path, bool $notMin, bool $vendor, bool $external, bool $async, bool $defer) {
		parent::__construct($fullPath, $path, $notMin, $vendor);
		$this->type = 'js';
		$this->external = $external;
		$this->async = $async;
		$this->defer = $defer;
	}

}
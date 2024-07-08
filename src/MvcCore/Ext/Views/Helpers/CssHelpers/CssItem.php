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

namespace MvcCore\Ext\Views\Helpers\CssHelpers;

class CssItem extends \MvcCore\Ext\Views\Helpers\Assets\Item {
	
	/**
	 * Media integer flag, it could contains multiple values together:
	 * - `0` - CssHelper::MEDIA_ALL     (`media="all"`),
	 * - `1` - CssHelper::MEDIA_SCREEN  (`media="screen"`),
	 * - `2` - CssHelper::MEDIA_PRINT   (`media="print"`),
	 * - `4` - MyCssHelper::ANY_HOVER   (`media="any-hover"`),
	 * - `8` - MyCssHelper::ANY_POINTER (`media="any-pointer"`),
	 * - ...
	 * So it's possible to define media like this:
	 * `$this->Css($packageName)->Append($path, media: CssHelper::MEDIA_SCREEN | MyCssHelper::ANY_HOVER);`
	 * @var int
	 */
	public int $media;

	/**
	 * If `TRUE`, css file will be rendered with PHP engine.
	 * @var bool
	 */
	public bool $render;

	/**
	 * Link `href` attribute, completed in rendering process in CSS view helper by configured conditions.
	 * @var ?string
	 */
	public ?string $href = NULL;

	public function __construct (string $fullPath, string $path, bool $notMin, bool $vendor, bool $render, int $media) {
		parent::__construct($fullPath, $path, $notMin, $vendor);
		$this->type = 'css';
		$this->render = $render;
		$this->media = $media;
	}

}
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

namespace MvcCore\Ext\Views\Helpers;

/**
 * @method \MvcCore\Ext\Views\Helpers\CssHelper GetInstance()
 */
class CssHelper extends Assets {
	
	/**
	 * Matches all devices.
	 * @var int
	 */
	const MEDIA_ALL		= 0;
	
	/**
	 * Matches all devices that aren't matched by print.
	 * @var int
	 */
	const MEDIA_SCREEN	= 1;
	
	/**
	 * Matches printers, and devices intended to reproduce 
	 * a printed display, such as a web browser showing 
	 * a document in "Print Preview".
	 * @var int
	 */
	const MEDIA_PRINT	= 2;

	/**
	 * @inheritDocs
	 * @var \MvcCore\Ext\Views\Helpers\CssHelper|NULL
	 */
	protected static $instance = NULL;
	
	/**
	 * Allowed media types for <link> tag.
	 * @var \string[]
	 */
	protected static $mediaTypes = [
		self::MEDIA_ALL		=> 'all',
		self::MEDIA_SCREEN	=> 'screen',
		self::MEDIA_PRINT	=> 'print',
	];


	/**
	 * View Helper Method, returns current object instance.
	 * @param  string $groupName
	 * @return \MvcCore\Ext\Views\Helpers\CssHelper
	 */
	public function Css ($groupName = self::GROUP_NAME_DEFAULT) {
		$this->currentGroupName = $groupName;
		$this->getGroupStore(); // prepare structure
		return $this;
	}
	
	/**
	 * Render link elements as html code with links 
	 * to original files or temporary rendered files.
	 * @param  int    $indent
	 * @return string
	 */
	public function Render ($indent = 0) {
		$currentGroupRecords = & $this->getGroupStore();
		if (count($currentGroupRecords) === 0) return '';
		$minify = (bool) self::$globalOptions['cssMinify'];
		$joinTogether = (bool) self::$globalOptions['cssJoin'];
		if ($joinTogether) {
			$result = $this->renderItemsTogether(
				$currentGroupRecords,
				$indent, $minify
			);
		} else {
			$result = $this->renderItemsSeparated(
				$currentGroupRecords,
				$indent, $minify
			);
		}
		$this->setGroupStore([]);
		return $result;
	}


	/**
	 * Check if style sheet is already 
	 * presented in stylesheets group.
	 * @throws \Exception
	 * @param  string     $path
	 * @param  int|string $media
	 * @param  bool       $notMin
	 * @return bool
	 */
	public function Contains ($path, $media = self::MEDIA_ALL, $notMin = FALSE) {
		return $this->execContains($path, $media, $notMin, FALSE);
	}

	/**
	 * Remove style sheet if it is already 
	 * presented in stylesheets group.
	 * @throws \Exception
	 * @param  string     $path
	 * @param  int|string $media
	 * @param  bool       $notMin
	 * @return bool
	 */
	public function Remove ($path, $media = self::MEDIA_ALL, $notMin = FALSE) {
		return $this->execRemove($path, $media, $notMin, FALSE);
	}
	
	/**
	 * Add style sheet into given index of group 
	 * stylesheets array for later render process.
	 * @throws \Exception
	 * @param  int	      $index
	 * @param  string     $path
	 * @param  int|string $media
	 * @param  bool       $notMin
	 * @return \MvcCore\Ext\Views\Helpers\CssHelper
	 */
	public function Offset ($index, $path, $media = self::MEDIA_ALL, $notMin = FALSE) {
		return $this->execOffset($index, $path, $media, $notMin, FALSE, FALSE);
	}
	
	/**
	 * Append style sheet after all group 
	 * stylesheets for later render process.
	 * @throws \Exception
	 * @param  string     $path
	 * @param  int|string $media
	 * @param  bool       $notMin
	 * @return \MvcCore\Ext\Views\Helpers\CssHelper
	 */
	public function Append ($path, $media = self::MEDIA_ALL, $notMin = FALSE) {
		return $this->execAppend($path, $media, $notMin, FALSE, FALSE);
	}

	/**
	 * Prepend style sheet before all group 
	 * stylesheets for later render process.
	 * @throws \Exception
	 * @param  string     $path
	 * @param  int|string $media
	 * @param  bool       $notMin
	 * @return \MvcCore\Ext\Views\Helpers\CssHelper
	 */
	public function Prepend ($path, $media = self::MEDIA_ALL, $notMin = FALSE) {
		return $this->execPrepend($path, $media, $notMin, FALSE, FALSE);
	}
	
	/**
	 * Add style sheet into given index of stylesheets 
	 * group array for later render process with php 
	 * tags executing in given file.
	 * @throws \Exception
	 * @param  int	      $index
	 * @param  string     $path
	 * @param  int|string $media
	 * @param  bool       $notMin
	 * @return \MvcCore\Ext\Views\Helpers\CssHelper
	 */
	public function OffsetRendered ($index, $path, $media = self::MEDIA_ALL, $notMin = FALSE) {
		return $this->execOffset($index, $path, $media, $notMin, FALSE, TRUE);
	}

	/**
	 * Append style sheet after all group stylesheets 
	 * for later render process with php tags 
	 * executing in given file.
	 * @throws \Exception
	 * @param  string     $path
	 * @param  int|string $media
	 * @param  bool       $notMin
	 * @return \MvcCore\Ext\Views\Helpers\CssHelper
	 */
	public function AppendRendered ($path, $media = self::MEDIA_ALL, $notMin = FALSE) {
		return $this->execAppend($path, $media, $notMin, FALSE, TRUE);
	}

	/**
	 * Prepend style sheet before all group stylesheets 
	 * for later render process with php tags executing 
	 * in given file.
	 * @throws \Exception
	 * @param  string     $path
	 * @param  int|string $media
	 * @param  bool       $notMin
	 * @return \MvcCore\Ext\Views\Helpers\CssHelper
	 */
	public function PrependRendered ($path, $media = self::MEDIA_ALL, $notMin = FALSE) {
		return $this->execPrepend($path, $media, $notMin, FALSE, TRUE);
	}

	
	/**
	 * Check if style sheet is already 
	 * presented in stylesheets group.
	 * This method is necessary to use 
	 * in vendor application packages.
	 * @throws \Exception
	 * @param  string     $path
	 * @param  int|string $media
	 * @param  bool       $notMin
	 * @return bool
	 */
	public function VendorContains ($path, $media = self::MEDIA_ALL, $notMin = FALSE) {
		return $this->execContains($path, $media, $notMin, TRUE);
	}

	/**
	 * Remove style sheet if it is already 
	 * presented in stylesheets group.
	 * This method is necessary to use 
	 * in vendor application packages.
	 * @throws \Exception
	 * @param  string     $path
	 * @param  int|string $media
	 * @param  bool       $notMin
	 * @return bool
	 */
	public function VendorRemove ($path, $media = self::MEDIA_ALL, $notMin = FALSE) {
		return $this->execRemove($path, $media, $notMin, TRUE);
	}
	
	/**
	 * Add style sheet into given index of group 
	 * stylesheets array for later render process.
	 * This method is necessary to use 
	 * in vendor application packages.
	 * @throws \Exception
	 * @param  int	      $index
	 * @param  string     $path
	 * @param  int|string $media
	 * @param  bool       $notMin
	 * @return \MvcCore\Ext\Views\Helpers\CssHelper
	 */
	public function VendorOffset ($index, $path, $media = self::MEDIA_ALL, $notMin = FALSE) {
		return $this->execOffset($index, $path, $media, $notMin, TRUE, FALSE);
	}
	
	/**
	 * Append style sheet after all group 
	 * stylesheets for later render process.
	 * This method is necessary to use 
	 * in vendor application packages.
	 * @throws \Exception
	 * @param  string     $path
	 * @param  int|string $media
	 * @param  bool       $notMin
	 * @return \MvcCore\Ext\Views\Helpers\CssHelper
	 */
	public function VendorAppend ($path, $media = self::MEDIA_ALL, $notMin = FALSE) {
		return $this->execAppend($path, $media, $notMin, TRUE, FALSE);
	}

	/**
	 * Prepend style sheet before all group 
	 * stylesheets for later render process.
	 * This method is necessary to use 
	 * in vendor application packages.
	 * @throws \Exception
	 * @param  string     $path
	 * @param  int|string $media
	 * @param  bool       $notMin
	 * @return \MvcCore\Ext\Views\Helpers\CssHelper
	 */
	public function VendorPrepend ($path, $media = self::MEDIA_ALL, $notMin = FALSE) {
		return $this->execPrepend($path, $media, $notMin, TRUE, FALSE);
	}
	
	/**
	 * Add style sheet into given index of stylesheets 
	 * group array for later render process with php 
	 * tags executing in given file.
	 * This method is necessary to use 
	 * in vendor application packages.
	 * @throws \Exception
	 * @param  int	      $index
	 * @param  string     $path
	 * @param  int|string $media
	 * @param  bool       $notMin
	 * @return \MvcCore\Ext\Views\Helpers\CssHelper
	 */
	public function VendorOffsetRendered ($index, $path, $media = self::MEDIA_ALL, $notMin = FALSE) {
		return $this->execOffset($index, $path, $media, $notMin, TRUE, TRUE);
	}

	/**
	 * Append style sheet after all group stylesheets 
	 * for later render process with php tags 
	 * executing in given file.
	 * This method is necessary to use 
	 * in vendor application packages.
	 * @throws \Exception
	 * @param  string     $path
	 * @param  int|string $media
	 * @param  bool       $notMin
	 * @return \MvcCore\Ext\Views\Helpers\CssHelper
	 */
	public function VendorAppendRendered ($path, $media = self::MEDIA_ALL, $notMin = FALSE) {
		return $this->execAppend($path, $media, $notMin, TRUE, TRUE);
	}

	/**
	 * Prepend style sheet before all group stylesheets 
	 * for later render process with php tags executing 
	 * in given file.
	 * This method is necessary to use 
	 * in vendor application packages.
	 * @throws \Exception
	 * @param  string     $path
	 * @param  int|string $media
	 * @param  bool       $notMin
	 * @return \MvcCore\Ext\Views\Helpers\CssHelper
	 */
	public function VendorPrependRendered ($path, $media = self::MEDIA_ALL, $notMin = FALSE) {
		return $this->execPrepend($path, $media, $notMin, TRUE, TRUE);
	}


	/**
	 * Check if style sheet is already 
	 * presented in stylesheets group.
	 * @throws \Exception
	 * @param  string     $path
	 * @param  int|string $media
	 * @param  bool       $notMin
	 * @param  bool       $vendor
	 * @return bool
	 */
	protected function execContains ($path, $media, $notMin, $vendor) {
		$reverseKey = $this->getGroupStoreReverseKey(func_get_args());
		return isset($this->groupStoreReverseKeys[$reverseKey]);
	}

	/**
	 * Remove style sheet if it is already 
	 * presented in stylesheets group.
	 * @throws \Exception
	 * @param  string     $path
	 * @param  int|string $media
	 * @param  bool       $notMin
	 * @param  bool       $vendor
	 * @return bool
	 */
	protected function execRemove ($path, $media, $notMin, $vendor) {
		$result = FALSE;
		$linksGroup = & $this->getGroupStore();
		$mediaInt = $this->getMediaType($media);
		foreach ($linksGroup as $index => $item) {
			if (
				$item->path === $path &&
				$item->media === $mediaInt && 
				$item->notMin === $notMin && 
				$item->vendor === $vendor
			) {
				$result = $this->unsetGroupStore($index);
				$reverseKey = $this->getGroupStoreReverseKey(func_get_args());
				unset($this->groupStoreReverseKeys[$reverseKey]);
				break;
			}
		}
		return $result;
	}

	/**
	 * Add style sheet into given index of group 
	 * stylesheets array for later render process.
	 * @throws \Exception
	 * @param  int	      $index
	 * @param  string     $path
	 * @param  int|string $media
	 * @param  bool       $notMin
	 * @param  bool       $vendor
	 * @param  bool       $render
	 * @return \MvcCore\Ext\Views\Helpers\CssHelper
	 */
	protected function execOffset ($index, $path, $media, $notMin, $vendor, $render) {
		$item = $this->completeItem($path, $media, $notMin, $vendor, $render);
		$currentItems = & $this->getGroupStore();
		$newItems = $index > 0
			? array_slice($currentItems, 0, $index, FALSE)
			: [];
		$newItems[] = $item;
		$this->setUpGroupStoreReverseKey([$path, $media, $notMin, $vendor]);
		$currentItemsCount = count($currentItems);
		if ($index < count($currentItems)) 
			$newItems = array_merge($newItems, array_slice(
				$currentItems, $index, 
				$currentItemsCount - $index, FALSE
			));
		return $this->setGroupStore($newItems);
	}

	/**
	 * Append style sheet after all group 
	 * stylesheets for later render process.
	 * @throws \Exception
	 * @param  string     $path
	 * @param  int|string $media
	 * @param  bool       $notMin
	 * @param  bool       $vendor
	 * @param  bool       $render
	 * @return \MvcCore\Ext\Views\Helpers\CssHelper
	 */
	protected function execAppend ($path, $media, $notMin, $vendor, $render) {
		$item = $this->completeItem($path, $media, $notMin, $vendor, $render);
		$currentGroupRecords = & $this->getGroupStore();
		array_push($currentGroupRecords, $item);
		$this->setUpGroupStoreReverseKey([$path, $media, $notMin, $vendor]);
		return $this;
	}

	/**
	 * Prepend style sheet before all group 
	 * stylesheets for later render process.
	 * @throws \Exception
	 * @param  string     $path
	 * @param  int|string $media
	 * @param  bool       $notMin
	 * @param  bool       $vendor
	 * @param  bool       $render
	 * @return \MvcCore\Ext\Views\Helpers\CssHelper
	 */
	protected function execPrepend ($path, $media, $notMin, $vendor, $render) {
		$item = $this->completeItem($path, $media, $notMin, $vendor, $render);
		$currentGroupRecords = & $this->getGroupStore();
		array_unshift($currentGroupRecords, $item);
		$this->setUpGroupStoreReverseKey([$path, $media, $notMin, $vendor]);
		return $this;
	}

	
	/**
	 * Create data item to store for render process.
	 * @throws \Exception
	 * @param  string     $path
	 * @param  int|string $media
	 * @param  bool       $notMin
	 * @param  bool       $vendor
	 * @param  bool       $render
	 * @return \stdClass
	 */
	protected function completeItem ($path, $media, $notMin, $vendor, $render) {
		if (self::$fileChecking) {
			$duplication = $this->isDuplicateStyle($path, $vendor);
			if ($duplication !== NULL) 
				$this->warning("Style sheet `{$path}` is already added in css group: `{$duplication}`.");
		}
		if ($vendor)
			$path = $this->move2TmpGetPath(
				$path, self::$vendorDocRoot . $path, 'css'
			);
		return (object) [
			'fullPath'		=> static::$docRoot . $path,
			'path'			=> $path,
			'media'			=> $this->getMediaType($media),
			'notMin'		=> $notMin,
			'vendor'		=> $vendor,
			'render'		=> $render,
		];
	}

	/**
	 * Get media type integer by input string 
	 * (or validate and return int type if input is int).
	 * @throws \Exception
	 * @param  string|NULL $media 
	 * @return int
	 */
	protected function getMediaType ($media) {
		if (is_int($media)) {
			if (isset(static::$mediaTypes[$media]))
				return $media;
			$this->exception("Media int type `{$media}` not defined.");
		} else if (is_string($media)) {
			$mediaInt = array_search($media, static::$mediaTypes, TRUE);
			if (is_int($mediaInt))
				return $mediaInt;
			$this->exception("Media string type `{$media}` not defined.");			
		}
		return static::MEDIA_ALL;
	}

	/**
	 * Is the linked style duplicate?
	 * @param  string $path
	 * @param  bool   $vendor
	 * @return string|NULL
	 */
	protected function isDuplicateStyle ($path, $vendor) {
		$result = NULL;
		$currentRecords = $this->groupStore[$this->getCtrlActionKey()];
		foreach ($currentRecords as $groupName => $groupItems) {
			foreach ($groupItems as $item) {
				if ($item->path === $path && $item->vendor === $vendor) {
					$result = $groupName;
					break;
				}
			}
		}
		return $result;
	}


	/**
	 * Minify style sheet string and return minified result.
	 * @param  string $css
	 * @param  string $path
	 * @return string
	 */
	protected function minify (& $css, $path) {
		$result = '';
		$errorMsg = "Unable to minify css: `{$path}`.";
		try {
			$compressor = new \tubalmartin\CssMin\Minifier;
			$compressor->keepSourceMapComment(TRUE);
			$result = $compressor->run($css);
		} catch (\Throwable $e) {
			$this->exception($errorMsg);
		}
		return $result;
	}


	/**
	 * Render data items as one <link> html tag or all 
	 * another <link> html tags after with files which 
	 * is not possible to minify.
	 * @param  \stdClass[] $items
	 * @param  int	       $indent
	 * @param  bool        $minify
	 * @return string
	 */
	protected function renderItemsTogether (array & $items, $indent, $minify) {
		// some configurations is not possible to render together and minimized
		list(
			$itemsToRenderMinimized, 
			$itemsToRenderSeparately
		) = $this->separateItemsToMinifiedGroups($items);

		$indentStr = $this->getIndentString($indent);
		$resultItems = [];
		if (self::$fileRendering) 
			$resultItems[] = '<!-- css group begin: ' . $this->currentGroupName . ' -->';

		// process array with groups, which are not possible to minimize
		foreach ($itemsToRenderSeparately as $itemsToRender) {
			$resultItems[] = $this->renderItemsTogetherAsGroup($itemsToRender, $minify);
		}

		// process array with groups to minimize
		foreach ($itemsToRenderMinimized as $itemsToRender) {
			$resultItems[] = $this->renderItemsTogetherAsGroup($itemsToRender, $minify);
		}

		if (self::$fileRendering) 
			$resultItems[] = '<!-- css group end: ' . $this->currentGroupName . ' -->';

		return "\n" . $indentStr . implode("\n" . $indentStr, $resultItems);
	}

	/**
	 * Render all items in group together, when application 
	 * is compiled, do not check source files and changes.
	 * @param  \stdClass[] $itemsToRender
	 * @param  bool        $minify
	 * @return string
	 */
	protected function renderItemsTogetherAsGroup (array & $itemsToRender, $minify) {
		// complete tmp filename by source filenames and source files modification times
		$filesGroupInfo = [];
		foreach ($itemsToRender as $item) {
			if (self::$fileChecking) {
				if (!file_exists($item->fullPath)) {
					$this->exception("File not found in CSS view rendering process ('{$item->fullPath}').");
				}
				$filesGroupInfo[] = $item->path . '?_' . self::getFileImprint($item->fullPath);
			} else {
				$filesGroupInfo[] = $item->path;
			}
		}
		$tmpFileFullPath = $this->getTmpFileFullPathByPartFilesInfo(
			$filesGroupInfo, $minify, 'css'
		);

		// check, if the rendered, together completed 
		// and minimized file is in tmp cache already
		if (self::$fileRendering) {
			if (!file_exists($tmpFileFullPath)) {
				// load all items and join them together
				$resultContents = [];
				foreach ($itemsToRender as & $item) {
					if ($item->render) {
						$fileContent = $this->renderFile($item->fullPath);
					} else {
						$fileContent = file_get_contents($item->fullPath);
					}
					$fileContent = $this->convertCssPathsFromRel2TmpAbs(
						$fileContent, $item->path
					);
					if ($minify) 
						$fileContent = $this->minify($fileContent, $item->path);
					$resultContents[] = "/* " . $item->path . " */\n" . $fileContent;
				}
				// save completed tmp file
				$this->saveFileContent($tmpFileFullPath, implode("\n\n", $resultContents));
				$this->log("Css files group rendered: `{$tmpFileFullPath}`.", 'debug');
			}
		}

		// complete <link> tag with tmp file path in $tmpFileFullPath variable
		$firstItem = array_merge([], (array) $itemsToRender[0]);
		$pathToTmp = substr($tmpFileFullPath, strlen(static::$docRoot));
		$firstItem['href'] = $this->CssJsFileUrl($pathToTmp);
		return $this->renderItemSeparated((object) $firstItem);
	}

	/**
	 * Render data items as separated <link> html tags.
	 * @param  \stdClass[] $items
	 * @param  int	       $indent
	 * @param  bool        $minify
	 * @return string
	 */
	protected function renderItemsSeparated (array & $items, $indent, $minify) {
		$indentStr = $this->getIndentString($indent);
		$resultItems = [];
		if (self::$fileRendering) 
			$resultItems[] = '<!-- css group begin: ' . $this->currentGroupName . ' -->';
		foreach ($items as $item) {
			if ($item->render || ($minify && !$item->notMin)) {
				$item->href = $this->CssJsFileUrl($this->render2TmpGetPath($item, $minify, 'css'));
			} else {
				$item->href = $this->CssJsFileUrl($item->path);
			}
			if (self::$fileChecking)
				$item->href = $this->addFileModImprint2HrefUrl($item->href, $item->fullPath);
			$resultItems[] = $this->renderItemSeparated($item);
		}
		if (self::$fileRendering) 
			$resultItems[] = '<!-- css group end: ' . $this->currentGroupName . ' -->';
		return "\n" . $indentStr . implode("\n" . $indentStr, $resultItems);
	}
	
	/**
	 * Create HTML link element from data item
	 * @param  \stdClass $item
	 * @return string
	 */
	protected function renderItemSeparated (\stdClass $item) {
		$result = ['<link rel="stylesheet"'];
		if ($nonceAttr = static::getNonce(FALSE)) $result[] = $nonceAttr;
		if ($item->media !== static::MEDIA_ALL) 
			$result[] = ' media="' . static::$mediaTypes[$item->media] . '"';
		if (!$item->render && self::$fileChecking && !file_exists($item->fullPath)) {
			$this->log("File not found in CSS view rendering process: `{$item->fullPath}`.", 'error');
		}
		$result[] = ' href="' . $item->href . '" />';
		return implode('', $result);
	}


	/**
	 * @inheritDocs
	 * @throws \Exception
	 * @param  \stdClass  $item 
	 * @param  string     $srcFileFullPath 
	 * @param  string     $minify 
	 * @return string
	 */
	protected function render2TmpGetPathExec (\stdClass $item, $srcFileFullPath, $minify) {
		if ($item->render) {
			$fileContent = $this->renderFile($srcFileFullPath);
		} else if ($minify) {
			$fileContent = file_get_contents($srcFileFullPath);
		}
		$fileContent = $this->convertCssPathsFromRel2TmpAbs(
			$fileContent, $item->path
		);
		if ($minify) 
			$fileContent = $this->minify($fileContent, $item->path);
		return $fileContent;
	}

	/**
	 * Render css file by absolute path as php file 
	 * and return rendered result as string.
	 * @param  string $absolutePath
	 * @return string
	 */
	protected function renderFile ($absolutePath) {
		ob_start();
		try {
			include($absolutePath);
		} catch (\Throwable $e) {
			$this->exceptionHandler($e);
		}
		return ob_get_clean();
	}

	/**
	 * Converts all relative paths in all css 
	 * rules to absolute paths with `\MvcCore` Url structures.
	 * @param  mixed  $fullPathContent Css file full path.
	 * @param  mixed  $href            Css file href value.
	 * @return string
	 */
	protected function convertCssPathsFromRel2TmpAbs (& $fullPathContent, $href) {
		$lastHrefSlashPos = mb_strrpos($href, '/');
		if ($lastHrefSlashPos === FALSE) return $fullPathContent;
		$stylesheetDirectoryRelative = mb_substr($href, 0, $lastHrefSlashPos + 1);

		// process content for all double dots
		$position = 0;
		while ($position < mb_strlen($fullPathContent)) {
			$doubleDotsPos = mb_strpos($fullPathContent, '../', $position);
			if ($doubleDotsPos === FALSE) break;

			// make sure that double dot string is in `url('')` or `url("")` block

			// try to find first occurrence of `url("` backwards
			$lastUrlBeginStrPos = mb_strrpos(mb_substr($fullPathContent, 0, $doubleDotsPos), 'url(');
			if ($lastUrlBeginStrPos === FALSE) {
				$position = $doubleDotsPos + 3;
				continue;
			}

			// then check if between that are only [\./ ]
			$beginOfUrlBlockChars = mb_substr($fullPathContent, $lastUrlBeginStrPos + 4, $doubleDotsPos - ($lastUrlBeginStrPos + 4));
			$beginOfUrlBlockChars = preg_replace("#[\./ \"'_\-]#", "", $beginOfUrlBlockChars);
			if (mb_strlen($beginOfUrlBlockChars) > 0) {
				$position = $lastUrlBeginStrPos + 4;
				continue;
			}

			// try to find first occurrence of `")`
			$firstUrlEndStrPos = mb_strpos($fullPathContent, ')', $doubleDotsPos);
			if ($firstUrlEndStrPos === FALSE) {
				$position = $doubleDotsPos + 3;
				continue;
			}

			// then check of between that are only [a-zA-Z\./ ]
			$endOfUrlBlockChars = mb_substr($fullPathContent, $doubleDotsPos + 3, $firstUrlEndStrPos - ($doubleDotsPos + 3));
			$endOfUrlBlockChars = preg_replace("#[a-zA-Z\./ \"'_\-\?\&\#]#", "", $endOfUrlBlockChars);
			if (mb_strlen($endOfUrlBlockChars) > 0) {
				$position = $firstUrlEndStrPos + 1;
				continue;
			}

			// if it is not the Url block, shift the position and continue

			// replace relative path to absolute path
			$lastUrlBeginStrPos += 4;
			$urlSubStr = mb_substr($fullPathContent, $lastUrlBeginStrPos, $firstUrlEndStrPos - $lastUrlBeginStrPos);

			// get double or single quotes or no quotes
			$firstStr = mb_substr($urlSubStr, 0, 1);
			$lastStr = mb_substr($urlSubStr, mb_strlen($urlSubStr) - 1, 1);
			if ($firstStr === '"' && $lastStr === '"') {
				$urlSubStr = mb_substr($urlSubStr, 1, mb_strlen($urlSubStr) - 2);
				$quote = '"';
			} else if ($firstStr === "'" && $lastStr === "'") {
				$urlSubStr = mb_substr($urlSubStr, 1, mb_strlen($urlSubStr) - 2);
				$quote = "'";
			} else {
				$quote = '"';
			}

			// translate relative to web absolute path
			$trimmedUrlSubStr = ltrim($urlSubStr, './');
			$trimmedPartLength = mb_strlen($urlSubStr) - mb_strlen($trimmedUrlSubStr);
			$trimmedPart = trim(mb_substr($urlSubStr, 0, $trimmedPartLength), '/');
			$subjectRestPath = trim(mb_substr($urlSubStr, $trimmedPartLength), '/');

			$urlFullBasePath = str_replace('\\', '/', realpath(static::$docRoot . $stylesheetDirectoryRelative . $trimmedPart));
			$urlFullPath = $urlFullBasePath . '/' . $subjectRestPath;

			// complete style sheet new path
			$webPath = mb_substr($urlFullPath, mb_strlen(static::$docRoot));
			$webPath = $this->CssJsFileUrl($webPath);

			// replace the URL part
			$fullPathContent = mb_substr($fullPathContent, 0, $lastUrlBeginStrPos)
				. $quote . $webPath . $quote
				. mb_substr($fullPathContent, $firstUrlEndStrPos);

			// shift the position property
			$position = $lastUrlBeginStrPos + mb_strlen($webPath) + 3;
		}

		return str_replace(static::REL_BASE_PATH_PLACEMENT, '../..', $fullPathContent);
	}
}

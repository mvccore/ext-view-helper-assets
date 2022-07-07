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
 * @method \MvcCore\Ext\Views\Helpers\JsHelper GetInstance()
 */
class JsHelper extends Assets {

	protected static $instance = NULL;

	/**
	 * Whatever Expires header is send over http scheme,
	 * minimal cache time for external files will be one
	 * day from last download
	 * @const integer
	 */
	const EXTERNAL_MIN_CACHE_TIME = 86400;

	/**
	 * View Helper Method, returns current object instance.
	 * @param  string $groupName
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	public function Js ($groupName = self::GROUP_NAME_DEFAULT) {
		$this->currentGroupName = $groupName;
		$this->getGroupStore(); // prepare structure
		return $this;
	}
	
	/**
	 * Render script elements as html code with links
	 * to original files or temporary downloaded files.
	 * @param  int    $indent
	 * @return string
	 */
	public function Render ($indent = 0) {
		$currentGroupRecords = & $this->getGroupStore();
		if (count($currentGroupRecords) === 0) return '';
		$minify = (bool) self::$globalOptions['jsMinify'];
		$joinTogether = (bool) self::$globalOptions['jsJoin'];
		if ($joinTogether) {
			$result = $this->renderItemsTogether(
				$currentGroupRecords,
				$indent,
				$minify
			);
		} else {
			$result = $this->renderItemsSeparated(
				$currentGroupRecords,
				$indent,
				$minify
			);
		}
		$this->setGroupStore([]);
		return $result;
	}


	/**
	 * Check if script is already 
	 * presented in scripts group.
	 * @param  string $path
	 * @param  bool   $async
	 * @param  bool   $defer
	 * @param  bool   $notMin
	 * @return bool
	 */
	public function Contains ($path, $async = FALSE, $defer = FALSE, $notMin = FALSE) {
		return $this->execContains($path, $async, $defer, $notMin, FALSE);
	}

	/**
	 * Remove script if it is already 
	 * presented in scripts group.
	 * @param  string  $path
	 * @param  bool    $async
	 * @param  bool    $defer
	 * @param  bool    $notMin
	 * @return bool
	 */
	public function Remove ($path, $async = FALSE, $defer = FALSE, $notMin = FALSE) {
		return $this->execRemove($path, $async, $defer, $notMin, FALSE);
	}
	
	/**
	 * Add script into given index of scripts 
	 * group array for later render process.
	 * @param  int    $index
	 * @param  string $path
	 * @param  bool   $async
	 * @param  bool   $defer
	 * @param  bool   $notMin
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	public function Offset ($index, $path, $async = FALSE, $defer = FALSE, $notMin = FALSE) {
		return $this->execOffset($index, $path, $async, $defer, $notMin, FALSE, FALSE);
	}

	/**
	 * Append script after all group scripts 
	 * for later render process.
	 * @param  string $path
	 * @param  bool   $async
	 * @param  bool   $defer
	 * @param  bool   $notMin
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	public function Append ($path, $async = FALSE, $defer = FALSE, $notMin = FALSE) {
		return $this->execAppend($path, $async, $defer, $notMin, FALSE, FALSE);
	}

	/**
	 * Prepend script before all group 
	 * scripts for later render process.
	 * @param  string $path
	 * @param  bool   $async
	 * @param  bool   $defer
	 * @param  bool   $notMin
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	public function Prepend ($path, $async = FALSE, $defer = FALSE, $notMin = FALSE) {
		return $this->execPrepend($path, $async, $defer, $notMin, FALSE, FALSE);
	}

	/**
	 * Add script into given index of scripts 
	 * group array for later render process
	 * with downloading external content.
	 * @param  int    $index
	 * @param  string $path
	 * @param  bool   $async
	 * @param  bool   $defer
	 * @param  bool   $notMin
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	public function OffsetExternal ($index, $path, $async = FALSE, $defer = FALSE, $notMin = FALSE) {
		return $this->execOffset($index, $path, $async, $defer, $notMin, FALSE, TRUE);
	}

	/**
	 * Append script after all group scripts 
	 * for later render process with downloading 
	 * external content.
	 * @param  string $path
	 * @param  bool   $async
	 * @param  bool   $defer
	 * @param  bool   $notMin
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	public function AppendExternal ($path, $async = FALSE, $defer = FALSE, $notMin = FALSE) {
		return $this->execAppend($path, $async, $defer, $notMin, FALSE, TRUE);
	}

	/**
	 * Prepend script before all group 
	 * scripts for later render process 
	 * with downloading external content.
	 * @param  string $path
	 * @param  bool   $async
	 * @param  bool   $defer
	 * @param  bool   $notMin
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	public function PrependExternal ($path, $async = FALSE, $defer = FALSE, $notMin = FALSE) {
		return $this->execPrepend($path, $async, $defer, $notMin, FALSE, TRUE);
	}


	/**
	 * Check if script is already 
	 * presented in scripts group.
	 * This method is necessary to use 
	 * in vendor application packages.
	 * @param  string $path
	 * @param  bool   $async
	 * @param  bool   $defer
	 * @param  bool   $notMin
	 * @return bool
	 */
	public function VendorContains ($path, $async = FALSE, $defer = FALSE, $notMin = FALSE) {
		return $this->execContains($path, $async, $defer, $notMin, TRUE);
	}

	/**
	 * Remove script if it is already 
	 * presented in scripts group.
	 * This method is necessary to use 
	 * in vendor application packages.
	 * @param  string  $path
	 * @param  bool    $async
	 * @param  bool    $defer
	 * @param  bool    $notMin
	 * @return bool
	 */
	public function VendorRemove ($path, $async = FALSE, $defer = FALSE, $notMin = FALSE) {
		return $this->execRemove($path, $async, $defer, $notMin, TRUE);
	}
	
	/**
	 * Add script into given index of scripts 
	 * group array for later render process.
	 * This method is necessary to use 
	 * in vendor application packages.
	 * @param  int    $index
	 * @param  string $path
	 * @param  bool   $async
	 * @param  bool   $defer
	 * @param  bool   $notMin
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	public function VendorOffset ($index, $path, $async = FALSE, $defer = FALSE, $notMin = FALSE) {
		return $this->execOffset($index, $path, $async, $defer, $notMin, TRUE, FALSE);
	}

	/**
	 * Append script after all group scripts 
	 * for later render process.
	 * This method is necessary to use 
	 * in vendor application packages.
	 * @param  string $path
	 * @param  bool   $async
	 * @param  bool   $defer
	 * @param  bool   $notMin
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	public function VendorAppend ($path, $async = FALSE, $defer = FALSE, $notMin = FALSE) {
		return $this->execAppend($path, $async, $defer, $notMin, TRUE, FALSE);
	}

	/**
	 * Prepend script before all group 
	 * scripts for later render process.
	 * This method is necessary to use 
	 * in vendor application packages.
	 * @param  string $path
	 * @param  bool   $async
	 * @param  bool   $defer
	 * @param  bool   $notMin
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	public function VendorPrepend ($path, $async = FALSE, $defer = FALSE, $notMin = FALSE) {
		return $this->execPrepend($path, $async, $defer, $notMin, TRUE, FALSE);
	}

	/**
	 * Add script into given index of scripts 
	 * group array for later render process
	 * with downloading external content.
	 * This method is necessary to use 
	 * in vendor application packages.
	 * @param  int    $index
	 * @param  string $path
	 * @param  bool   $async
	 * @param  bool   $defer
	 * @param  bool   $notMin
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	public function VendorOffsetExternal ($index, $path, $async = FALSE, $defer = FALSE, $notMin = FALSE) {
		return $this->execOffset($index, $path, $async, $defer, $notMin, TRUE, TRUE);
	}

	/**
	 * Append script after all group scripts 
	 * for later render process with downloading 
	 * external content.
	 * This method is necessary to use 
	 * in vendor application packages.
	 * @param  string $path
	 * @param  bool   $async
	 * @param  bool   $defer
	 * @param  bool   $notMin
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	public function VendorAppendExternal ($path, $async = FALSE, $defer = FALSE, $notMin = FALSE) {
		return $this->execAppend($path, $async, $defer, $notMin, TRUE, TRUE);
	}

	/**
	 * Prepend script before all group 
	 * scripts for later render process 
	 * with downloading external content.
	 * This method is necessary to use 
	 * in vendor application packages.
	 * @param  string $path
	 * @param  bool   $async
	 * @param  bool   $defer
	 * @param  bool   $notMin
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	public function VendorPrependExternal ($path, $async = FALSE, $defer = FALSE, $notMin = FALSE) {
		return $this->execPrepend($path, $async, $defer, $notMin, TRUE, TRUE);
	}


	/**
	 * Check if script is already 
	 * presented in scripts group.
	 * @param  string $path
	 * @param  bool   $async
	 * @param  bool   $defer
	 * @param  bool   $notMin
	 * @param  bool   $vendor
	 * @return bool
	 */
	protected function execContains ($path, $async, $defer, $notMin, $vendor) {
		$reverseKey = $this->getGroupStoreReverseKey([$path, $async, $defer, $notMin, $vendor]);
		return isset($this->groupStoreReverseKeys[$reverseKey]);
	}

	/**
	 * Remove script if it is already 
	 * presented in scripts group.
	 * @param  string  $path
	 * @param  bool    $async
	 * @param  bool    $defer
	 * @param  bool    $notMin
	 * @param  bool    $vendor
	 * @param  bool    $external
	 * @return bool
	 */
	protected function execRemove ($path, $async, $defer, $notMin, $vendor, $external) {
		$result = FALSE;
		$scriptsGroup = & $this->getGroupStore();
		foreach ($scriptsGroup as $index => $item) {
			if (
				$item->path === $path &&
				$item->async === $async && 
				$item->defer === $defer && 
				$item->notMin === $notMin && 
				$item->vendor === $vendor
			) {
				$result = $this->unsetGroupStore($index);
				$reverseKey = $this->getGroupStoreReverseKey([$path, $async, $defer, $notMin, $vendor]);
				unset($this->groupStoreReverseKeys[$reverseKey]);
				break;
			}
		}
		return $result;
	}

	/**
	 * Add script into given index of scripts 
	 * group array for later render process.
	 * @param  int    $index
	 * @param  string $path
	 * @param  bool   $async
	 * @param  bool   $defer
	 * @param  bool   $notMin
	 * @param  bool   $external
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	protected function execOffset ($index, $path, $async, $defer, $notMin, $vendor, $external) {
		$item = $this->completeItem($path, $async, $defer, $notMin, $vendor, $external);
		$currentItems = & $this->getGroupStore();
		$newItems = $index > 0
			? array_slice($currentItems, 0, $index, FALSE)
			: [];
		$newItems[] = $item;
		$this->setUpGroupStoreReverseKey([$path, $async, $defer, $notMin, $vendor]);
		$currentItemsCount = count($currentItems);
		if ($index < count($currentItems)) 
			$newItems = array_merge($newItems, array_slice(
				$currentItems, $index, 
				$currentItemsCount - $index, FALSE
			));
		return $this->setGroupStore($newItems);
	}

	/**
	 * Append script after all group scripts 
	 * for later render process.
	 * @param  string $path
	 * @param  bool   $async
	 * @param  bool   $defer
	 * @param  bool   $notMin
	 * @param  bool   $vendor
	 * @param  bool   $external
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	protected function execAppend ($path, $async, $defer, $notMin, $vendor, $external) {
		$item = $this->completeItem($path, $async, $defer, $notMin, $vendor, $external);
		$currentGroupRecords = & $this->getGroupStore();
		array_push($currentGroupRecords, $item);
		$this->setUpGroupStoreReverseKey([$path, $async, $defer, $notMin, $vendor]);
		return $this;
	}

	/**
	 * Prepend script before all group 
	 * scripts for later render process.
	 * @param  string $path
	 * @param  bool   $async
	 * @param  bool   $defer
	 * @param  bool   $notMin
	 * @param  bool   $vendor
	 * @param  bool   $external
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	protected function execPrepend ($path, $async, $defer, $notMin, $vendor, $external) {
		$item = $this->completeItem($path, $async, $defer, $notMin, $vendor, $external);
		$currentGroupRecords = & $this->getGroupStore();
		array_unshift($currentGroupRecords, $item);
		$this->setUpGroupStoreReverseKey([$path, $async, $defer, $notMin, $vendor]);
		return $this;
	}


	/**
	 * Create data item to store for render process.
	 * @param  string    $path
	 * @param  string    $async
	 * @param  bool      $defer
	 * @param  bool      $notMin
	 * @param  bool      $vendor
	 * @param  bool      $external
	 * @return \stdClass
	 */
	protected function completeItem ($path, $async, $defer, $notMin, $vendor, $external) {
		if (self::$fileChecking) {
			$duplication = $this->isDuplicateScript($path, $vendor);
			if ($duplication !== NULL) 
				$this->warning("Script `{$path}` is already added in js group: `{$duplication}`.");
	}
		if ($vendor) 
			$path = $this->move2TmpGetPath(
				$path, self::$vendorDocRoot . $path, 'js'
			);
		return (object) [
			'fullPath'	=> static::$docRoot . $path,
			'path'		=> $path,
			'async'		=> $async,
			'defer'		=> $defer,
			'notMin'	=> $notMin,
			'vendor'	=> $vendor,
			'external'	=> $external,
		];
	}
	
	/**
	 * Is the linked script duplicate?
	 * @param  string $path
	 * @param  bool   $vendor
	 * @return string|NULL
	 */
	protected function isDuplicateScript ($path, $vendor) {
		$result = NULL;
		$allGroupItems = & $this->groupStore[$this->getCtrlActionKey()];
		foreach ($allGroupItems as $groupName => $groupItems) {
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
	 * Minify javascript string and return minified result.
	 * @param  string $js
	 * @param  string $path
	 * @return string
	 */
	protected function minify (& $js, $path) {
		$result = '';
		$errorMsg = "Unable to minify js: `{$path}`.";
		try {
			$result = \JShrink\Minifier::minify($js);
		} catch (\Throwable $e) {
			$this->exception($errorMsg);
		}
		return $result;
	}


	/**
	 * Render data items as one <script> html tag 
	 * or all another <script> html tags after 
	 * with files which is not possible to minify.
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
			$resultItems[] = '<!-- js group begin: ' . $this->currentGroupName . ' -->';

		// process array with groups, which are not possible to minimize
		foreach ($itemsToRenderSeparately as $itemsToRender) {
			$resultItems[] = $this->renderItemsTogetherAsGroup($itemsToRender, FALSE);
		}

		// process array with groups to minimize
		foreach ($itemsToRenderMinimized as $itemsToRender) {
			$resultItems[] = $this->renderItemsTogetherAsGroup($itemsToRender, $minify);
		}

		if (self::$fileRendering) 
			$resultItems[] = $indentStr . '<!-- js group end: ' . $this->currentGroupName . ' -->';
		
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
			if ($item->external) {
				$item->path = $this->download2TmpGetPath($item, $minify);
				$item->fullPath = static::$docRoot . $item->path;
				$filesGroupInfo[] = $item->path . '?_' . self::getFileImprint($item->fullPath);
			} else {
				if (self::$fileChecking) {
					if (!file_exists($item->fullPath)) {
						$this->exception("File not found in JS view rendering process ('{$item->fullPath}').");
					}
					$filesGroupInfo[] = $item->path . '?_' . self::getFileImprint($item->fullPath);
				} else {
					$filesGroupInfo[] = $item->path;
				}
			}
		}
		$tmpFileFullPath = $this->getTmpFileFullPathByPartFilesInfo(
			$filesGroupInfo, $minify, 'js'
		);

		// check, if the rendered, together completed and minimized file is in tmp cache already
		if (self::$fileRendering) {
			if (!file_exists($tmpFileFullPath)) {
				// load all items and join them together
				$resultContents = [];
				foreach ($itemsToRender as & $item) {
					if ($item->external) {
						$item->path = $this->download2TmpGetPath($item, $minify);
						$item->fullPath = static::$docRoot . $item->path;
						$fileContent = file_get_contents($item->fullPath);
					} else if ($minify) {
						$fileContent = file_get_contents($item->fullPath);
						if ($minify) $fileContent = $this->minify($fileContent, $item->path);
					} else {
						$fileContent = file_get_contents($item->fullPath);
					}
					$resultContents[] = "/* " . $item->path . " */\n" . $fileContent;
				}
				// save completed tmp file
				$this->saveFileContent($tmpFileFullPath, implode("\n\n", $resultContents));
				$this->log("Js files group rendered: `{$tmpFileFullPath}`.", 'debug');
			}
		}

		// complete <script> tag with tmp file path in $tmpFileFullPath variable
		$firstItem = array_merge((array) $itemsToRender[0], []);
		$pathToTmp = mb_substr($tmpFileFullPath, mb_strlen(static::$docRoot));
		$firstItem['src'] = $this->CssJsFileUrl($pathToTmp);
		return $this->renderItemSeparated((object) $firstItem);
	}

	/**
	 * Render data items as separated <script> html tags.
	 * @param  \stdClass[] $items
	 * @param  int	       $indent
	 * @param  bool        $minify
	 * @return string
	 */
	protected function renderItemsSeparated (array & $items, $indent, $minify) {
		$indentStr = $this->getIndentString($indent);
		$resultItems = [];
		if (self::$fileRendering) 
			$resultItems[] = '<!-- js group begin: ' . $this->currentGroupName . ' -->';
		foreach ($items as $item) {
			if ($item->external) {
				$item->src = $this->CssJsFileUrl($this->download2TmpGetPath($item, $minify));
			} else if ($minify && !$item->notMin) {
				$item->src = $this->CssJsFileUrl($this->render2TmpGetPath($item, $minify , 'js'));
			} else {
				$item->src = $this->CssJsFileUrl($item->path);
			}
			if (self::$fileChecking)
				$item->src = $this->addFileModImprint2HrefUrl($item->src, $item->fullPath);
			$resultItems[] = $this->renderItemSeparated($item);
		}
		if (self::$fileRendering) 
			$resultItems[] = '<!-- js group end: ' . $this->currentGroupName . ' -->';
		return "\n" . $indentStr . implode("\n" . $indentStr, $resultItems);
	}
	
	/**
	 * Create HTML script element from data item
	 * @param  \stdClass $item
	 * @return string
	 */
	protected function renderItemSeparated (\stdClass $item) {
		$result = ['<script type="text/javascript"'];
		if ($nonceAttr = static::getNonce(TRUE)) $result[] = $nonceAttr;
		if ($item->async) $result[] = ' async="async"';
		if ($item->defer) $result[] = ' defer="defer"';
		if (!$item->external && self::$fileChecking && !file_exists($item->fullPath)) {
			$this->log("File not found in JS view rendering process: `{$item->fullPath}`.", 'error');
		}
		$result[] = ' src="' . $item->src . '"></script>';
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
		$fileContent = file_get_contents($srcFileFullPath);
		if ($minify)
			$fileContent = $this->minify($fileContent, $item->path);
		return $fileContent;
	}

	/**
	 * Download js file by path and store result 
	 * in tmp directory and return new href value.
	 * @param \stdClass $item
	 * @param bool      $minify
	 * @return string
	 */
	protected function download2TmpGetPath ($item, $minify) {
		$path = $item->path;
		$tmpFileName = $this->getTmpFileName($item->path, 'external');
		$tmpFileFullPath = $this->getTmpDir() . $tmpFileName;
		if (self::$fileRendering) {
			if (file_exists($tmpFileFullPath)) {
				$cacheFileTime = filemtime($tmpFileFullPath);
			} else {
				$cacheFileTime = 0;
			}
			if (time() > $cacheFileTime + self::EXTERNAL_MIN_CACHE_TIME) {
				while (TRUE) {
					$newPath = $this->getPossiblyRedirectedPath($path);
					if ($newPath === $path) {
						break;
					} else {
						$path = $newPath;
					}
				}
				$fr = fopen($path, 'r');
				$fileContents = [];
				$bufferLength = 102400; // 100 KB
				$buffer = '';
				while ($buffer = fread($fr, $bufferLength)) {
					$fileContents[] = $buffer;
				}
				fclose($fr);
				$fileContent = implode('', $fileContents);
				if ($minify) 
					$fileContent = $this->minify($fileContent, $path);
				$this->saveFileContent($tmpFileFullPath, $fileContent);
				$this->log("External js file downloaded: `{$tmpFileFullPath}`.", 'debug');
			}
		}
		$tmpPath = substr($tmpFileFullPath, strlen(static::$docRoot));
		return $tmpPath;
	}

	/**
	 * If there is any redirection in external 
	 * content path - get redirect path.
	 * @param  string $path
	 * @return string
	 */
	protected function getPossiblyRedirectedPath ($path) {
		$fp = fopen($path, 'r');
		$metaData = stream_get_meta_data($fp);
		foreach ($metaData['wrapper_data'] as $response) {
			// Were we redirected? */
			if (mb_strtolower(mb_substr($response, 0, 9)) === 'location:') {
				// update $src with where we were redirected to
				$path = trim(mb_substr($response, 9));
			}
		}
		return $path;
	}
}

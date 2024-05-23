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
use MvcCore\Ext\Models\Db\Exception;

/**
 * @method static \MvcCore\Ext\Views\Helpers\JsHelper GetInstance()
 */
class JsHelper extends Assets {

	/**
	 * Whatever Expires header is send over http scheme,
	 * minimal cache time for external files will be one
	 * day from last download
	 * @const integer
	 */
	const EXTERNAL_MIN_CACHE_TIME = 86400;

	/**
	 * TypeScript source map detection substring in JavaScript source end.
	 */
	const TS_MAP_DETECT_SUBSTR = '//# sourceMappingURL=';

	/**
	 * Buffer length to load last bytes from source file,
	 * where could be TypeScript source map definition.
	 */
	const TS_MAP_DETECT_MAX_MAP_FILENAME_LENGTH = 512;


	/**
	 * @inheritDoc
	 * @var \MvcCore\Ext\Views\Helpers\JsHelper|NULL
	 */
	protected static $instance = NULL;

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
		$minify = (bool) static::$globalOptions['jsMinify'];
		$joinTogether = (bool) static::$globalOptions['jsJoin'];
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
	public function ExternalOffset ($index, $path, $async = FALSE, $defer = FALSE, $notMin = FALSE) {
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
	public function ExternalAppend ($path, $async = FALSE, $defer = FALSE, $notMin = FALSE) {
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
	public function ExternalPrepend ($path, $async = FALSE, $defer = FALSE, $notMin = FALSE) {
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
		$reverseKey = $this->getGroupStoreReverseKey(func_get_args());
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
		if (static::$fileChecking) {
			$duplication = $this->isDuplicateScript($path, $vendor);
			if ($duplication !== NULL) 
				$this->warning("Script `{$path}` is already added in js group: `{$duplication}`.");
		}
		if ($external) {
			$url = $path;
			$path = $this->getSignificantPathPartFromFullPath($url);
			$publicFullPath = $this->download2TmpGetPath($url, $path);
		} else {
			$docRootPrefix = mb_strpos($path, '~/') === 0;
			if ($vendor) {
				if ($docRootPrefix) {
					$vendorFullPath = static::$vendorDocRoot . mb_substr($path, 1);
				} else {
					$vendorFullPath = $path;
					$path = $this->getSignificantPathPartFromFullPath($path);
				}
				list(, $path) = $this->move2TmpGetPath(
					$path, $vendorFullPath, 'js'
				);
				$publicFullPath = static::$docRoot . $path;
			} else if (!$external) {
				if ($docRootPrefix) {
					$path = mb_substr($path, 1);
					$publicFullPath = static::$docRoot . $path;
				} else {
					$publicFullPath = $path;
					if (mb_strpos($publicFullPath, static::$docRoot) !== 0) {
						$path = $this->getSignificantPathPartFromFullPath($path);
						list(, $path) = $this->move2TmpGetPath(
							$path, $publicFullPath, 'js'
						);
						$publicFullPath = static::$docRoot . $path;
					}
				}
			}
		}
		return (object) [
			'fullPath'	=> $publicFullPath,
			'path'		=> $path,
			'async'		=> $async,
			'defer'		=> $defer,
			'notMin'	=> $notMin,
			'vendor'	=> $vendor,
			'external'	=> $external,
		];
	}

	/**
	 * Move file from any vendor assets directory into 
	 * assets tmp dir. Return new asset path in tmp directory.
	 * @param  string $path 
	 * @param  string $fullPath 
	 * @return array[bool, string]
	 */
	protected function move2TmpGetPath ($path, $srcFileFullPath, $type) {
		list($coppied, $newPath) = parent::move2TmpGetPath($path, $srcFileFullPath, $type);
		if ($coppied && static::$devMode)
			$this->move2TmpTsMapAndSource($newPath, $srcFileFullPath);
		return [$coppied, $newPath];
	}

	/**
	 * Try to detect TypeScript map definition in the last line 
	 * of JS source file. If there is TS source definition,
	 * create new TS map file base od original map file in tmp dir
	 * and move TypeScript source into tmp dir with tmp file name.
	 * @param  string $newPath 
	 * @param  string $srcFileFullPath 
	 * @return void
	 */
	protected function move2TmpTsMapAndSource ($newPath, $srcFileFullPath) {
		try {
			$tmpFileFullPath = static::$docRoot . $newPath;
			// load last 1024 bytes:
			$fileMask = static::$globalOptions['fileMask'];
			@chmod($tmpFileFullPath, $fileMask);
			$handle = fopen($tmpFileFullPath, 'a+');
			$fileSize = filesize($tmpFileFullPath);
			$tsMapDetectSubstr = static::TS_MAP_DETECT_SUBSTR;
			$tsMapDetectSubstrLen = strlen($tsMapDetectSubstr);
			$bufferSize = $tsMapDetectSubstrLen + static::TS_MAP_DETECT_MAX_MAP_FILENAME_LENGTH + 2;
			fseek($handle, $fileSize - $bufferSize);
			$lastContent = fread($handle, $bufferSize);
			$lastContentNormalized = trim(str_replace(["\r\n", "\r"], "\n", $lastContent));
			$lastLines = explode("\n", $lastContentNormalized);
			// get last source line:
			$lastLine = $lastLines[count($lastLines) - 1];
			unset($lastContentNormalized, $lastLines);
			// check if there is TS map definition:
			if (mb_strpos($lastLine, $tsMapDetectSubstr) !== 0) 
				throw new \Exception("No TS map definition.");
			// get TS map file name:
			$tsMapFileName = mb_substr($lastLine, $tsMapDetectSubstrLen);
			// load JSON map:
			$lastSlashPos = mb_strrpos($srcFileFullPath, '/');
			if ($lastSlashPos === FALSE) 
				throw new \Exception("Not possible to complete source dir.");
			$srcFileDir = mb_substr($srcFileFullPath, 0, $lastSlashPos);
			$lastSlashPos = mb_strrpos($tmpFileFullPath, '/');
			if ($lastSlashPos === FALSE) 
				throw new \Exception("Not possible to complete target dir.");
			$targetFileDir = mb_substr($tmpFileFullPath, 0, $lastSlashPos);
			$targetFileName = mb_substr($tmpFileFullPath, $lastSlashPos + 1);
			$mapSrcFileFullPath = $srcFileDir . '/' . $tsMapFileName;
			$rawMapJson = file_get_contents($mapSrcFileFullPath);
			$toolClass = static::$app->GetToolClass();
			$mapJson = $toolClass::JsonDecode($rawMapJson);
			if (!(is_array($mapJson->sources) && count($mapJson->sources) === 1))
				throw new \Exception("TS source is not single file.");
			// get previous source location:
			$origSource = $mapJson->sources[0];
			// change js source file and new map location in tmp:
			$mapJson->file = $targetFileName;
			$tmpTsName = mb_substr($targetFileName, 0, -2) . 'ts';
			$mapJson->sources = ['./' . $tmpTsName];
			// define new map name and save map:
			$rawMapJson = $toolClass::JsonEncode($mapJson);
			$tmpMapName = $targetFileName . '.map';
			$mapTargetFileFullPath = $targetFileDir . '/' . $tmpMapName;
			if (file_exists($mapTargetFileFullPath)) {
				@chmod($mapTargetFileFullPath, $fileMask);
				unlink($mapTargetFileFullPath);
			}
			$toolClass::AtomicWrite($mapTargetFileFullPath, $rawMapJson);
			@chmod($mapTargetFileFullPath, $fileMask);
			unset($mapJson, $rawMapJson);
			// change JS map definition in moved js file:
			$tsDefPosInLastContent = strpos($lastContent, $tsMapDetectSubstr);
			$tsDefPosInBuffer = $bufferSize - $tsDefPosInLastContent;
			$tsDefPosInFile = $fileSize - $tsDefPosInBuffer;
			if ($tsDefPosInFile < 0) $tsDefPosInFile = 0;
			ftruncate($handle, $tsDefPosInFile);
			fseek($handle, $tsDefPosInFile);
			fwrite($handle, $tsMapDetectSubstr . $tmpMapName);
			fclose($handle);
			@chmod($tmpFileFullPath, $fileMask);
			unset($handle, $fileSize, $bufferSize, $lastContent);
			// move source typescript into tmp:
			$tsSrcFullPath = $toolClass::RealPathVirtual($srcFileDir . '/' . $origSource);
			$tmpTsFullPath = $targetFileDir . '/' . $tmpTsName;
			if (file_exists($tmpTsFullPath)) {
				@chmod($tmpTsFullPath, $fileMask);
				unlink($tmpTsFullPath);
			}
			$copied = copy($tsSrcFullPath, $tmpTsFullPath);
			@chmod($tmpTsFullPath, $fileMask);
			if (!$copied) 
				throw new \Exception("Not possible to copy TS source.");
		} catch (\Throwable $e) {
		}
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
		if (static::$fileRendering) 
			$resultItems[] = '<!-- js group begin: ' . $this->currentGroupName . ' -->';

		// process array with groups, which are not possible to minimize
		foreach ($itemsToRenderSeparately as $itemsToRender) {
			$resultItems[] = $this->renderItemsTogetherAsGroup($itemsToRender, FALSE);
		}

		// process array with groups to minimize
		foreach ($itemsToRenderMinimized as $itemsToRender) {
			$resultItems[] = $this->renderItemsTogetherAsGroup($itemsToRender, $minify);
		}

		if (static::$fileRendering) 
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
			if (static::$fileChecking) {
				if (!file_exists($item->fullPath)) {
					$this->exception("File not found in JS view rendering process ('{$item->fullPath}').");
				}
				$filesGroupInfo[] = $item->path . '?_' . static::getFileImprint($item->fullPath);
			} else {
				$filesGroupInfo[] = $item->path;
			}
		}
		$tmpFileFullPath = $this->getTmpFileFullPathByPartFilesInfo(
			$filesGroupInfo, $minify, 'js'
		);

		// check, if the rendered, together completed and minimized file is in tmp cache already
		if (static::$fileRendering) {
			if (!file_exists($tmpFileFullPath)) {
				// load all items and join them together
				$resultContents = [];
				foreach ($itemsToRender as & $item) {
					if ($minify) {
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
		if (static::$fileRendering) 
			$resultItems[] = '<!-- js group begin: ' . $this->currentGroupName . ' -->';
		foreach ($items as $item) {
			if ($minify && !$item->notMin) {
				$item->src = $this->CssJsFileUrl($this->render2TmpGetPath($item, $minify , 'js'));
			} else {
				$item->src = $this->CssJsFileUrl($item->path);
			}
			if (static::$fileChecking)
				$item->src = $this->addFileModImprint2HrefUrl($item->src, $item->fullPath);
			$resultItems[] = $this->renderItemSeparated($item);
		}
		if (static::$fileRendering) 
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
		if (!$item->external && static::$fileChecking && !file_exists($item->fullPath)) {
			$this->log("File not found in JS view rendering process: `{$item->fullPath}`.", 'error');
		}
		$result[] = ' src="' . $item->src . '"></script>';
		return implode('', $result);
	}


	/**
	 * @inheritDoc
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
	 * in tmp directory and return new fullpath value.
	 * @param  string $url
	 * @param  string $path
	 * @return string
	 */
	protected function download2TmpGetPath ($url, $path) {
		$currentPath = $url;
		$tmpFileName = $this->getTmpFileName($url, $path, 'e');
		$tmpFileFullPath = $this->getTmpDir() . $tmpFileName;
		if (static::$fileRendering) {
			if (file_exists($tmpFileFullPath)) {
				$cacheFileTime = filemtime($tmpFileFullPath);
			} else {
				$cacheFileTime = 0;
			}
			if (time() > $cacheFileTime + static::EXTERNAL_MIN_CACHE_TIME) {
				while (TRUE) {
					$newPath = $this->getPossiblyRedirectedPath($currentPath);
					if ($newPath === $currentPath) {
						break;
					} else {
						$currentPath = $newPath;
					}
				}
				$fr = fopen($currentPath, 'r');
				$fileContents = [];
				$bufferLength = 102400; // 100 KB
				$buffer = '';
				while ($buffer = fread($fr, $bufferLength)) {
					$fileContents[] = $buffer;
				}
				fclose($fr);
				$fileContent = implode('', $fileContents);
				$this->saveFileContent($tmpFileFullPath, $fileContent);
				$this->log("External js file downloaded: `{$tmpFileFullPath}`.", 'debug');
			}
		}
		return $tmpFileFullPath;
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

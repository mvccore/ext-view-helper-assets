<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md
 */

namespace MvcCore\Ext\Views\Helpers;

class JsHelper extends Assets
{
	protected static $instance = null;
	/**
	 * Whatever Expires header is send over http protocol,
	 * minimal cache time for external files will be one
	 * day from last download
	 * @const integer
	 */
	const EXTERNAL_MIN_CACHE_TIME = 86400;

	/**
	 * Array with full class name and public method accepted as first param javascript code and returning minified code
	 * @var callable
	 */
	public static $MinifyCallable = ['\JSMin', 'minify'];

	/**
	 * Array with all defined files to create specific script tags
	 * @var array
	 */
	protected static $scriptsGroupContainer = [];

	/**
	 * View Helper Method, returns current object instance.
	 * @param  string $groupName string identifier
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	public function Js ($groupName = self::GROUP_NAME_DEFAULT) {
		$this->actualGroupName = $groupName;
		$this->_getScriptsGroupContainer($groupName);
		return $this;
	}

	/**
	 * Check if script is already presented in scripts group
	 * @param  string  $path
	 * @param  boolean $async
	 * @param  boolean $defer
	 * @param  boolean $doNotMinify
	 * @return bool
	 */
	public function Contains ($path = '', $async = FALSE, $defer = FALSE, $doNotMinify = FALSE) {
		$result = FALSE;
		$scriptsGroup = & $this->_getScriptsGroupContainer($this->actualGroupName);
		foreach ($scriptsGroup as & $item) {
			if ($item->path == $path) {
				if ($item->async == $async && $item->defer == $defer && $item->doNotMinify == $doNotMinify) {
					$result = TRUE;
					break;
				}
			}
		}
		return $result;
	}

	/**
	 * Append script after all group scripts for later render process with downloading external content
	 * @param  string  $path
	 * @param  boolean $async
	 * @param  boolean $defer
	 * @param  boolean $doNotMinify
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	public function AppendExternal ($path = '', $async = FALSE, $defer = FALSE, $doNotMinify = FALSE) {
		return $this->Append($path, $async, $defer, $doNotMinify, TRUE);
	}

	/**
	 * Prepend script before all group scripts for later render process with downloading external content
	 * @param  string  $path
	 * @param  boolean $async
	 * @param  boolean $defer
	 * @param  boolean $doNotMinify
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	public function PrependExternal ($path = '', $async = FALSE, $defer = FALSE, $doNotMinify = FALSE) {
		return $this->Prepend($path, $async, $defer, $doNotMinify, TRUE);
	}

	/**
	 * Add script into given index of scripts group array for later render process with downloading external content
	 * @param  integer $index
	 * @param  string  $path
	 * @param  boolean $async
	 * @param  boolean $defer
	 * @param  boolean $doNotMinify
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	public function OffsetExternal ($index = 0, $path = '', $async = FALSE, $defer = FALSE, $doNotMinify = FALSE) {
		return $this->Offset($index, $path, $async, $defer, $doNotMinify, TRUE);
	}

	/**
	 * Append script after all group scripts for later render process
	 * @param  string  $path
	 * @param  boolean $async
	 * @param  boolean $defer
	 * @param  boolean $doNotMinify
	 * @param  boolean $external
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	public function Append ($path = '', $async = FALSE, $defer = FALSE, $doNotMinify = FALSE, $external = FALSE) {
		$item = $this->_completeItem($path, $async, $defer, $doNotMinify, $external);
		$actialGroupItems = & $this->_getScriptsGroupContainer($this->actualGroupName);
		array_push($actialGroupItems, $item);
		return $this;
	}

	/**
	 * Prepend script before all group scripts for later render process
	 * @param  string  $path
	 * @param  boolean $async
	 * @param  boolean $defer
	 * @param  boolean $doNotMinify
	 * @param  boolean $external
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	public function Prepend ($path = '', $async = FALSE, $defer = FALSE, $doNotMinify = FALSE, $external = FALSE) {
		$item = $this->_completeItem($path, $async, $defer, $doNotMinify, $external);
		$actualGroupItems = & $this->_getScriptsGroupContainer($this->actualGroupName);
		array_unshift($actualGroupItems, $item);
		return $this;
	}

	/**
	 * Add script into given index of scripts group array for later render process
	 * @param  integer $index
	 * @param  string  $path
	 * @param  boolean $async
	 * @param  boolean $defer
	 * @param  boolean $doNotMinify
	 * @param  boolean $external
	 * @return \MvcCore\Ext\Views\Helpers\JsHelper
	 */
	public function Offset ($index = 0, $path = '', $async = FALSE, $defer = FALSE, $doNotMinify = FALSE, $external = FALSE) {
		$item = $this->_completeItem($path, $async, $defer, $doNotMinify, $external);
		$actialGroupItems = & $this->_getScriptsGroupContainer($this->actualGroupName);
		$newItems = [];
		$added = FALSE;
		foreach ($actialGroupItems as $key => & $groupItem) {
			if ($key == $index) {
				$newItems[] = $item;
				$added = TRUE;
			}
			$newItems[] = $groupItem;
		}
		if (!$added) $newItems[] = $item;
		self::$scriptsGroupContainer[$this->getCtrlActionKey()][$this->actualGroupName] = $newItems;
		return $this;
	}

	/**
	 * Get actualy dispatched controller/action group name
	 * @param string $name
	 * @return array
	 */
	private function & _getScriptsGroupContainer ($name = '') {
		$ctrlActionKey = $this->getCtrlActionKey();
		if (!isset(self::$scriptsGroupContainer[$ctrlActionKey])) {
			self::$scriptsGroupContainer[$ctrlActionKey] = [];
		}
		if (!isset(self::$scriptsGroupContainer[$ctrlActionKey][$name])) {
			self::$scriptsGroupContainer[$ctrlActionKey][$name] = [];
		}
		return self::$scriptsGroupContainer[$ctrlActionKey][$name];
	}

	/**
	 * Create data item to store for render process
	 * @param  string  $path
	 * @param  string  $async
	 * @param  boolean $defer
	 * @param  boolean $doNotMinify
	 * @param  boolean $external
	 * @return \stdClass
	 */
	private function _completeItem ($path, $async, $defer, $doNotMinify, $external) {
		if (self::$logingAndExceptions) {
			if (!$path) $this->exception('Path to *.js can\'t be an empty string.');
			$duplication = $this->_isDuplicateScript($path);
			if ($duplication) $this->warning("Script '$path' is already added in js group: '$duplication'.");
		}
		return (object) [
			'path'			=> $path,
			'async'			=> $async,
			'defer'			=> $defer,
			'doNotMinify'	=> $doNotMinify,
			'external'		=> $external,
		];
	}

	/**
	 * Is the linked script duplicate?
	 * @param  string $path
	 * @return string
	 */
	private function _isDuplicateScript ($path) {
		$result = '';
		$allGroupItems = & self::$scriptsGroupContainer[$this->getCtrlActionKey()];
		foreach ($allGroupItems as $groupName => $groupItems) {
			foreach ($groupItems as $item) {
				if ($item->path == $path) {
					$result = $groupName;
					break;
				}
			}
		}
		return $result;
	}

	/**
	 * Render script elements as html code with links to original files or temporary downloaded files
	 * @param  int $indent
	 * @return string
	 */
	public function Render ($indent = 0) {
		$actualGroupItems = & $this->_getScriptsGroupContainer($this->actualGroupName);
		if (count($actualGroupItems) === 0) return '';
		$minify = (bool)self::$globalOptions['jsMinify'];
		$joinTogether = (bool)self::$globalOptions['jsJoin'];
		if ($joinTogether) {
			$result = $this->_renderItemsTogether(
				$this->actualGroupName,
				$actualGroupItems,
				$indent,
				$minify
			);
		} else {
			$result = $this->_renderItemsSeparated(
				$this->actualGroupName,
				$actualGroupItems,
				$indent,
				$minify
			);
		}
		return $result;
	}

	/**
	 * Render data items as separated <script> html tags
	 * @param string  $actualGroupName
	 * @param array   $items
	 * @param int	 $indent
	 * @param boolean $minify
	 * @return string
	 */
	private function _renderItemsSeparated ($actualGroupName = '', $items = [], $indent = 0, $minify = FALSE) {
		$indentStr = $this->getIndentString($indent);
		$resultItems = [];
		if (self::$fileRendering) $resultItems[] = '<!-- js group begin: ' . $actualGroupName . ' -->';
		$appCompilation = \MvcCore\Application::GetInstance()->GetCompiled();
		foreach ($items as $item) {
			if ($item->external) {
				$item->src = $this->CssJsFileUrl($this->_downloadFileToTmpAndGetNewHref($item, $minify));
			} else if ($minify && !$item->doNotMinify) {
				$item->src = $this->CssJsFileUrl($this->_renderFileToTmpAndGetNewHref($item, $minify));
			} else {
				$item->src = $this->CssJsFileUrl($item->path);
			}
			if (!$appCompilation) {
				/*if ($item->external) {
					$tmpOrSrcPath = substr($item->src, strlen(self::$basePath));
				} else {
					$tmpOrSrcPath = $item->src;
				}*/
				$item->src = $this->addFileModificationImprintToHrefUrl($item->src, $item->path);
			}
			$resultItems[] = $this->_renderItemSeparated($item);
		}
		if (self::$fileRendering) $resultItems[] = '<!-- js group end: ' . $actualGroupName . ' -->';
		return $indentStr . implode(PHP_EOL . $indentStr, $resultItems);
	}

	/**
	 * Render js file by path and store result in tmp directory and return new href value
	 * @param \stdClass $item
	 * @param boolean  $minify
	 * @return string
	 */
	private function _renderFileToTmpAndGetNewHref ($item, $minify = FALSE) {
		$path = $item->path;
		$tmpFileName = '/rendered_js_' . self::$systemConfigHash . '_' . trim(str_replace('/', '_', $path), "_");
		$srcFileFullPath = $this->getAppRoot() . $path;
		$tmpFileFullPath = $this->getTmpDir() . $tmpFileName;
		if (self::$fileRendering) {
			if (file_exists($srcFileFullPath)) {
				$srcFileModDate = filemtime($srcFileFullPath);
			} else {
				$srcFileModDate = 1;
			}
			if (file_exists($tmpFileFullPath)) {
				$tmpFileModDate = filemtime($tmpFileFullPath);
			} else {
				$tmpFileModDate = 0;
			}
			if ($srcFileModDate !== FALSE && $tmpFileModDate !== FALSE) {
				if ($srcFileModDate > $tmpFileModDate) {
					$fileContent = file_get_contents($srcFileFullPath);
					if ($minify) {
						$fileContent = $this->_minify($fileContent, $path);
					}
					$this->saveFileContent($tmpFileFullPath, $fileContent);
					$this->log("Js file rendered ('$tmpFileFullPath').", 'debug');
				}
			}
		}
		$tmpPath = substr($tmpFileFullPath, strlen($this->getAppRoot()));
		return $tmpPath;
	}

	/**
	 * Download js file by path and store result in tmp directory and return new href value
	 * @param \stdClass $item
	 * @param boolean  $minify
	 * @return string
	 */
	private function _downloadFileToTmpAndGetNewHref ($item, $minify = FALSE) {
		$path = $item->path;
		$tmpFileFullPath = $this->getTmpDir() . '/external_js_' . md5($path) . '.js';
		if (self::$fileRendering) {
			if (file_exists($tmpFileFullPath)) {
				$cacheFileTime = filemtime($tmpFileFullPath);
			} else {
				$cacheFileTime = 0;
			}
			if (time() > $cacheFileTime + self::EXTERNAL_MIN_CACHE_TIME) {
				while (TRUE) {
					$newPath = $this->_getPossiblyRedirectedPath($path);
					if ($newPath === $path) {
						break;
					} else {
						$path = $newPath;
					}
				}
				$fr = fopen($path, 'r');
				$fileContent = '';
				$bufferLength = 102400; // 100 KB
				$buffer = '';
				while ($buffer = fread($fr, $bufferLength)) {
					$fileContent .= $buffer;
				}
				fclose($fr);
				if ($minify) {
					$fileContent = $this->_minify($fileContent, $path);
				}
				$this->saveFileContent($tmpFileFullPath, $fileContent);
				$this->log("External js file downloaded ('$tmpFileFullPath').", 'debug');
			}
		}
		$tmpPath = substr($tmpFileFullPath, strlen($this->getAppRoot()));
		return $tmpPath;
	}

	/**
	 * If there is any redirection in external content path - get redirect path
	 * @param string $path
	 * @return string
	 */
	private function _getPossiblyRedirectedPath ($path = '') {
		$fp = fopen($path, 'r');
		$metaData = stream_get_meta_data($fp);
		foreach ($metaData['wrapper_data'] as $response) {
			// Were we redirected? */
			if (strtolower(substr($response, 0, 10)) == 'location: ') {
				// update $src with where we were redirected to
				$path = substr($response, 10);
			}
		}
		return $path;
	}

	/**
	 * Create HTML script element from data item
	 * @param  \stdClass $item
	 * @return string
	 */
	private function _renderItemSeparated (\stdClass $item) {
		$result = '<script type="text/javascript"';
		if ($item->async) $result .= ' async="async"';
		if ($item->async) $result .= ' defer="defer"';
		if (!$item->external && self::$fileChecking) {
			$fullPath = $this->getAppRoot() . $item->path;
			if (!file_exists($fullPath)) {
				$this->log("File not found in CSS view rendering process ('$fullPath').", 'error');
			}
		}
		$result .= ' src="' . $item->src . '"></script>';
		return $result;
	}

	/**
	 * Minify javascript string and return minified result
	 * @param string $js
	 * @param string $path
	 * @return string
	 */
	private function _minify (& $js, $path) {
		$result = '';
		if (!is_callable(static::$MinifyCallable)) {
			$this->exception(
				"Configured callable object for JS minification doesn't exist. "
				."Use: https://github.com/mrclay/minify -> /min/lib/JSMin.php"
			);
		}
		try {
			$result = call_user_func(static::$MinifyCallable, $js);
		} catch (\Exception $e) {
			$this->exception("Unable to minify javascript ('$path').");
		}
		return $result;
	}

	/**
	 * Render data items as one <script> html tag or all another <script> html tags after with files which is not possible to minify.
	 * @param string  $actualGroupName
	 * @param array   $items
	 * @param int	 $indent
	 * @param boolean $minify
	 * @return string
	 */
	private function _renderItemsTogether ($actualGroupName = '', $items = [], $indent, $minify = FALSE) {

		// some configurations is not possible to render together and minimized
		list($itemsToRenderMinimized, $itemsToRenderSeparately) = $this->filterItemsForNotPossibleMinifiedAndPossibleMinifiedItems($items);

		$indentStr = $this->getIndentString($indent);
		$resultItems = [];
		if (self::$fileRendering) $resultItems[] = '<!-- js group begin: ' . $actualGroupName . ' -->';

		// process array with groups, which are not possible to minimize
		foreach ($itemsToRenderSeparately as & $itemsToRender) {
			$resultItems[] = $this->_renderItemsTogetherAsGroup($itemsToRender, FALSE);
		}

		// process array with groups to minimize
		foreach ($itemsToRenderMinimized as & $itemsToRender) {
			$resultItems[] = $this->_renderItemsTogetherAsGroup($itemsToRender, $minify);
		}

		if (self::$fileRendering) $resultItems[] = $indentStr . '<!-- js group end: ' . $actualGroupName . ' -->';

		return $indentStr . implode(PHP_EOL, $resultItems);
	}

	/**
	 * Render all items in group together, when application is compiled, do not check source files and changes.
	 * @param array   $itemsToRender
	 * @param boolean $minify
	 * @return string
	 */
	private function _renderItemsTogetherAsGroup ($itemsToRender = [], $minify = FALSE) {

		// complete tmp filename by source filenames and source files modification times
		$filesGroupInfo = [];
		foreach ($itemsToRender as $item) {
			if ($item->external) {
				$srcFileFullPath = $this->_downloadFileToTmpAndGetNewHref($item, $minify);
				$filesGroupInfo[] = $item->path . '?_' . self::getFileImprint($this->getAppRoot() . $srcFileFullPath);
			} else {
				if (self::$fileChecking) {
					$fullPath = $this->getAppRoot() . $item->path;
					if (!file_exists($fullPath)) {
						$this->exception("File not found in JS view rendering process ('$fullPath').");
					}
					$filesGroupInfo[] = $item->path . '?_' . self::getFileImprint($fullPath);
				} else {
					$filesGroupInfo[] = $item->path;
				}
			}
		}
		$tmpFileFullPath = $this->getTmpFileFullPathByPartFilesInfo($filesGroupInfo, $minify, 'js');

		// check, if the rendered, together completed and minimized file is in tmp cache already
		if (self::$fileRendering) {
			if (!file_exists($tmpFileFullPath)) {
				// load all items and join them together
				$resultContent = '';
				foreach ($itemsToRender as & $item) {
					$srcFileFullPath = $this->getAppRoot() . $item->path;
					if ($item->external) {
						$srcFileFullPath = $this->_downloadFileToTmpAndGetNewHref($item, $minify);
						$fileContent = file_get_contents($this->getAppRoot() . $srcFileFullPath);
					} else if ($minify) {
						$fileContent = file_get_contents($srcFileFullPath);
						if ($minify) $fileContent = $this->_minify($fileContent, $item->path);
					} else {
						$fileContent = file_get_contents($srcFileFullPath);
					}
					$resultContent .= PHP_EOL . "/* " . $item->path . " */" . PHP_EOL . $fileContent . PHP_EOL;
				}
				// save completed tmp file
				$this->saveFileContent($tmpFileFullPath, $resultContent);
				$this->log("Js files group rendered ('$tmpFileFullPath').", 'debug');
			}
		}

		// complete <link> tag with tmp file path in $tmpFileFullPath variable
		$firstItem = array_merge((array) $itemsToRender[0], []);
		$pathToTmp = substr($tmpFileFullPath, strlen($this->getAppRoot()));
		$firstItem['src'] = $this->CssJsFileUrl($pathToTmp);

		return $this->_renderItemSeparated((object) $firstItem);
	}
}

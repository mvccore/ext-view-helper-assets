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
 * @method \MvcCore\Ext\Views\Helpers\Assets GetInstance()
 */
abstract class Assets extends \MvcCore\Ext\Views\Helpers\AbstractHelper {

	/**
	 * MvcCore Extension - View Helper - Assets - version:
	 * Comparison by PHP function version_compare();
	 * @see http://php.net/manual/en/function.version-compare.php
	 */
	const VERSION = '5.1.7';

	/**
	 * Default link group name
	 * @const string
	 */
	const GROUP_NAME_DEFAULT   = 'default';

	/**
	 * Internal string replacement help.
	 * @var string
	 */
	const REL_BASE_PATH_PLACEMENT = '__RELATIVE_BASE_PATH__';

	/**
	 * Date format for ?_fmd param timestamp in admin development mode
	 * @const string
	 */
	const FILE_MODIFICATION_DATE_FORMAT = 'Y-m-d_H-i-s';


	/**
	 * Simple app view object
	 * @var \MvcCore\View
	 */
	protected $view;
	
	/**
	 * Array with all defined files to create specific link tags.
	 * @var array<string, <string, \stdClass[]>>
	 */
	protected $groupStore = [];
	
	/**
	 * Reverse map with unique keys into group store tree.
	 * @var array<string, string[]>
	 */
	protected $groupStoreReverseKeys = [];

	/**
	 * Called `$this->groupStore[]` index through helper function Css() or Js()
	 * @var string
	 */
	protected $currentGroupName = '';

	/**
	 * Stream wrapper for actual file save operations (http://php.net/stream_wrapper_register)
	 * @var string
	 */
	protected $streamWrapper = '';

	/**
	 * Global options about joining and minifying which
	 * can bee overwritten by single settings throw calling
	 * for example: append() method as another param.
	 * All possible options and meanings:
	 * - jsJoin			- boolean	- join JS files in JS group together into single file in tmp dir
	 * - jsMinify		- boolean	- minify JS file in any group by default (it's possible to override for each file)
	 * - cssJoin		- boolean	- join CSS files in CSS group together into single file in tmp dir
	 * - cssMinify		- boolean	- minify CSS file in any group by default (it's possible to override for each file)
	 * - tmpDir			- string	- relative path to temporary dir from application document root, by default: '/Var/Tmp'
	 * - fileChecking	- string	- php function names: md5_file | filemtime (filemtime is not working for PHAR packages,
	 *								  for PHAR packages use slower 'md5_file' value)
	 * - assetsUrl		- boolean	- strictly enable or disable special packge assets URL completion in form:
	 *								  '?controller=controller&action=asset&path=...', by default, this switch is
	 *								  automatically detected by application compile mode. In every compile mode except
	 *								  development mode and strict HDD mode is this switch configured internally to true.
	 * @var array
	 */
	protected static $globalOptions = [
		'jsJoin'		=> 0,
		'jsMinify'		=> 0,
		'cssJoin'		=> 0,
		'cssMinify'		=> 0,
		'tmpDir'		=> '~/Var/Tmp',
		'fileChecking'	=> 'filemtime',
		'assetsUrl'		=> NULL,
	];
	
	/**
	 * Boolean about initialized static props.
	 * @var bool
	 */
	protected static $initialized = FALSE;

	/**
	 * Application reference.
	 * @var \MvcCore\Application
	 */
	protected static $app = NULL;

	/**
	 * Application document root directory from request object.
	 * @var string|NULL
	 */
	protected static $docRoot = NULL;

	/**
	 * Application vendor package document root
	 * (completed by difference between request object 
	 * app root and request document root).
	 * @var string|NULL
	 */
	protected static $vendorDocRoot = NULL;

	/**
	 * Relative path to store joined and minified files
	 * from application root directory.
	 * @var string|NULL
	 */
	protected static $tmpDir = NULL;

	/**
	 * Base not compiled URL path from localhost if necessary
	 * @var string|NULL
	 */
	protected static $basePath = NULL;

	/**
	 * Request script name.
	 * @var string|NULL
	 */
	protected static $scriptName = NULL;

	/**
	 * If true, all messages are logged on hard drive,
	 * all exceptions are thrown.
	 * @var bool|NULL
	 */
	protected static $devMode = NULL;

	/**
	 * If true, all assets sources existences are checked
	 * @var bool|NULL
	 */
	protected static $fileChecking = NULL;

	/**
	 * If true, all temporary files are rendered
	 * @var bool|NULL
	 */
	protected static $fileRendering = NULL;

	/**
	 * If true, method AssetUrl in all css files returns
	 * to: 'index.php?controller=controller&action=asset&path=...'.
	 * @var bool|NULL
	 */
	protected static $assetsUrlCompletion = NULL;

	/**
	 * Hash completed as md5(json_encode()) from static::$globalOptions
	 * @var string
	 */
	protected static $systemConfigHash = '';

	/**
	 * Controller name in pascal case, slash and action name in pascal case.
	 * @var string
	 */
	protected static $ctrlActionKey = '';
	
	/**
	 * Supporting assets nonce attribute for CSP policy, completed only if necessary.
	 * @var \bool[]|\string[]|\NULL[]
	 */
	protected static $nonces = [NULL, NULL];


	/**
	 * Set global static options about minifying and joining together
	 * which can bee overwritten by single settings throw calling for
	 * example: append() method as another param.
	 *
	 * @see \MvcCore\Ext\Views\Helpers\Assets::$globalOptions
	 * @param  array $options whether or not to auto escape output
	 * @return void
	 */
	public static function SetGlobalOptions ($options = []) {
		static::$globalOptions = array_merge(static::$globalOptions, (array) $options);
		if (isset($options['assetsUrl']) && !is_null($options['assetsUrl'])) {
			static::$assetsUrlCompletion = (bool) $options['assetsUrl'];
		}
	}

	/**
	 * Strictly enable/disable assets URL completing in form
	 * '?controller=controller&action=asset&path=...'. Use this
	 * method only for cases, when you want to pack your application
	 * and you want to have all URL addresses to css/js/fonts and
	 * images directly to hard drive.
	 * @param  bool $enable
	 * @return void
	 */
	public static function SetAssetUrlCompletion ($enable = TRUE) {
		static::$assetsUrlCompletion = $enable;
	}

	/**
	 * Set global static $basePath to load assets from
	 * any static CDN domain or any other place.
	 * @param  string $basePath
	 * @return void
	 */
	public static function SetBasePath ($basePath) {
		static::$basePath = $basePath;
	}


	/**
	 * Insert a \MvcCore\View in each helper constructing
	 * @param  \MvcCore\View $view
	 * @return \MvcCore\Ext\Views\Helpers\AbstractHelper
	 */
	public function SetView (\MvcCore\IView $view) {
		parent::SetView($view);
		$req = $this->request;
		static::$ctrlActionKey = implode('/', [
			$req->GetControllerName(),
			$req->GetActionName()
		]);
		if (!static::$initialized) 
			static::initCommonProps($view, $req);
		return $this;
	}

	/**
	 * Render assets group.
	 * @return string
	 */
	public function __toString () {
		return $this->Render();
	}

	/**
	 * Completes font or image file URL inside CSS/JS file content.
	 *
	 * If application compile mode is in development state or packed in strict HDD mode,
	 * there is generated standard URL with \MvcCore\Request::$BasePath (current app location)
	 * plus called $path param. Because those application compile modes presume by default,
	 * that those files are placed beside php code on hard drive.
	 *
	 * If application compile mode is in php preserve package, php preserve HDD,
	 * php strict package or in single file URL mode, there is generated URL by \MvcCore
	 * in form: '?controller=controller&action=asset&path=...'.
	 *
	 * Feel free to change this css/js file URL completion to any custom way.
	 * There could be typically only: "$result = static::$basePath . $path;",
	 * but if you want to complete URL for assets on hard drive or
	 * to any other CDN place, use \MvcCore\Ext\Views\Helpers\Assets::SetBasePath($cdnBasePath);
	 *
	 * @param  string $path relative path from application document root with slash in begin
	 * @return string
	 */
	public function AssetUrl ($path = '') {
		$result = '';
		if (static::$assetsUrlCompletion) {
			// for static::$app->GetCompiled() equal to: 'PHAR', 'SFU', 'PHP_STRICT_PACKAGE', 'PHP_PRESERVE_PACKAGE', 'PHP_PRESERVE_HDD'
			$result = static::$scriptName . '?controller=controller&action=asset&path=' . $path;
		} else {
			// for static::$app->GetCompiled(), by default equal to: '' (development), 'PHP_STRICT_HDD'
			//$result = static::$basePath . $path;
			$result = static::REL_BASE_PATH_PLACEMENT . $path;
		}
		return $result;
	}

	/**
	 * Completes CSS or JS file url.
	 *
	 * If application compile mode is in development state or packed in strict HDD mode,
	 * there is generated standard URL with \MvcCore\Request->GetBasePath() (current app location)
	 * plus called $path param. Because those application compile modes presume by default,
	 * that those files are placed beside php code on hard drive.
	 *
	 * If application compile mode is in php preserve package, php preserve HDD,
	 * php strict package or in single file URL mode, there is generated URL by \MvcCore
	 * in form: 'index.php?controller=controller&action=asset&path=...'.
	 *
	 * Feel free to change this css/js file URL completion to any custom way.
	 * There could be typically only: "$result = static::$basePath . $path;",
	 * but if you want to complete URL for assets on hard drive or
	 * to any other CDN place, use \MvcCore\Ext\Views\Helpers\Assets::SetBasePath($cdnBasePath);
	 *
	 * @param  string $path relative path from application document root with slash in begin
	 * @return string
	 */
	public function CssJsFileUrl ($path = '') {
		$result = '';
		if (static::$assetsUrlCompletion) {
			// for static::$app->GetCompiled() equal to: 'PHAR', 'SFU', 'PHP_STRICT_PACKAGE', 'PHP_PRESERVE_PACKAGE', 'PHP_PRESERVE_HDD'
			$result = $this->view->AssetUrl($path);
		} else {
			// for static::$app->GetCompiled() equal to: '' (development), 'PHP_STRICT_HDD'
			$result = static::$basePath . $path;
		}
		return $result;
	}
	

	/**
	 * Load (or render) asset file, process any 
	 * custom replacements, call `$this->minify()` 
	 * if necessary and return result content.
	 * @throws \Exception
	 * @param  \stdClass  $item 
	 * @param  string     $srcFileFullPath 
	 * @param  string     $minify 
	 * @return string
	 */
	protected abstract function render2TmpGetPathExec (\stdClass $item, $srcFileFullPath, $minify);
	
	/**
	 * Get inline `<script>` or `<style>` nonce attribute 
	 * from CSP header if any. If no CSP header exists 
	 * or if CSP header exist with no nonce or `strict-dynamic`, 
	 * return an empty string.
	 * @param  bool   $js 
	 * @return string
	 */
	protected static function getNonce ($js = TRUE) {
		$nonceIndex = $js ? 1 : 0;
		if (static::$nonces[$nonceIndex] !== NULL) 
			return static::$nonces[$nonceIndex] === FALSE
				? ''
				: ' nonce="' . static::$nonces[$nonceIndex] . '"';
		$cspClassFullName = '\\MvcCore\\Ext\\Tools\\Csp';
		if (class_exists($cspClassFullName)) {
			/** @var \MvcCore\Ext\Tools\Csp $csp */
			$assetsNonce = FALSE;
			$csp = $cspClassFullName::GetInstance();
			$defaultScrNonce = $csp->IsAllowedNonce($cspClassFullName::FETCH_DEFAULT_SRC);
			if ((
				$js && ($csp->IsAllowedNonce($cspClassFullName::FETCH_SCRIPT_SRC) || $defaultScrNonce)
			) || (
				!$js && ($csp->IsAllowedNonce($cspClassFullName::FETCH_STYLE_SRC) || $defaultScrNonce)
			)) $assetsNonce = $csp->GetNonce();
			static::$nonces[$nonceIndex] = $assetsNonce;
		} else {
			$headerFound = false;
			foreach (headers_list() as $rawHeader) {
				if (!preg_match_all('#^Content\-Security\-Policy\s*:\s*(.*)$#i', trim($rawHeader), $matches)) continue;
				$rawHeaderValue = $matches[1][0];
				$sections = ['script'	=> FALSE, 'style' => FALSE, 'default' => FALSE];
				foreach ($sections as $sectionKey => $sectionValue) 
					if (preg_match_all("#{$sectionKey}\-src\s+(?:[^;]+\s)?\'nonce\-([^']+)\'#i", $rawHeaderValue, $sectionMatches)) 
						$sections[$sectionKey] = $sectionMatches[1][0];
				static::$nonces = [
					$sections['style']  ? $sections['style']  : $sections['default'],
					$sections['script'] ? $sections['script'] : $sections['default']
				];
				$headerFound = TRUE;
				break;
			}
			if (!$headerFound) 
				static::$nonces = [FALSE, FALSE];
		}
		return static::getNonce($js);
	}
	
	/**
	 * Initialize all static common properties 
	 * used in css and js helper.
	 * @param  \MvcCore\IView    $view 
	 * @param  \MvcCore\IRequest $req 
	 * @return void
	 */
	protected static function initCommonProps (\MvcCore\IView $view, \MvcCore\IRequest $req) {
		static::$initialized = TRUE;

		static::$app = $view->GetController()->GetApplication();

		static::$docRoot = $req->GetDocumentRoot();

		if (!static::$app->GetVendorAppDispatch()) {
			static::$vendorDocRoot = static::$docRoot;
		} else {
			$vendorAppRoot = static::$app->GetVendorAppRoot();
			$reqAppRoot = $req->GetAppRoot();
			if ($reqAppRoot === static::$docRoot) {
				static::$vendorDocRoot = $vendorAppRoot;
			} else {
				$docRootAddition = mb_substr(
					static::$docRoot, mb_strlen($reqAppRoot)
				);
				static::$vendorDocRoot = $vendorAppRoot . $docRootAddition;
			}
		}

		static::$basePath = $req->GetBasePath();
		static::$scriptName = ltrim($req->GetScriptName(), '/.');
		
		static::$devMode = static::$app->GetEnvironment()->IsDevelopment();

		$mvcCoreCompiledMode = static::$app->GetCompiled();

		// file checking is true only for classic development mode, not for single file mode
		if (!$mvcCoreCompiledMode) 
			static::$fileChecking = TRUE;

		// file rendering is true for classic development state, SFU app mode
		if (!$mvcCoreCompiledMode || $mvcCoreCompiledMode == 'SFU') {
			static::$fileRendering = TRUE;
		}

		// set URL addresses completion to true by default for:
		// - all package modes outside PHP_STRICT_HDD and outside development
		if ($mvcCoreCompiledMode && $mvcCoreCompiledMode != 'PHP_STRICT_HDD') {
			static::$assetsUrlCompletion = TRUE;
		} else {
			static::$assetsUrlCompletion = FALSE;
		}
		
		static::$systemConfigHash = hash("crc32b", serialize(static::$globalOptions));
	}

	/**
	 * Returns file modification imprint by global settings -
	 * by `md5_file()` or by `filemtime()` - always as a string
	 * @param  string $fullPath
	 * @return string
	 */
	protected static function getFileImprint ($fullPath) {
		$fileChecking = static::$globalOptions['fileChecking'];
		if ($fileChecking == 'filemtime') {
			return filemtime($fullPath);
		} else {
			return (string) call_user_func($fileChecking, $fullPath);
		}
	}


	/**
	 * Get actually dispatched controller/action group name.
	 * @return \stdClass[]
	 */
	protected function & getGroupStore () {
		$ctrlActionKey = $this->getCtrlActionKey();
		$name = $this->currentGroupName;
		if (!isset($this->groupStore[$ctrlActionKey]))
			$this->groupStore[$ctrlActionKey] = [];
		if (!isset($this->groupStore[$ctrlActionKey][$name]))
			$this->groupStore[$ctrlActionKey][$name] = [];
		return $this->groupStore[$ctrlActionKey][$name];
	}

	/**
	 * Return unique hash by params.
	 * @param array $args 
	 */
	protected function getGroupStoreReverseKey (array $args) {
		return hash("crc32b", serialize($args));
	}

	/**
	 * Set actually dispatched controller/action group name.
	 * @param  \stdClass[] $items
	 * @return \MvcCore\Ext\Views\Helpers\CssHelper
	 */
	protected function setGroupStore ($items) {
		$ctrlActionKey = $this->getCtrlActionKey();
		$name = $this->currentGroupName;
		if (!isset($this->groupStore[$ctrlActionKey]))
			$this->groupStore[$ctrlActionKey] = [];
		if (!isset($this->groupStore[$ctrlActionKey][$name]))
			$this->groupStore[$ctrlActionKey][$name] = [];
		$this->groupStore[$ctrlActionKey][$name] = $items;
		return $this;
	}

	/**
	 * Unset record under given numeric index in 
	 * actually dispatched controller/action group name.
	 * @param  int  $index
	 * @return bool
	 */
	protected function unsetGroupStore ($index) {
		$ctrlActionKey = $this->getCtrlActionKey();
		$name = $this->currentGroupName;
		if (!isset($this->groupStore[$ctrlActionKey]))
			$this->groupStore[$ctrlActionKey] = [];
		if (!isset($this->groupStore[$ctrlActionKey][$name]))
			$this->groupStore[$ctrlActionKey][$name] = [];
		if (array_key_exists($index, $this->groupStore[$ctrlActionKey][$name])) {
			unset($this->groupStore[$ctrlActionKey][$name][$index]);
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Set up reverse group store to detect 
	 * if style/script is already contained.
	 * @param  \mixed[] $args
	 * @return \MvcCore\Ext\Views\Helpers\Assets
	 */
	protected function setUpGroupStoreReverseKey ($args) {
		$reverseKey = md5(serialize($args));
		$this->groupStoreReverseKeys[$reverseKey] = TRUE;
		return $this;
	}

	/**
	 * Get request params controller/action combination string.
	 * @return string
	 */
	protected function getCtrlActionKey () {
		return static::$ctrlActionKey;
	}

	/**
	 * Look for every item to render if there is any 
	 * 'notMin' record to render item separately.
	 * @param  \stdClass[]   $items
	 * @return \stdClass[][] $itemsToRenderMinimized $itemsToRenderSeparately
	 */
	protected function separateItemsToMinifiedGroups ($items) {
		$itemsToRenderMinimized = [];
		$itemsToRenderSeparately = []; // some configurations is not possible to render together and minimized
		// go for every item to complete existing combinations in attributes
		foreach ($items as & $item) {
			$itemArr = array_merge([], (array) $item);
			unset($itemArr['path'], $itemArr['fullPath'], $itemArr['vendor']);
			if (isset($itemArr['render'])) 
				unset($itemArr['render']);
			if (isset($itemArr['external'])) 
				unset($itemArr['external']);
			$renderArrayKey = md5(json_encode($itemArr));
			if ($itemArr['notMin']) {
				if (isset($itemsToRenderSeparately[$renderArrayKey])) {
					$itemsToRenderSeparately[$renderArrayKey][] = $item;
				} else {
					$itemsToRenderSeparately[$renderArrayKey] = [$item];
				}
			} else {
				if (isset($itemsToRenderMinimized[$renderArrayKey])) {
					$itemsToRenderMinimized[$renderArrayKey][] = $item;
				} else {
					$itemsToRenderMinimized[$renderArrayKey] = [$item];
				}
			}
		}
		return [
			$itemsToRenderMinimized,
			$itemsToRenderSeparately,
		];
	}

	/**
	 * Render js file by path and store result 
	 * in tmp directory and return new src value.
	 * @param  \stdClass $item
	 * @param  bool      $minify
	 * @param  string    $type
	 * @return string
	 */
	protected function render2TmpGetPath ($item, $minify, $type) {
		$tmpFileName = $this->getTmpFileName($item->fullPath, $item->path, 'r');
		$srcFileFullPath = $item->fullPath;
		$tmpFileFullPath = $this->getTmpDir() . $tmpFileName;
		if (static::$fileRendering) {
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
					$fileContent = $this->render2TmpGetPathExec(
						$item, $srcFileFullPath, $minify	
					);
					$this->saveFileContent($tmpFileFullPath, $fileContent);
					$this->log(ucfirst($type) . " file rendered ('{$tmpFileFullPath}').", 'debug');
				}
			}
		}
		$tmpPath = mb_substr($tmpFileFullPath, mb_strlen(static::$docRoot));
		return $tmpPath;
	}

	/**
	 * Add to href URL file modification param by original file.
	 * @param  string $url
	 * @param  string $fullPath
	 * @return string
	 */
	protected function addFileModImprint2HrefUrl ($url, $fullPath) {
		$questionMarkPos = strpos($url, '?');
		$separator = ($questionMarkPos === FALSE) ? '?' : '&';
		if (static::$globalOptions['fileChecking'] == 'filemtime') {
			$fileMTime = static::getFileImprint($fullPath);
			$url .= $separator . '_fmt=' . date(
				static::FILE_MODIFICATION_DATE_FORMAT,
				(int)$fileMTime
			);
		} else {
			$url .= $separator . '_md5=' . static::getFileImprint($fullPath);
		}
		return $url;
	}

	/**
	 * Get indent string.
	 * @param  string|int $indent
	 * @return string
	 */
	protected function getIndentString ($indent = 0) {
		$indentStr = '';
		if (is_numeric($indent)) {
			$indInt = intval($indent);
			if ($indInt > 0) {
				$i = 0;
				while ($i < $indInt) {
					$indentStr .= "\t";
					$i += 1;
				}
			}
		} else if (is_string($indent)) {
			$indentStr = $indent;
		}
		return $indentStr;
	}

	/**
	 * Return filename in assets tmp dir.
	 * @param  string $path   Originaly defined path.
	 * @param  string $prefix Prefix in tmp dir.
	 * @return string
	 */
	protected function getTmpFileName ($fullPathOrUrl, $path, $prefix) {
		$path = mb_strpos($path, '~/') === 0
			? mb_substr($path, 1)
			: $path;
		$hash = hash("crc32b", serialize([
			static::$systemConfigHash,
			$fullPathOrUrl
		]));
		return '/' . implode('_', [
			$prefix,
			$hash,
			trim(str_replace('/', '_', $path), "_"),
		]);
	}
	
	/**
	 * Move file from any vendor assets directory into 
	 * assets tmp dir. Return new asset path in tmp directory.
	 * @param  string $path 
	 * @param  string $fullPath 
	 * @param  string $type 
	 * @return [bool, string]
	 */
	protected function move2TmpGetPath ($path, $srcFileFullPath, $type) {
		$copied = FALSE;
		$tmpFileName = $this->getTmpFileName($srcFileFullPath, $path, 'm');
		$tmpFileFullPath = $this->getTmpDir() . $tmpFileName;
		if (static::$fileRendering) {
			if (file_exists($srcFileFullPath)) {
				$srcFileModDate = filemtime($srcFileFullPath);
			} else {
				$srcFileModDate = 1;
			}
			$tmpFileExists = file_exists($tmpFileFullPath);
			if ($tmpFileExists) {
				$tmpFileModDate = filemtime($tmpFileFullPath);
			} else {
				$tmpFileModDate = 0;
			}
			if ($srcFileModDate !== FALSE && $tmpFileModDate !== FALSE) {
				if ($srcFileModDate > $tmpFileModDate) {
					if ($tmpFileExists) {
						$removed = @unlink($tmpFileFullPath);
						if (!$removed) $this->exception(
							"Not possible to remove previous "
							."tmp file to move {$type}: `{$path}`."
						);
					}
					$copied = copy($srcFileFullPath, $tmpFileFullPath);
					@chmod($tmpFileFullPath, 0554);
					if (!$copied) $this->exception(
						"Not possible to copy {$type}: `{$path}` into tmp file."
					);
					$this->log("File copied: `{$tmpFileFullPath}`.", 'debug');
				}
			}
		}
		return [
			$copied,
			mb_substr($tmpFileFullPath, mb_strlen(static::$docRoot))
		];
	}

	/**
	 * Get significant file sub-path from full path.
	 * @param  string $path
	 * @param  int    $maxPathSegments
	 * @return string
	 */
	protected function getSignificantPathPartFromFullPath ($absPath, $maxPathSegments = 5) {
		$pathParts = explode('/', str_replace('\\', '/', $absPath));
		$pathPartsCount = count($pathParts);
		$partsCount = min($pathPartsCount, $maxPathSegments);
		$parts = [];
		for ($i = 0; $i < $partsCount; $i++)
			$parts[] = $pathParts[$pathPartsCount - 1 - $i];
		$parts[] = '';
		return implode('/', array_reverse($parts));
	}

	/**
	 * Return and store application document root 
	 * from controller view request object.
	 * @throws \Exception
	 * @return string
	 */
	protected function getTmpDir () {
		if (!static::$tmpDir) {
			$tmpDir = static::$globalOptions['tmpDir'];
			$tmpDir = mb_strpos($tmpDir, '~/') === 0
				? static::$docRoot . mb_substr($tmpDir, 1)
				: $tmpDir;
			if (static::$fileChecking) {
				if (!is_dir($tmpDir)) 
					@mkdir($tmpDir, 0754);
				if (is_dir($tmpDir) && !is_writable($tmpDir)) 
					@chmod($tmpDir, 0754);
			}
			static::$tmpDir = $tmpDir;
		}
		return static::$tmpDir;
	}

	/**
	 * Save atomically file content in full path 
	 * by 1 MB to not overflow any memory limits.
	 * @param  string $fullPath
	 * @param  string $fileContent
	 * @return void
	 */
	protected function saveFileContent ($fullPath = '', $fileContent = '') {
		$toolClass = static::$app->GetToolClass();
		$toolClass::AtomicWrite($fullPath, $fileContent);
		@chmod($fullPath, 0554);
	}

	/**
	 * Log any render messages with optional log file name.
	 * @param  string $msg
	 * @param  string $logType
	 * @return void
	 */
	protected function log ($msg, $logType = 'debug') {
		if (static::$devMode)
			\MvcCore\Debug::Log($msg, $logType);
	}

	/**
	 * Throw exception with given message with actual helper class name before
	 * @throws \Exception text by given message
	 * @param  string     $msg
	 * @return void
	 */
	protected function exception ($msg) {
		if (static::$devMode)
			throw new \Exception('[' . get_class($this) . '] ' . $msg);
	}

	/**
	 * Throw exception with given message 
	 * with actual helper class name before.
	 * @param  string $msg
	 * @return void
	 */
	protected function warning ($msg) {
		if (static::$devMode)
			\MvcCore\Debug::BarDump('[' . get_class($this) . '] ' . $msg, \MvcCore\IDebug::DEBUG);
	}

	/**
	 * Thrown given exception by configuration.
	 * @param  \Throwable $e
	 * @return void
	 */
	protected function exceptionHandler ($e) {
		if (static::$devMode)
			throw $e;
	}

	/**
	 * Complete items group tmp directory file 
	 * name by group source files info.
	 * @param  array  $filesGroupInfo
	 * @param  bool   $minify
	 * @return string
	 */
	protected function getTmpFileFullPathByPartFilesInfo ($filesGroupInfo, $minify, $extension) {
		return implode('', [
			$this->getTmpDir(),
			'/' . ($minify ? 'minified' : 'rendered') . '_' . $extension . '_',
			md5(implode(',', $filesGroupInfo) . '_' . $minify),
			'.' . $extension
		]);
	}
}
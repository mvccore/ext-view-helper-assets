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
class Assets extends \MvcCore\Ext\Views\Helpers\AbstractHelper {

	/**
	 * MvcCore Extension - View Helper - Assets - version:
	 * Comparison by PHP function version_compare();
	 * @see http://php.net/manual/en/function.version-compare.php
	 */
	const VERSION = '5.0.3';

	/**
	 * Default link group name
	 * @const string
	 */
	const GROUP_NAME_DEFAULT   = 'default';

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
	 * Called $_linksGroupContainer index throw helper function Css() or Js()
	 * @var string
	 */
	protected $actualGroupName = '';

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
		'tmpDir'		=> '/Var/Tmp',
		'fileChecking'	=> 'filemtime',
		'assetsUrl'		=> NULL,
	];

	/**
	 * Application root directory from request object
	 * @var string
	 */
	protected static $documentRoot = NULL;

	/**
	 * Relative path to store joined and minified files
	 * from application root directory.
	 * @var string
	 */
	protected static $tmpDir = NULL;

	/**
	 * Base not compiled URL path from localhost if necessary
	 * @var string
	 */
	protected static $basePath = NULL;

	/**
	 * Request script name.
	 * @var string
	 */
	protected static $scriptName = NULL;

	/**
	 * If true, all messages are logged on hard drive,
	 * all exceptions are thrown.
	 * @var boolean
	 */
	protected static $loggingAndExceptions = TRUE;

	/**
	 * If true, all assets sources existences are checked
	 * @var boolean
	 */
	protected static $fileChecking = FALSE;

	/**
	 * If true, all temporary files are rendered
	 * @var boolean
	 */
	protected static $fileRendering = FALSE;

	/**
	 * If true, method AssetUrl in all css files returns
	 * to: 'index.php?controller=controller&action=asset&path=...'.
	 * @var boolean
	 */
	protected static $assetsUrlCompletion = NULL;

	/**
	 * Hash completed as md5(json_encode()) from self::$globalOptions
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
	 * Insert a \MvcCore\View in each helper constructing
	 * @param \MvcCore\View $view
	 * @return \MvcCore\Ext\Views\Helpers\AbstractHelper
	 */
	public function SetView (\MvcCore\IView $view) {
		parent::SetView($view);

		if (self::$documentRoot === NULL) self::$documentRoot = $this->request->GetDocumentRoot();
		if (self::$basePath === NULL) self::$basePath = $this->request->GetBasePath();
		if (self::$scriptName === NULL) self::$scriptName = ltrim($this->request->GetScriptName(), '/.');
		$app = $view->GetController()->GetApplication();
		$environment =$app->GetEnvironment();
		self::$loggingAndExceptions = $environment->IsDevelopment();
		$mvcCoreCompiledMode = $app->GetCompiled();

		self::$ctrlActionKey = $this->request->GetControllerName() . '/' . $this->request->GetActionName();

		// file checking is true only for classic development mode, not for single file mode
		if (!$mvcCoreCompiledMode) self::$fileChecking = TRUE;

		// file rendering is true for classic development state, SFU app mode
		if (!$mvcCoreCompiledMode || $mvcCoreCompiledMode == 'SFU') {
			self::$fileRendering = TRUE;
		}

		if (is_null(self::$assetsUrlCompletion)) {
			// set URL addresses completion to true by default for:
			// - all package modes outside PHP_STRICT_HDD and outside development
			if ($mvcCoreCompiledMode && $mvcCoreCompiledMode != 'PHP_STRICT_HDD') {
				self::$assetsUrlCompletion = TRUE;
			} else {
				self::$assetsUrlCompletion = FALSE;
			}
		}

		self::$systemConfigHash = md5(json_encode(self::$globalOptions));

		return $this;
	}

	/**
	 * Set global static options about minifying and joining together
	 * which can bee overwritten by single settings throw calling for
	 * example: append() method as another param.
	 *
	 * @see \MvcCore\Ext\Views\Helpers\Assets::$globalOptions
	 * @param array $options whether or not to auto escape output
	 * @return void
	 */
	public static function SetGlobalOptions ($options = []) {
		self::$globalOptions = array_merge(self::$globalOptions, (array) $options);
		if (isset($options['assetsUrl']) && !is_null($options['assetsUrl'])) {
			self::$assetsUrlCompletion = (bool) $options['assetsUrl'];
		}
	}

	/**
	 * Strictly enable/disable assets URL completing in form
	 * '?controller=controller&action=asset&path=...'. Use this
	 * method only for cases, when you want to pack your application
	 * and you want to have all URL addresses to css/js/fonts and
	 * images directly to hard drive.
	 * @param bool $enable
	 * @return void
	 */
	public static function SetAssetUrlCompletion ($enable = TRUE) {
		self::$assetsUrlCompletion = $enable;
	}

	/**
	 * Set global static $basePath to load assets from
	 * any static CDN domain or any other place.
	 * @param string $basePath
	 * @return void
	 */
	public static function SetBasePath ($basePath) {
		self::$basePath = $basePath;
	}

	/**
	 * Returns file modification imprint by global settings -
	 * by `md5_file()` or by `filemtime()` - always as a string
	 * @param string $fullPath
	 * @return string
	 */
	protected static function getFileImprint ($fullPath) {
		$fileChecking = self::$globalOptions['fileChecking'];
		if ($fileChecking == 'filemtime') {
			return filemtime($fullPath);
		} else {
			return (string) call_user_func($fileChecking, $fullPath);
		}
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
	 * There could be typically only: "$result = self::$basePath . $path;",
	 * but if you want to complete URL for assets on hard drive or
	 * to any other CDN place, use \MvcCore\Ext\Views\Helpers\Assets::SetBasePath($cdnBasePath);
	 *
	 * @param  string $path relative path from application document root with slash in begin
	 * @return string
	 */
	public function AssetUrl ($path = '') {
		$result = '';
		if (self::$assetsUrlCompletion) {
			// for \MvcCore\Application::GetInstance()->GetCompiled() equal to: 'PHAR', 'SFU', 'PHP_STRICT_PACKAGE', 'PHP_PRESERVE_PACKAGE', 'PHP_PRESERVE_HDD'
			$result = self::$scriptName . '?controller=controller&action=asset&path=' . $path;
		} else {
			// for \MvcCore\Application::GetInstance()->GetCompiled(), by default equal to: '' (development), 'PHP_STRICT_HDD'
			//$result = self::$basePath . $path;
			$result = '__RELATIVE_BASE_PATH__' . $path;
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
	 * There could be typically only: "$result = self::$basePath . $path;",
	 * but if you want to complete URL for assets on hard drive or
	 * to any other CDN place, use \MvcCore\Ext\Views\Helpers\Assets::SetBasePath($cdnBasePath);
	 *
	 * @param  string $path relative path from application document root with slash in begin
	 * @return string
	 */
	public function CssJsFileUrl ($path = '') {
		$result = '';
		if (self::$assetsUrlCompletion) {
			// for \MvcCore\Application::GetInstance()->GetCompiled() equal to: 'PHAR', 'SFU', 'PHP_STRICT_PACKAGE', 'PHP_PRESERVE_PACKAGE', 'PHP_PRESERVE_HDD'
			$result = $this->view->AssetUrl($path);
		} else {
			// for \MvcCore\Application::GetInstance()->GetCompiled() equal to: '' (development), 'PHP_STRICT_HDD'
			$result = self::$basePath . $path;
		}
		return $result;
	}

	/**
	 * Get request params controller/action combination string
	 * @return string
	 */
	protected function getCtrlActionKey () {
		return self::$ctrlActionKey;
	}

	/**
	 * Look for every item to render if there is any 'doNotMinify' record to render item separately
	 * @param array $items
	 * @return array[] $itemsToRenderMinimized $itemsToRenderSeparately
	 */
	protected function filterItemsForNotPossibleMinifiedAndPossibleMinifiedItems ($items) {
		$itemsToRenderMinimized = [];
		$itemsToRenderSeparately = []; // some configurations is not possible to render together and minimized
		// go for every item to complete existing combinations in attributes
		foreach ($items as & $item) {
			$itemArr = array_merge((array) $item, []);
			unset($itemArr['path']);
			if (isset($itemArr['render'])) unset($itemArr['render']);
			if (isset($itemArr['external'])) unset($itemArr['external']);
			$renderArrayKey = md5(json_encode($itemArr));
			if ($itemArr['doNotMinify']) {
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
	 * Add to href URL file modification param by original file
	 * @param  string $url
	 * @param  string $path
	 * @return string
	 */
	protected function addFileModificationImprintToHrefUrl ($url, $path) {
		$questionMarkPos = strpos($url, '?');
		$separator = ($questionMarkPos === FALSE) ? '?' : '&';
		$strippedUrl = $questionMarkPos !== FALSE ? substr($url, $questionMarkPos) : $url ;
		$srcPath = $this->getAppRoot() . substr($strippedUrl, strlen(self::$basePath));
		if (self::$globalOptions['fileChecking'] == 'filemtime') {
			$fileMTime = self::getFileImprint($srcPath);
			$url .= $separator . '_fmt=' . date(
				self::FILE_MODIFICATION_DATE_FORMAT,
				(int)$fileMTime
			);
		} else {
			$url .= $separator . '_md5=' . self::getFileImprint($srcPath);
		}
		return $url;
	}

	/**
	 * Get indent string
	 * @param string|int $indent
	 * @return string
	 */
	protected function getIndentString($indent = 0) {
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
	 * Return and store application document root from controller view request object
	 * @return string
	 */
	protected function getAppRoot() {
		return self::$documentRoot;
	}

	/**
	 * Return and store application document root from controller view request object
	 * @throws \Exception
	 * @return string
	 */
	protected function getTmpDir() {
		if (!self::$tmpDir) {
			$tmpDir = $this->getAppRoot() . self::$globalOptions['tmpDir'];
			if (!\MvcCore\Application::GetInstance()->GetCompiled()) {
				if (!is_dir($tmpDir)) 
					@mkdir($tmpDir, 0777, TRUE);
				if (is_dir($tmpDir) && !is_writable($tmpDir)) 
					@chmod($tmpDir, 0777);
			}
			self::$tmpDir = $tmpDir;
		}
		return self::$tmpDir;
	}

	/**
	 * Save atomically file content in full path by 1 MB to not overflow any memory limits
	 * @param string $fullPath
	 * @param string $fileContent
	 * @return void
	 */
	protected function saveFileContent ($fullPath = '', & $fileContent = '') {
		$toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
		$toolClass::AtomicWrite($fullPath, $fileContent);
		@chmod($fullPath, 0766);
	}

	/**
	 * Log any render messages with optional log file name
	 * @param string $msg
	 * @param string $logType
	 * @return void
	 */
	protected function log ($msg = '', $logType = 'debug') {
		if (self::$loggingAndExceptions) {
			\MvcCore\Debug::Log($msg, $logType);
		}
	}

	/**
	 * Throw exception with given message with actual helper class name before
	 * @param string $msg
	 * @throws \Exception text by given message
	 * @return void
	 */
	protected function exception ($msg) {
		if (self::$loggingAndExceptions) {
			throw new \Exception('[' . get_class($this) . '] ' . $msg);
		}
	}

	/**
	 * Throw exception with given message with actual helper class name before
	 * @param string $msg
	 * @return void
	 */
	protected function warning ($msg) {
		if (self::$loggingAndExceptions) {
			\MvcCore\Debug::BarDump('[' . get_class($this) . '] ' . $msg, \MvcCore\IDebug::DEBUG);
		}
	}

	/**
	 * Render given exception
	 * @param \Throwable $e
	 * @return void
	 */
	protected function exceptionHandler ($e) {
		if (self::$loggingAndExceptions) {
			\MvcCore\Debug::Exception($e);
		}
	}

	/**
	 * Complete items group tmp directory file name by group source files info
	 * @param array   $filesGroupInfo
	 * @param boolean $minify
	 * @return string
	 */
	protected function getTmpFileFullPathByPartFilesInfo ($filesGroupInfo = [], $minify = FALSE, $extension = '') {
		return implode('', [
			$this->getTmpDir(),
			'/' . ($minify ? 'minified' : 'rendered') . '_' . $extension . '_',
			md5(implode(',', $filesGroupInfo) . '_' . $minify),
			'.' . $extension
		]);
	}

	/**
	 * Get inline `<script>` or `<style>` nonce attribute from CSP header if any.
	 * If no CSP header exists or if CSP header exist with no nonce or `strict-dynamic`, 
	 * return an empty string.
	 * @param  bool $js 
	 * @return string
	 */
	protected static function getNonce ($js = TRUE) {
		$nonceIndex = $js ? 1 : 0;
		if (self::$nonces[$nonceIndex] !== NULL) 
			return self::$nonces[$nonceIndex] === FALSE
				? ''
				: ' nonce="' . self::$nonces[$nonceIndex] . '"';
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
			self::$nonces[$nonceIndex] = $assetsNonce;
		} else {
			$headerFound = false;
			foreach (headers_list() as $rawHeader) {
				if (!preg_match_all('#^Content\-Security\-Policy\s*:\s*(.*)$#i', trim($rawHeader), $matches)) continue;
				$rawHeaderValue = $matches[1][0];
				$sections = ['script'	=> FALSE, 'style' => FALSE, 'default' => FALSE];
				foreach ($sections as $sectionKey => $sectionValue) 
					if (preg_match_all("#{$sectionKey}\-src\s+(?:[^;]+\s)?\'nonce\-([^']+)\'#i", $rawHeaderValue, $sectionMatches)) 
						$sections[$sectionKey] = $sectionMatches[1][0];
				self::$nonces = [
					$sections['style']  ? $sections['style']  : $sections['default'],
					$sections['script'] ? $sections['script'] : $sections['default']
				];
				$headerFound = TRUE;
				break;
			}
			if (!$headerFound) 
				self::$nonces = [FALSE, FALSE];
		}
		return static::getNonce($js);
	}
}

<?php
// CSS Routes:
//$F3->route('GET|HEAD '.$base.'/css/@lastmod', 'CssConcat->process_route');
//$F3->route('GET|HEAD '.$base.'/css/@lastmod/@appName', 'CssConcat->process_route');
//$F3->route('GET|HEAD '.$base.'/css/@lastmod/@appName/@campaignId', 'CssConcat->process_route');

define('DEBUG', FALSE);
define('SITE_PATH', dirname(__DIR__));
define('STYLE_PATH', SITE_PATH.'/css/');
define('DEFAULT_VERSION_NUM', 1);

class CssConcat {
	
	// This file will split apart the query string by pluses (+) to build css
	// css/?fonts+backgrounds will load fonts.css & backgrounds.css & any assoc css from Styles class
	//
	// Cache mechanism:
	// file is loaded from css/cache/fonts_backgrounds_<lastmod>.css if it exists
	//
	public function process_route($q_str, $folder=null)
	{

		$q_str       = str_replace(' ','+', $q_str);
		$expires_in  = 31536000; // one year since we're cache-busting;

		// Build headers:
		header("Content-type: text/css");
		header('Cache-Control: public, max-age='.$expires_in);
		header('Pragma: cache');
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires_in) . ' GMT');

		// Write everything out
		$output = self::concatCSS( $q_str, $folder );

		if( empty($output) )
		{
			header('HTTP/1.0 404 Not Found');
			echo "<h1>Page Not Found</h1>";
			exit;
		}

		echo $output;
	}



	// Used to concat all CSS together based on query string e.g. main+filename
	// used mainly by /css/?file+otherfile routes
	public static function concatCSS( $query, $folder = null )
	{

		$style_path		= isset($folder) ? STYLE_PATH.$folder.'/' : STYLE_PATH;
		$cache_path		= $style_path.'cache/';

		$output 		= '';
		$lastmod 		= 0;
		$filePaths 		= [];
		$fileNames		= [];
		
		list( $lastmod, $filePaths, $fileNames ) = self::getStyleFileInfo( $query, $folder );

		// Serve up the cached file if it exists:
		$cacheFile = $cache_path. implode('_', $fileNames) .'_'.$lastmod.'.css';
		if( !DEBUG && file_exists($cacheFile) ){
			return @file_get_contents($cacheFile);
		}

		// Build output if no cache file exists:
		foreach( $filePaths as $fn ){

			// Load the file:
			if( file_exists($fn) ){
				$ftime 	 = filemtime($fn);
				if( $ftime > $lastmod ) $lastmod = $ftime;
				$output .= @file_get_contents($fn);
			}

		}

		// Remove whitespace:
		if( !DEBUG && !empty($output) ){

			$output = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $output);
			$output = str_replace(': ', ':', $output);
			$output = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $output);
			$output = "/* Last Mod: ".date("F d Y H:i:s", $lastmod)." */\n".$output;

			// Write a new cache file for the next request:
			@file_put_contents($cacheFile, $output);

		}

		// If nothing to write out...
		if( empty($output) ){
			return null;
		}

		return $output;

	}

	public static function getStyleFileInfo( $query, $folder = null )
	{
		
		$lastmod 		= 0;
		$filePaths 		= [];
		$fileNames		= [];
		$style_path		= isset($folder) ? STYLE_PATH.$folder.'/' : STYLE_PATH;
		$files	 		= explode('+', $query);
		
		// Check if the CSS files exist:
		foreach( $files as $filename ){
			if( file_exists($style_path.$filename.'.css') ) {

				$cssfile	 = $style_path.$filename.'.css';
				$ftime 	 	 = filemtime( $cssfile );
				if( $ftime > $lastmod ) $lastmod = $ftime;
				$filePaths[] = $cssfile;
				$fileNames[] = !is_null($folder) ? $folder.'.'.str_replace('/','-',$filename) : str_replace('/','-',$filename);

			}
		}

		return [$lastmod, $filePaths, $fileNames];

	}

}

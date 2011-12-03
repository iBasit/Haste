<?php
/*
 * plan note:
 * 		scan and make tree of all files and folders
 * 		save new and make changes to old
 * 		fetch the new updates from database
 * 		build the files, (remember to add css/js to html files and also feature for grouping the file as one file.
 */
class resources
{
	private $_cache_dir_name = '_c';
	private $_dirTree = array();

	private $_paths = array(
		'html' => array(
			'dir' => '',
			'gzip' => false,
			'dir_cache' => true // cache files in there own directory names, but in _c (cache dir)
		),
		'js' => array(
			'dir' => '',
			'url_path' => ''
		),
		'css' => array(
			'dir' => '',
			'url_path' => '',
			'filter' => '.swf,.htaccess'
		)
	);


	public function setPath ($paths)
	{
		foreach ($paths as $key => $array)
		{
			$array2 = is_array($this->_paths["{$key}"]) ? $this->_paths["{$key}"] : array();
			$paths["{$key}"] = array_merge($array2, $array);
		}
		$this->_paths = $paths;
	}

	public function getPath ()
	{
		return $this->_paths;
	}

	public function scan ()
	{
		$paths = $this->_paths;

		foreach ($paths as $key => $scan_dir)
		{
			$this->_dirTree["{$key}"] = $this->_scan($scan_dir);
		}

		return $this->_dirTree;
	}

	protected function _scan (Array $path_array, $directory = NULL)
	{
		$filter = null;
		if (!$directory)
			$directory = $path_array['dir'];

		if (!isset($path_array['subDir']))
			$path_array['subDir'] = '';

		if (!empty($path_array['filter']))
		{
			$filter = explode(',', $path_array['filter']);

			if (!is_array($filter))
				$filter = array($path_array['filter']);
		}
		else
			$filter = array(); // empty error, to shut the error

		if (substr($directory, -1) == '/')
		{
			$directory = substr($directory, 0, -1);
		}

		if (!file_exists($directory) || !is_dir($directory))
		{
			return FALSE;
		}
		elseif (is_readable($directory))
		{
			$directory_tree = array();
			$directory_list = opendir($directory);

			while ($file = readdir($directory_list))
			{
				$path = realpath($directory . '/' . $file);
				$fileName = basename($file);

				if ($file == '.'
				|| $file == '..'
				|| in_array($fileName, $filter)
				|| !is_readable($path)
				|| ($fileName == $this->_cache_dir_name and is_dir($path)))
				{
					continue;
				}

				if (is_dir($path))
				{
					$sub_path_array = $path_array;
					$sub_path_array['subDir'] .= $fileName.'/';
					$directory_tree[] = array (
						'path' => $path,
						'subDir' => $path_array['subDir'],
						'name' => $fileName,
						'type' => 'dir',
						'tree' => $this->_scan($sub_path_array, $path)
					);
				}
				elseif (is_file($path))
				{
					$extension = end(explode('.', $fileName));

					if (in_array('.'.$extension, $filter))
					{
						continue;
					}

					$cacheFile = $path_array['dir'].'/'.$this->_cache_dir_name.'/';
					if (isset($path_array['dir_cache']) && $path_array['dir_cache'])
						$cacheFile .= $path_array['subDir'];
					$cacheFile .= $fileName;
					$cacheFile = realpath($cacheFile); // if dont exist, it will empty it.

					$url = null;
					if (!empty($path_array['url_path']))
						$url = $path_array['url_path'].'/'.$path_array['subDir'].$fileName;

					$directory_tree[] = array (
						'path' => realpath($path),
						'subDir' => $path_array['subDir'],
						'local_url' => $url,
						'name' => $fileName,
						'type' => $extension,
						'size' => filesize($path),
						'last_modified' => filemtime($path),
					 	'cache_modified' => $cacheFile ? filemtime($cacheFile) : 0
					);
				}
			}
			closedir($directory_list);
			return $directory_tree;
		}
		else
		{
			return FALSE;
		}
	}

	private function _setDB ($for = 'resource')
	{
		return Database::Set('resources', $table_pk);
	}

	public function fetch ()
	{
		;//TODO
	}

	public function build ()
	{


		//$this->_compile($this->_dirTree);
	}

	protected function _compile ($dirTree)
	{
		foreach ($dirTree as $file)
		{
			if ($file['type'] == 'dir')
			{
				if (!is_dir( $this->_cacheDir.'/'.$file['subDir']))
					mkdir($this->_cacheDir.'/'.$file['subDir']);

				// create dir, if dont exist
				$this->_compile($file['tree']);
			}
			else
			{
				if ($file['isCache'] == 1)
					continue;
				else
				{
					$this->gzip_save($file['path'], $this->_cacheDir.'/'.$file['subDir']);
				}
			}
		}
	}

	public function gzip_save ($local_file, $save_dir, $gz_encoding_mode = 9)
	{
		$file_name = basename($local_file);
		$file_content = file_get_contents($local_file);

		$subdirectories = explode('/', $local_file);
		$extension = end(explode('.', end($subdirectories)));

		if ($extension == 'php')
		{
			$file_content = $this->html_compress($file_content);
			$file_content = $this->php_compress($file_content);
		}

		$file_content = gzencode($file_content, $gz_encoding_mode);
		file_put_contents($save_dir.'/'.$file_name , $file_content);
	}

	/*
	 * compress js files from google compiler
	 * @param $js_file
	 * @param $compile_mode  s (simple), a (advanced) or w (whitespaced)
	 */
	public function js_compress ($js_file, $compile_mode = 's')
	{
		$c = new PhpClosure();

		if (!is_array($js_file))
		{
			$c->add($js_file);
		}
		else
		{
		 	foreach ($js_file as $file)
		 	{
		 		$c->add($file);
		 	}
		}

		switch ($compile_mode)
		{
			case 's':
				$c->simpleMode();
			break;
			case 'a':
				$c->advancedMode();
			break;
			case 'w':
				$c->whitespaceOnly();
			break;
		}

		return $c->useClosureLibrary()->_compile();
	}

	public function css_compress ($css)
	{
		$css = preg_replace('!//[^\n\r]+!', '', $css); //comments
		$css = preg_replace('/[\r\n\t\s]+/s', ' ', $css); //new lines, multiple spaces/tabs/newlines
		$css = preg_replace('#/\*.*?\*/#', '', $css); //comments
		$css = preg_replace('/[\s]*([\{\},;:])[\s]*/', '\1', $css); //spaces before and after marks
		$css = preg_replace('/^\s+/', '', $css); //spaces on the begining

		return $css;
	}

	public function html_compress ($html)
	{
		preg_match_all('!(<(?:code|pre).*>[^<]+</(?:code|pre)>)!',$html,$pre); //exclude pre or code tags

		$html = preg_replace('!<(?:code|pre).*>[^<]+</(?:code|pre)>!', '#pre#', $html); //removing all pre or code tags
		$html = preg_replace('#<!–[^\[].+–>#', '', $html); //removing HTML comments
		$html = preg_replace('/[\r\n\t]+/', ' ', $html); //remove new lines, spaces, tabs
		$html = preg_replace('/>[\s]+</', '><', $html); //remove new lines, spaces, tabs
		$html = preg_replace('/[\s]+/', ' ', $html); //remove new lines, spaces, tabs

		if(!empty($pre[0]))
		{
			foreach($pre[0] as $tag)
			{
				$html = preg_replace('!#pre#!', $tag, $html, 1); //putting back pre|code tags
			}
		}

		return $html;
	}

	public function php_compress ($php)
	{
		/* remove comments */
		$php = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $php);
		/* remove tabs, spaces, new lines, etc. */
		$php = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $php);
		/* remove unnecessary spaces */
	    $php = str_replace('{ ', '{', $php);
	    $php = str_replace(' }', '}', $php);
	    $php = str_replace('; ', ';', $php);
	    $php = str_replace(', ', ',', $php);
	    $php = str_replace(' {', '{', $php);
	    $php = str_replace('} ', '}', $php);
	    $php = str_replace(': ', ':', $php);
	    $php = str_replace(' ,', ',', $php);
	    $php = str_replace(' ;', ';', $php);

		return $php;
	}
}

/*
#http://gadelkareem.com/2007/06/23/compressing-your-html-css-and-javascript-using-simple-php-code/
function JS_check(){

$jsfiles = array(’script.js',’style.css'); #add names of scripts you like to generate

foreach($jsfiles as $js){

clearstatcache();

#if the file doesn't exist or the time stamp time of both compressed and uncompressed files are not near

if(!file_exists($_SERVER['DOCUMENT_ROOT']."/js/{$js}") || ($diff = filemtime($_SERVER['DOCUMENT_ROOT']."/js/local_{$js}") - filemtime($_SERVER['DOCUMENT_ROOT']."/js/{$js}")) > 3){

if(strpos($js,'.css')===false){ #if a JS file

require_once($_SERVER['DOCUMENT_ROOT']."/include/class.JavaScriptPacker.php"); #include Packer class

$script = file_get_contents($_SERVER['DOCUMENT_ROOT']."/js/local_{$js}"); #getting JS file

$script = str_replace(';;;', '//',$script); #the ;;; comment feature in the packer beta
$packer = new JavaScriptPacker($script, 62, 1, 0); #using Dean Edwards ’s Packer, check documentations on the class file for the best compression level

$packed = $packer->pack(); #JS code compressed

}else  #if CSS file

$packed = css_cmpress(file_get_contents($_SERVER['DOCUMENT_ROOT']."/js/local_{$js}"));

file_put_contents($_SERVER['DOCUMENT_ROOT']."/js/{$js}", $packed); #inserting compressed code into files

touch($_SERVER['DOCUMENT_ROOT']."/js/local_{$js}"); #change the time stamp time for original scripts

#$GLOBALS['debuger'][] = "$js generated, diff was $diff"; #Global array to let you see results

}

}

}



function JS_check(){

$jsfiles = array(’script.js',’style.css'); #add names of scripts you like to generate

foreach($jsfiles as $js){

clearstatcache();

#if the file doesn't exist or the time stamp time of both compressed and uncompressed files are not near

if(!file_exists($_SERVER['DOCUMENT_ROOT']."/js/{$js}") || ($diff = filemtime($_SERVER['DOCUMENT_ROOT']."/js/local_{$js}") - filemtime($_SERVER['DOCUMENT_ROOT']."/js/{$js}")) > 3){

if(strpos($js,'.css')===false){ #if a JS file

require_once($_SERVER['DOCUMENT_ROOT']."/include/class.JavaScriptPacker.php"); #include Packer class

$script = file_get_contents($_SERVER['DOCUMENT_ROOT']."/js/local_{$js}"); #getting JS file

$script = str_replace(';;;', '//',$script); #the ;;; comment feature in the packer beta
$packer = new JavaScriptPacker($script, 62, 1, 0); #using Dean Edwards ’s Packer, check documentations on the class file for the best compression level

$packed = $packer->pack(); #JS code compressed

}else  #if CSS file

$packed = css_cmpress(file_get_contents($_SERVER['DOCUMENT_ROOT']."/js/local_{$js}"));

file_put_contents($_SERVER['DOCUMENT_ROOT']."/js/{$js}", $packed); #inserting compressed code into files

touch($_SERVER['DOCUMENT_ROOT']."/js/local_{$js}"); #change the time stamp time for original scripts

#$GLOBALS['debuger'][] = "$js generated, diff was $diff"; #Global array to let you see results

}

*/

?>
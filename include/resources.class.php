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
	private $_production_mode = TRUE;
	private $_cache_dir_name = '_c';
	private $_dirTree = array();

	private $_pathTemplate = '';
	private $_paths = array(
		'html' => array(
			'dir' => '',
			'type' => 'template',
			'gzip' => false,
			'dir_cache' => true // cache files in there own directory names, but in _c (cache dir)
		),
		'js' => array(
			'dir' => '',
			'type' => 'static',
			'url_path' => ''
		),
		'css' => array(
			'dir' => '',
			'type' => 'static',
			'url_path' => '',
			'filter' => '.swf,.htaccess'
		)
	);

	public function __construct()
	{
	}

	public function setPath ($paths)
	{
		$this->_paths = $paths;

		foreach ($paths as $path)
		{
			if (isset($path['type']) && $path['type'] == 'template')
			{
				$this->_pathTemplate = $path['dir'];
			}
		}
	}

	private function _setDB ($for)
	{
		if ($for == 'usage')
			return Database::Set('resource_usage', 'resource_usage_id');
		elseif ($for == 'package')
			return Database::Set('resource_package', 'resource_package_id');
		elseif ($for == 'resource')
			return Database::Set('resource', 'resource_id');
	}

	private function _resetDB ($for)
	{
		$resource = $this->_setDB('resource');

		if ($for == 'usage')
			return $resource->query('TRUNCATE TABLE resource_usage');
		elseif ($for == 'package')
			return $resource->query('TRUNCATE TABLE resource_package');
		elseif ($for == 'resource')
			return $resource->query('TRUNCATE TABLE resource');
	}

	public function setProductionMode ($mode)
	{
		$this->_production_mode = $mode ? TRUE : FALSE;
	}

	public function getPath ()
	{
		return $this->_paths;
	}

	protected function getType ($extension)
	{
		if ($extension == 'png' || $extension == 'jpeg' || $extension == 'jpg' || $extension == 'gif')
			return 'image';
		else
			return $extension;
	}

	protected function getTokens ($file)
	{
		$array = array(
			'@provides' => '',
			'@requires' => '',
			'@group'    => '',
			'@suggestion' => ''
		);

		if (!file_exists($file))
			return $array;

		$content = file_get_contents($file);

		if (preg_match_all('!/\*[^*]*\*+([^/][^*]*\*+)*/!', $content, $comment) > 0)
		{
			$comment = str_ireplace(array("/", "*", '  '), '', $comment[0]);
			$comment = explode("\n", implode('', $comment));

			foreach ($comment as $param)
			{
				$param = trim($param);
				$name 	= explode(' ', $param, 2);

				if (is_array($name))
				{
					switch ($name['0'])
					{
						case '@provides':
						case '@requires':
						case '@group':
						case '@suggestion':
							if (isset($array["{$name['0']}"]))
								$array["{$name['0']}"] .= isset($name['1']) ? ' '.$name['1'] : '';
							else
								$array["{$name['0']}"] = isset($name['1']) ? $name['1'] : '';
							break;
						default:
							break;
					}
				}
			}
		}

		return $array;
	}

	public function getTree ()
	{
		return $this->_dirTree;
	}

	public function scan ()
	{
		$paths = $this->_paths;

		foreach ($paths as $key => $scan_dir)
		{
			$this->_scan($scan_dir);
		}

		$this->updates();
		$this->save();

		return $this->_dirTree;
	}

	private function _scan (Array $path_array, $directory = NULL)
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
					$directory_tree = $this->_scan($sub_path_array, $path);
				}
				elseif (is_file($path))
				{
					$extension = end(explode('.', $fileName));

					if (in_array('.'.$extension, $filter))
					{
						continue;
					}

					$url = null;
					if (!empty($path_array['url_path']))
						$url = $path_array['url_path'].'/'.$path_array['subDir'].$fileName;

					$path = realpath($path);
					$this->_dirTree["{$path}"] = array (
						'subDir' => $path_array['subDir'],
						'local_url' => $url,
						'name' => $fileName,
						'type' => $path_array['type'],
						'extension' => $extension,
						'size' => filesize($path),
						'last_modified' => filemtime($path)
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

	public function updates ()
	{
		$DB = $this->_setDB('resource');
		$resource = $DB->Select('select * from resource');

		if ($resource)
		{
			foreach ($resource as $Next)
			{
				$path = $resource->path;
				if (!empty($this->_dirTree["$path"]))
				{
					// if its same old file then delete it
					if ($this->_dirTree["{$resource->path}"]['last_modified'] == $resource->last_modified)
						unset($this->_dirTree["{$resource->path}"]); // no need for update, its old file
					else // trigger for update
					{
						$this->_dirTree["{$resource->path}"]['id'] = $resource->resource_id;
						$this->_dirTree["{$resource->path}"]['is_update'] = TRUE;
					}
				}

			}
		}

		return $this->_dirTree;
	}

	public function save ()
	{
		$resource = $this->_setDB('resource');

		foreach ($this->_dirTree as $path => $file)
		{
			$resource->values(array());

			if (!isset($file['id']))
			{
				$resource->resource_id = NULL;
				$resource->path = addslashes($path);
				$resource->type = $file['type'];
				$resource->file_exetension = $file['extension'];
				$resource->file_name = $file['name'];
				$resource->url = addslashes($file['local_url']);
			}

			$resource->file_size = $file['size'];
			$resource->last_modified = $file['last_modified'];

			if ($file['extension'] == 'js' || $file['extension'] == 'css' || $file['type'] == 'template')
			{
				$token = $this->getTokens($path);

				$resource->name = $token['@provides'];
				$resource->requires = $token['@requires'];
				$resource->force_group_on = $token['@group'];
				$resource->suggestion = $token['@suggestion'] ? 1 : 0;
			}

			$resource->save(isset($file['id']) ? $file['id'] : false);
		}
	}

	public function build ()
	{
		if (!is_dir($this->_pathTemplate))
			trigger_error('please set the template directory');

		$cache_dir = $this->_pathTemplate.'/'.$this->_cache_dir_name;

		if (!is_dir($cache_dir))
		{
			if (!mkdir($cache_dir, 0755))
				trigger_error('Error creating cache dir "'.$cache_dir.'"');
		}

		$DB = $this->_setDB('resource');
		$resources = $DB->select('select * from resource where type = "template"');

		foreach ($resources as $Next)
		{
			$new_path = str_ireplace($this->_pathTemplate, $cache_dir, $resources->path);
			$dir = dirname($new_path);

			if (!is_dir($dir))
				mkdir($dir, 0755, TRUE);

			$replace_with = '<!--'.$resources->requires.'-->';

			$content = file_get_contents($resources->path);
			$content = str_ireplace('</head>', $replace_with.'</head>', $content, $count);
			if ($count == 0)
				$content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', $replace_with, $content, 1);

			$content = $this->php_compress($content);
			$content = $this->html_compress($content);
			file_put_contents($new_path, $content);
		}
	}

	public function fetch ()
	{
		;//TODO
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

	protected function gzip_save ($local_file, $save_dir, $gz_encoding_mode = 9)
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
	protected function js_compress ($js_file, $compile_mode = 's')
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

	protected function css_compress ($css)
	{
		$css = preg_replace('!//[^\n\r]+!', '', $css); //comments
		$css = preg_replace('/[\r\n\t\s]+/s', ' ', $css); //new lines, multiple spaces/tabs/newlines
		$css = preg_replace('#/\*.*?\*/#', '', $css); //comments
		$css = preg_replace('/[\s]*([\{\},;:])[\s]*/', '\1', $css); //spaces before and after marks
		$css = preg_replace('/^\s+/', '', $css); //spaces on the begining

		return $css;
	}

	protected function html_compress ($html)
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

	protected function php_compress ($php)
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
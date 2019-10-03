<?php
	$dir = $_GET["d"] ?? '';
	if(is_dir('.\\' . $dir))
	{
		$files = scandir('.\\' . $dir);
		unset($files[array_search('.', $files)]);
		unset($files[array_search('..', $files)]);
		$files = array_values($files);
	}
	else
	{
		$files = [];
	}
	
	$search = $_GET["s"] ?? '';
	$query = $_GET["q"] ?? '';
	$script = $_SERVER['SCRIPT_NAME'];
	$path = preg_replace('/^(.*?\/.*)(\?.*?)$/','$1',"http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
	$time = date(DATE_ATOM , time());
	
	echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
	echo "<feed xmlns=\"http://www.w3.org/2005/Atom\">";
	echo "<id>" . $path . "</id>";
	echo "<title>" . $path . "</title>";
	echo "<updated>" . $time . "</updated>";
	echo "<link type=\"application/atom+xml;profile=opds-catalog;kind=navigation\" title=\"Home\" href=\"" . $path . "\" rel=\"start\"/>";
	
	function addNavEntry($t,$u,$i,$c,$l)
	{
		echo '<entry>';
		echo '<title>' . $t . '</title>';
		echo '<updated>' . $u . '</updated>';
		echo '<id>' . $i . '</id>';
		echo '<content type="text">' . $c . '</content>';
		echo '<link type="application/atom+xml;profile=opds-catalog;kind=navigation" href="' . $l . '" rel="subsection"/>';
		echo '</entry>';
	}
	
	function addAcqEntry($t,$u,$i,$a,$s,$l,$lt)
	{
		echo "<entry>";
		echo "<title>" . $t . "</title>";
		echo "<updated>" . $u . "</updated>";
		echo "<id>" . $i . "</id>";
		echo "<author><name>" . $a . "</name></author>";
		echo "<summary>" . $s . "</summary>";
		echo "<link type=\"" . $lt . "\" href=\"" . $l ."\" rel=\"http://opds-spec.org/acquisition\"/>";
		echo "</entry>";
	}
	
	usort($files, function ($a, $b) use ($dir)
	{
		if (is_dir($dir.$a) && !is_dir($dir.$b))
		{
			return -1;
		}
		else if (is_dir($dir.$b) && !is_dir($dir.$a))
		{
			return 1;
		}
		else
		{
			$regex = '/(?:(?:\b(?:a|an|the)\b)|[^a-z0-9.]|(?: {2,}))+/i';
			return strnatcasecmp(preg_replace($regex, ' ', $a),preg_replace($regex, ' ', $b));
		}
	});
	
	if(!isset($_GET["s"]) && !isset($_GET["q"]))
	{
		if ($dir === '')
		{
			addNavEntry
			(
				'Search',
				$time,
				'Search',
				'Text Search',
				$path . '?s='
			);
		}
		
		for ($i = 0; $i < count($files); $i++)
		{		
			$name = htmlspecialchars(preg_replace('/(.*)(?:\..*)/','$1',$files[$i]));
			$ext = preg_replace('/.*(?:\.(.*))/','$1',strtolower($files[$i]));
			$url = preg_replace('/(.*\/).*/','$1',$path) . implode("/", array_map("rawurlencode", explode("/",($dir . $files[$i]))));
			switch ($ext)
			{
				case 'htaccess':
				case 'git':
				case 'gitignore':
				case 'php':
				case 'css':
				case 'ico':
					continue 2;
				case 'epub':
					$type = 'application/epub+zip';
					break;
				default:
					$type = 'application/' . $ext;
					break;
			}
			
			if (!is_dir($dir.$files[$i]))
			{
				addAcqEntry
				(
					$name,
					$time,
					$name,
					$ext,
					$url,
					$url,
					$type
				);
			}
			else
			{
				addNavEntry
				(
					htmlspecialchars($files[$i]) . '/',
					$time,
					$url,
					'/' . htmlspecialchars($dir . $files[$i]) . '/',
					$path . '?d=' . rawurlencode($dir . $files[$i]) . '/'
				);
			}
			
		}
	}
	else if (!isset($_GET["q"]))
	{
		addNavEntry
		(
			(($search)? preg_replace('/_(?!$)/',' ',$search) : 'Search'),
			$time,
			(($search)? preg_replace('/_(?!$)/',' ',$search) : 'Search'),
			(($search)?'Search for "' . preg_replace('/_(?!$)/',' ',$search) . '"':'Text Search'),
			(($search)? ($path . "?q=" . $search) : $path )
		);
		
		addNavEntry
		(
			'[space]',
			$time,
			'[space]',
			preg_replace('/_(?!$)/',' ',$search . "_"),
			$path . "?s=" . $search . '_'
		);
		
		for ($i = 0; $i < 26; $i++)
		{
			$name = chr($i+97);
			
			addNavEntry
			(
				strtoupper($name),
				$time,
				$name,
				preg_replace('/_(?!$)/',' ',$search . $name),
				$path . "?s=" . $search . $name
			);
		}
	}
	else
	{
		$list = [];
		$terms = preg_split('/[^A-Z0-9]/i', preg_replace('/(?:(?:\b(?:a|an|the)\b)|[^A-Z0-9]|(?: {2,}))+/i', ' ', $query));
		function recurse($d,$l)
		{
			global $list,$terms;
			$files = scandir($d);
			unset($files[array_search('.', $files)]);
			unset($files[array_search('..', $files)]);
			$files = array_values($files);
			for ($i = 0; $i < count($files); $i++)
			{
				if (is_dir($d . $files[$i]))
				{
					recurse($d . $files[$i] . '/', $l+1);
				}
				else
				{
					$sort = preg_replace('/(?:(?:\b(?:a|an|the)\b)|[^a-z0-9]|(?: {2,}))+/i', ' ', $d . $files[$i]);
					$words = explode(' ', trim($sort,' '));
					$lls = false;
					$llsa = 0;
					
					for ($ii = 0; $ii < count($terms); $ii++)
					{
						for ($iii = 0; $iii < count($words); $iii++)
						{
							$cls = levenshtein(strtolower($words[$iii]),strtolower($terms[$ii]))/strlen($words[$iii]);
							if ($cls < $lls || $lls === false)
							{
								$lls = $cls;
							}
						}
						$llsa += $lls;
						$lls = false;
					}
					$llsa = $llsa/count($terms);
					if ($llsa <= 3)
					{
						$list[] = (object) array('file' => $files[$i], 'dir' => $d, 'sort' => $sort, 'ls' => $llsa);
					}
				}
			}
		}
		
		recurse('./' . $dir,0);

		
		usort($list, function ($a, $b)
		{
			if ($a->ls !== $b->ls)
			{
				return $a->ls <=> $b->ls;
			}
			else
			{
				return strnatcasecmp($a->sort, $b->sort);
			}
		});
		
		addNavEntry
		(
			'Search',
			$time,
			'Search',
			'Text Search',
			$path . '?s='
		);

		$list = array_slice($list, 0, 100);
		
		for ($i = 0; $i < count($list); $i++)
		{
			$name = htmlspecialchars(preg_replace('/(.*)(?:\..*)/','$1',$list[$i]->file));
			$ext = preg_replace('/.*(?:\.(.*))/','$1',strtolower($list[$i]->file));
			$url = preg_replace('/(.*\/).*/','$1',$path) . implode("/", array_map("rawurlencode", explode("/",(preg_replace('/.\/(.*)/', '$1', $list[$i]->dir) . $list[$i]->file))));

			switch ($ext)
			{
				case 'htaccess':
				case 'git':
				case 'gitignore':
				case 'ico':
				case 'php':
				
				continue 2;
				case 'epub':
					$type = 'application/epub+zip';
				break;
				default:
					$type = 'application/' . $ext;
				break;
			}
			
			addAcqEntry
			(
				$name,
				$time,
				$name,
				$ext,
				$url,
				$url,
				$type
			);
		}
	}
	echo "</feed>";
?>

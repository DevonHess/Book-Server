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
	$script = $_SERVER['SCRIPT_NAME'];
	$path = preg_replace('/^(.*?\/.*)(\?.*?)$/','$1',"http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
	
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
?>
<html>	
	<head>
		<title>Broken Spine<?php echo (($dir)?' - ' . $dir:''); ?></title>
		<style>
			div div:nth-child(even)
			{
				background-color: Gainsboro;
			}
			.item
			{
				overflow: auto;
				text-align: center;
				padding: 10px;
			}
			.link
			{
				float: left;
			}
			.info
			{
				float: right;
			}
		</style>	
	</head>
	<body>
		<div>
			<div class="item">
				<?php
					$back = preg_replace('/(.*\/).*\//','$1',$dir);
					if ($back == '')
					{
						echo '<span class="link"><a>Broken Spine</a></span><span>/</span>';
					}
					else if ($back == $dir)
					{
						echo '<span class="link"><a href="/">Back</a></span><span>/' . $dir . '</span>';
					}
					else
					{
						echo '<span class="link"><a href="/' . $back . '">Back</a></span><span>/' . $dir . '</span>';
					}
				?>	
				<span class="info"><a href="/tree.php?d=<?php echo $dir; ?>">Tree</a> - <a href="/opds.php?d=<?php echo $dir; ?>">OPDS</a> - <form action="/search.php" style="display: inline;"><input type="hidden" name="d" value="<?php echo $dir; ?>"><input type="text" name="s" placeholder="Search" style="position: relative;"></form></span>
			</div>
			<?php
				for ($i = 0; $i < count($files); $i++)
				{
					$name = htmlspecialchars(preg_replace('/(.*)(?:\..*)/','$1',$files[$i]));
					$ext = preg_replace('/.*(?:\.(.*))/','$1',$files[$i]);
					$url = preg_replace('/(.*\/).*/','$1',$path) . rawurlencode($files[$i]);
					
					switch ($ext)
					{
						case 'htaccess':
						case 'git':
						case 'gitignore':
						case 'ico':
						case 'php':
						
						continue 2;
					}
					
					echo '<div class="item">';
					if (!is_dir($dir.$files[$i]))
					{
						echo '<span class="link"><a href="' . $url .'">' . $name . '</a></span>';
						echo '<span class="info">' . $ext . '</span>';
					}
					else
					{
						echo '<span class="link"><a href="' . rawurlencode($files[$i]) . '/">' . htmlspecialchars($files[$i]) . "/</a></span>";
						echo '<span class="info">/' . htmlspecialchars($dir . $files[$i]) . '/</span>';
					}
					
					echo '<br>';
					echo '</div>';
				}
			?>	
			</div>
		</div>
	</body>
</html>

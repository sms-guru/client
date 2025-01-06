<?php

namespace smsguru;

trait iniFunctions {
    public function loadINI()
	{
		$ini_array = parse_ini_file($this->ini_path);
		return $ini_array;
	}

    public function updateINI(Array $data)
	{
		// $written_data = http_build_query($data, '', "\n");
		$written_data = $this->arr2ini($data);
		@$fp = fopen($this->ini_path, "w");
		@flock($fp, LOCK_EX);
		fwrite($fp,$written_data);
		flock($fp,LOCK_UN);
		fclose($fp);
	}

	function arr2ini(array $a, array $parent = array())
	{
		$out = '';
		foreach ($a as $k => $v)
		{
			if (is_array($v))
			{
				//subsection case
				//merge all the sections into one array...
				$sec = array_merge((array) $parent, (array) $k);
				//add section information to the output
				$out .= '[' . join('.', $sec) . ']' . PHP_EOL;
				//recursively traverse deeper
				$out .= $this->arr2ini($v, $sec);
			}
			else
			{
				//plain key->value case
				$out .= "$k=$v" . PHP_EOL;
			}
		}
		return $out;
	}
} 
<?php // v1.01 2017-01-26 +
function debug() {
	$d = debug_backtrace();
	// print_r($d);
	$c = count($d);
	$b = $c >> 1 ? 1 : 0;
	$a = array('debug_stop');
	// echo $b, $c;
	for ($i=$b; $i < $c; $i++)
		if (!in_array($d[$i]["function"], $a))
			break;
	if ($i >= $c) {
		$b = 0;
		$i = $c - 1;
	}
	echo '<script type="text/javascript">' . PHP_EOL;
	if ($b) {
		$f = " [function]=>" . $d[$i]['function'] . "()";
		for ($j=$i + 1; $j < min($i + 3, $c); $j++)
			$f .= " <- " . $d[$j]['function'] . "()";
		if ($j < $c)
			$f .= " ...";
	} else
		$f = '';
	$ret = '[file]=>' . $d[$i - $b]['file'] . ' [line]=>' . $d[$i - $b]['line'] . $f;
	echo 'console.debug("' . str_replace('\\', '\\\\', $ret) . '");' . PHP_EOL;
	$n = func_num_args();
	for ($i = 0; $i < $n; $i++) {
		$a1 = func_get_arg($i);
		if (is_string($a1) && strlen($a1) > 0 && $a1[0] === '$' && $i + 1 < $n) {
			$i++;
			$a2 = ", '->', " . json_encode(func_get_arg($i));
		} else
			$a2 = '';
		$a1 = '  ' . json_encode($a1);
		echo "console.debug('  ', " . $a1 . $a2 . ");" . PHP_EOL;
	}
	echo "</script>" . PHP_EOL;
	return $ret;
}

function debug_stop($value = null, $print_to_screen = true, $stop_condition = true, $only_on_stop = true) {
	if (!$stop_condition && $only_on_stop) {
		return;
	}
	if (is_null($value))
		$bp = debug();
	else {
		$bp = debug('$value', $value);
		if ($print_to_screen) {
			echo "value = ";
			if (is_string($value)) {
				$value = htmlspecialchars($value);
			}
			print_r($value);
			echo '<br />';
		}
	}
	if ($stop_condition)
		die("Останов: $bp");
}
?>
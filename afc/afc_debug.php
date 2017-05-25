<?php // v1.02 2017-03-18

function GetBacktrace($depth) {
    $s = '';
    $MAXSTRLEN = 64;
    $trace_array = debug_backtrace();
    while (in_array($trace_array[1]['function'] ?? '', array('debug', 'debug_stop')))
        array_shift($trace_array);
    $line = (isset ($trace_array[0]['line']) ? $trace_array[0]['line']: 'unknown');
    $file = (isset ($trace_array[0]['file']) ? basename($trace_array[0]['file']): 'unknown');
    $s .= sprintf('# %s # %s:%d', $trace_array[0]['function'], $file, $line);
    array_shift($trace_array);
    $pops = sizeof($trace_array) - $depth;
    for ($i = 0; $i < $pops; $i++)
        array_pop($trace_array);
    $tabs = sizeof($trace_array);
    foreach ($trace_array as $arr) {
        if ($tabs)
            $s .= " <- ";
        $tabs -= 1;
        if (isset ($arr['class']))
            $s .= $arr['class'] . '.';
        $args = array ();
        if (!empty ($arr['args']))
            foreach ($arr['args'] as $v) {
                if (is_null($v))
                    $args[] = 'null';
                elseif (is_array($v))
                    $args[] = 'Array[' . sizeof($v) . ']';
                elseif (is_object($v))
                    $args[] = 'Object: ' . get_class($v);
                elseif (is_bool($v))
                    $args[] = $v ? 'true' : 'false';
                else {
                    $v = (string)@$v;
                    $str = htmlspecialchars(substr($v, 0, $MAXSTRLEN));
                    if (strlen($v) > $MAXSTRLEN)
                        $str .= '...';
                    $args[] = "'" . $str . "'";
                }
            }
        $s .= $arr['function'] . '(' . implode(', ', $args) . ')';
        $line = (isset ($arr['line']) ? $arr['line']: 'unknown');
        $file = (isset ($arr['file']) ? basename($arr['file']): 'unknown');
        $s .= sprintf(':%s:%d', $file, $line);
    }
    if ($pops > 0)
        $s .= ' ...';
    return $s;
}

function debug() {
    echo '<script type="text/javascript">' . PHP_EOL;

    $ret = GetBacktrace(3);

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
        echo "console.debug('  ', $a1$a2);" . PHP_EOL;
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

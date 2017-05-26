<?php // v1.03 2017-03-18

function GetBacktrace($depth)
{
    // $s = '';
    $MAXSTRLEN = 64;
    $trace_array = debug_backtrace();
    while (in_array($trace_array[1]['function'] ?? '', ['debug', 'debug_stop']))
        array_shift($trace_array);
    $file = $trace_array[0]['file'] ?? 'unknown';
    $line = $trace_array[0]['line'] ?? 'unknown';
    $a[] = sprintf('# %s(%d)', $file, $line);
    array_shift($trace_array);

    // If a depth is specified, we leave only the first depth values in the array
    if ($depth > 0) {
        $pops = sizeof($trace_array) - $depth;
        for ($i = 0; $i < $pops; $i++)
            array_pop($trace_array);
    } else
        $pops = 0;
    $tabs = sizeof($trace_array);
    foreach ($trace_array as $arr) {
        if ($tabs)
            $s = '# ';
        $tabs -= 1;
        $line = ($arr['line'] ?? 'unknown');
        $file = (isset ($arr['file']) ? $arr['file']: 'unknown');
        $s .= sprintf('%s(%d)::', $file, $line);
        $s .= $arr['class'] ?? '';
        $s .= $arr['type'] ?? '';
        $args = array ();
        if (!empty ($arr['args']))
            foreach ($arr['args'] as $v) {
                if (is_null($v))
                    $args[] = 'null';
                elseif (is_array($v))
                    // $args[] = 'Array[' . sizeof($v) . ']';
                    $args[] = json_encode($v);
                elseif (is_object($v))
                    // $args[] = 'Object:' . get_class($v);
                    $args[] = json_encode($v);
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
        $a[] = $s;
    }
    if ($pops > 0)
        $a[] = '# ...';
    return $a;
}

function debug(array $params = null, $depth = 0)
{
    echo '<script type="text/javascript">' . PHP_EOL;

    $a = GetBacktrace($depth);
    $a = array_reverse($a);
    $s = '';
    foreach ($a as $v) {
        $s .= str_replace(['\\', "'", "\n", "\r"], ['\\\\', "\\'", '.', '.'], $v) . '\n';
    }
    echo "console.debug('$s');" . PHP_EOL;
    foreach ($params as $key => $value) {
        $v = '  ' . json_encode($value);
        echo "console.debug('  $key: ', $v);" . PHP_EOL;
    }
    echo "</script>" . PHP_EOL;
    return $a;
}

function debug_stop($value = null, $print_to_screen = true, $stop_condition = true, $only_on_stop = true)
{
    if (!$stop_condition && $only_on_stop) {
        return;
    }
    if (is_null($value))
        $bp = debug();
    else
        $bp = debug('$value', $value);
    if ($print_to_screen) {
        // $bp = array_reverse($bp);
        $s = '';
        foreach ($bp as $v)
            $s .= $v . '<br />';
        if ($value) {
            if (is_string($value))
                $value = htmlspecialchars($value);
            $s .= "value = " . print_r($value, true) . '<br />';
        }
        echo $s;
    }
    if ($stop_condition)
        die("Останов программы");
}
?>

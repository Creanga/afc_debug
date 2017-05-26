<?php // v1.04 2017-04-13

function GetBacktrace($depth)
{
// debug_save_string("\n----------------");
    $trace_array = debug_backtrace();
// debug_save_array($trace_array);
    while (in_array($trace_array[1]['function'] ?? '', ['debug', 'debug_stop']))
        array_shift($trace_array);
    if ($depth > 0) {
        $pops = count($trace_array) - $depth;
        for ($i = 0; $i < $pops; $i++)
            array_pop($trace_array);
    } else
        $pops = 0;
    // For first record we have to get only filename and line number
    $i = 0;
    foreach ($trace_array as $rec) {
        $s = '';
        $line = ($rec['line'] ?? 'unknown');
        $file = (isset ($rec['file']) ? $rec['file']: 'unknown');
        $s .= sprintf('%s(%d)', $file, $line);

        // Line with "debug" function (first line)
        if ($i++ == 0) {
            $a[] = triming_string($s, true);
            continue;
        }

        $s .= '::';
        $s .= $rec['class'] ?? '';
        $s .= $rec['type'] ?? '';

        $s .= $rec['function'] . "(";
        $args = null; // Array of function parameters
        foreach ($rec['args'] as $arg)
            switch (gettype($arg)) {
                case 'object':
                case 'string':
                    parse_array_item($arg);
                    $args[] = $arg;
                    break;
                case 'array':
                    array_walk_recursive($arg, 'parse_array_item');
                    $args[] = $arg;
                    break;
                default:
                    $args[] = $arg ?? 'NULL';
                    break;
            }
        // $s = triming_string($s, true);

// debug_save_string($s);
        $a[] = [triming_string($s, true), $args];
    }
    if ($pops > 0)
        $a[] = '...';
// debug_save_array($a);
    return $a;
}

function debug(array $params = null, $depth = 3)
{
    ob_start();
    $a = array_reverse(GetBacktrace($depth));
// debug_save_array($a);

    echo '<script type="text/javascript">' . PHP_EOL;

    echo "console.debug(";

    // There is no need to print ",\n"
    // at the beginning of the first record
    $i = 0;
    foreach ($a as $v) {
        // Print the start of the breakpoint line
        $s = $i++ ? ", '\\n# " : "'# ";

        // if $v is array, then print $v[0], else print $v
        if (is_array($v)) {
            // Print breakpoint line with function arguments
            $s .= jshtml_chars($v[0]);
            if (isset($v[1])) {
                // There is no need to print ","
                // at the beginning of the first record
                $j = 0;
                foreach ((array)$v[1] as $arg) {
                    $s .= $j++ ? "', ',', " : "', ";
                    $s .= json_encode($arg) . ",'";
                }
            }
            // Check if the breakpoint line ends with "function ("
            if (substr($v[0], -1) == '(')
                $s .= ")'";
            else
                $s .= "'";
        } else
            // Print only breakpoint line
            $s .= jshtml_chars($v) . "'";
// debug_save_string($s);
        echo $s;
    }

    foreach ($params as $key => $value) {
        echo PHP_EOL, ", '\\n  $key: ', ", ($v1 = json_encode($value)) ? $v1 : "'error encoding!'";
    }

    echo ");", PHP_EOL;

    echo "</script>", PHP_EOL;
// debug_save_string(ob_get_contents());
    echo ob_get_clean();
    return $a;
}

function debug_stop($params = null, $print_to_screen = true, $stop_condition = true, $only_on_stop = true)
{
    if (!$stop_condition && $only_on_stop) {
        return;
    }
    $a = debug([$params], 0);

    if ($print_to_screen) {
        foreach ($a as $v) {
            // if $v (argument) is array, then print $v[0], else print $v
            if (is_array($v)) {
                // Print breakpoint line with function arguments
                echo "<br /># ", htmlspecialchars($v[0]);
                if (isset($v[1])) {
                    // There is no need to print ","
                    // at the beginning of the first argument
                    $i = 0;
                    foreach ($v[1] as $arg) {
                        if ($i++ > 0)
                            echo ", ";
                        echo htmlspecialchars(print_r($arg, true));
                    }
                }
                if (substr($v[0], -1) == "(")
                    // The end of breakpoint line is "function ("
                    echo ")";
            } else
                // Print only breakpoint line
                echo "<br /># ", htmlspecialchars($v);
        }
        echo "<br /><br />Breakpoint parameter is: ", htmlspecialchars(print_r($params, true)), "<br />";
    }
    if ($stop_condition)
        die("<br />Programm stopped at $v!");
}

//
function jshtml_chars(string $value='')
{
    return str_replace(
        ["\\", '"', "'", "\n", "\r", "-&gt;", "=&gt;"],
        ["\\\\", '\\"', "\\'", ".", ".", "\\u2192", "\\u21d2"],
        htmlspecialchars($value, ENT_NOQUOTES | ENT_COMPAT | ENT_HTML401)
    );
}

// The callback function for parsing objects and strings in an array
function parse_array_item(&$item, $key = null)
{
    switch (gettype($item)) {
        case 'object':
            // Converting the object to a string
            $item = 'Object{' . triming_string(get_class($item), true) . '}';
            break;
        case 'string':
            $item = "\"" . str_replace(["\n", "\r"], [".", "."], triming_string($item)) . "\"";
            break;
    }
}

// If necessary cut off the right or left side of the line
function triming_string(string $string, $left=false)
{
    $MAXSTRLEN = 79;
    if (mb_strlen($string) > $MAXSTRLEN)
        if ($left)
            return "..." . substr($string, 3 - $MAXSTRLEN);
        else
            return substr($string, 0, $MAXSTRLEN - 3) . "...";
    else
        return $string;
}

// Copy the string to text file
function debug_save_string($string, $file = 'afc_debug.txt')
{
    file_put_contents($file, $string . "\n", FILE_APPEND | LOCK_EX);
}
// Copy the array to text file
function debug_save_array(array &$a, $level = 0, $file = 'afc_debug.txt')
{
    if (!$level)
        file_put_contents($file, "\n----------------\n", FILE_APPEND | LOCK_EX);
    foreach ($a as $key => $value) {
        switch (gettype($value)) {
            case 'object':
                file_put_contents($file, str_repeat("  ", $level) . "$key=>Object{" . get_class($value) . "}\n", FILE_APPEND | LOCK_EX);
                break;
            case 'array':
                file_put_contents($file, str_repeat("  ", $level) . "$key=>\n", FILE_APPEND | LOCK_EX);
                debug_save_array($value, $level + 1);
                break;
            default:
                file_put_contents($file, str_repeat("  ", $level) . "$key=>$value\n", FILE_APPEND | LOCK_EX);
                break;
        }
    }
}
?>

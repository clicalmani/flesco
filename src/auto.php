<?php
function loadClass($className) {
    $fileName = '';
    $namespace = '';
    
    // Sets the include path as the "src" directory
    $includePath = root_path();
    
    if (false !== ($lastNsPos = strripos($className, '\\'))) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }

    $fileName = str_replace('\\', '/', $fileName);
    $fileName .= $className . '.php';
    $fullFileName = $includePath . $fileName;

    $bindings = [
        'App/' => 'app/',
        'Models/' => 'models/',
        'Middleware/' => 'middleware/',
        'Controllers/' => 'controllers/',
        'Authenticate/' => 'authenticate/'
    ];

    foreach ($bindings as $key => $value) {
        $fullFileName = str_replace($key, $value, $fullFileName);
    }
    
    if (file_exists($fullFileName)) {
        require $fullFileName;
    }
}

spl_autoload_register('loadClass'); // Registers the autoloader
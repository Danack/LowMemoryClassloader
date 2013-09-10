<?php

/*
 * This file was part of Composer, and still depends on Composer to function.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *     Dan Ackroyd <Danack@basereality.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Intahwebz\Autoload;


class LowMemoryClassloader
{
    private $classMap = null;
    private $prefixes = array();

    function __construct() {
        $filepath = dirname(dirname(__DIR__)).'/composer/autoload_namespaces.php';
        $map = require $filepath;

        foreach ($map as $namespace => $path) {
            $this->set($namespace, $path);
        }

        $this->register(true);
    }
    
    
    /**
     * Registers a set of classes, replacing any others previously set.
     *
     * @param string       $prefix The classes prefix
     * @param array|string $paths  The location(s) of the classes
     */
    public function set($prefix, $paths) {
        $this->prefixes[substr($prefix, 0, 1)][$prefix] = (array) $paths;
    }

    /**
     * Registers this instance as an autoloader.
     *
     * @param bool $prepend Whether to prepend the autoloader or not
     */
    public function register($prepend = false)
    {
        spl_autoload_register(array($this, 'loadClass'), true, $prepend);
    }

    /**
     * Unregisters this instance as an autoloader.
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }

    /**
     * Loads the given class or interface.
     *
     * @param  string    $class The name of the class
     * @return bool|null True if loaded, null otherwise
     */
    public function loadClass($class)
    {
        if ($file = $this->findFile($class)) {
            include $file;

            return true;
        }
    }

    /**
     * Finds the path to the file where the class is defined.
     *
     * @param string $class The name of the class
     *
     * @return string|false The path if found, false otherwise
     */
    public function findFile($class)
    {
        if (false !== $pos = strrpos($class, '\\')) {
            // namespaced class name
            $classPath = strtr(substr($class, 0, $pos), '\\', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $className = substr($class, $pos + 1);
        } else {
            // PEAR-like class name
            $classPath = null;
            $className = $class;
        }

        $classPath .= strtr($className, '_', DIRECTORY_SEPARATOR) . '.php';

        $first = $class[0];
        if (isset($this->prefixes[$first])) {
            foreach ($this->prefixes[$first] as $prefix => $dirs) {

                //Check all possible paths in OPCache before checking the file system
                if (0 === strpos($class, $prefix)) {
                    foreach ($dirs as $dir) {
                        $filename = $dir.DIRECTORY_SEPARATOR.$classPath;
                        if (opcache_script_cached($filename) == true) {
                            return $filename;
                        }
                    }

                    foreach ($dirs as $dir) {
                        $filename = $dir.DIRECTORY_SEPARATOR.$classPath;
                        if (file_exists($filename)) {
                            return $filename;
                        }
                    }
                }
            }
        }
        
        //Didn't find it - might be a classmap
        if ($this->classMap == null) {
            $filepath = dirname(dirname(__DIR__)).'/composer/autoload_classmap.php';
            $this->classMap = require $filepath;
        }

        if (isset($this->classMap[$class])) {
            return $this->classMap[$class];
        }

        return false;
    }
}


$loader = new \Intahwebz\Autoload\LowMemoryClassloader();

return $loader;



 
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drupal\asciidoc\Component\Process;

/**
 * Generic executable finder.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ExecutableFinder
{
    private $windows_suffixes = array('.exe', '.bat', '.cmd', '.com');

    protected $suffixes;
    protected $paths;

    public function __construct() {
      if (ini_get('open_basedir')) {
        $this->paths = explode(PATH_SEPARATOR, ini_get('open_basedir'));
      } else {
        $this->paths = array_merge(explode(PATH_SEPARATOR, getenv('PATH') ?: getenv('Path')));
      }

      $suffixes = array('');
      if ('\\' === DIRECTORY_SEPARATOR) {
        $pathExt = getenv('PATHEXT');
        $suffixes = array_merge($suffixes, $pathExt ? explode(PATH_SEPARATOR, $pathExt) : $this->windows_suffixes);
      }
      $this->suffixes = $suffixes;
    }

  /**
     * Replaces default suffixes of executable.
     *
     * @param array $suffixes
     */
    public function setSuffixes(array $suffixes)
    {
        $this->suffixes = $suffixes;
    }

    /**
     * Adds new possible suffix to check for executable.
     *
     * @param string $suffix
     */
    public function addSuffix($suffix)
    {
        $this->suffixes[] = $suffix;
    }

    /**
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Replaces default paths of executable.
     *
     * @param array $paths
     */
    public function setPaths(array $paths)
    {
        $this->paths = $paths;
    }

    /**
     * Adds new possible path to check for executable.
     *
     * @param string $path
     */
    public function addPath($path)
    {
        $this->paths[] = $path;
    }

    /**
     * Finds an executable by name.
     *
     * @param string $name      The executable name (without the extension)
     * @param string $default   The default to return if no executable is found
     * @param array  $extraDirs Additional dirs to check into
     *
     * @return string The executable path or default value
     */
    public function find($name, $default = null, array $extraDirs = array())
    {
        $dirs = $this->paths;
        if (!ini_get('open_basedir')) {
            $dirs = array_merge($this->paths, $extraDirs);
        }

        foreach ($this->suffixes as $suffix) {
            foreach ($dirs as $dir) {
                if (basename($dir) == $name && @is_executable($dir)) {
                    return $dir;
                }
                if (@is_file($file = $dir.DIRECTORY_SEPARATOR.$name.$suffix) && ('\\' === DIRECTORY_SEPARATOR || is_executable($file))) {
                    return $file;
                }
            }
        }

        return $default;
    }

  /**
   * @param string $name      The executable name (without the extension)
   *
   * @return array The executables that match the given paths, name and suffixes
   */
  public function findAll($name)
  {
    $all_paths = $this->getPaths();
    $this->setPaths([]);

    $found = [];
    foreach ($all_paths as $path) {
      if ($file = $this->find($name, NULL, [$path])) {
        $found[$path] = $file;
      }
    }
    $this->setPaths($all_paths);

    return $found;
  }
}

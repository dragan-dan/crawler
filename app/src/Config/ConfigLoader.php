<?php

namespace Config;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Resource\FileResource;

class ConfigLoader
{

    const PARAMETER_CONST_REGEX = '/(\%const\.([0-9a-z\.\-\_]+)\%)/i';
    const PARAMETER_REGEX = '/\%([0-9a-z\.\-\_]+)\%/i';

    /** @var  DelegatingLoader */
    protected $delegatingLoader;

    /**
     * @var array - Path to config directory
     */
    private static $configDirPaths;

    /**
     * @var string - Path to cache file
     */
    private static $cachePath;

    /**
     * @param array $configPaths - an array of paths to load configs
     * @param string $cachePath - config cache path
     */
    public function __construct($configPaths = null, $cachePath = null)
    {
        if ($configPaths) {
            self::$configDirPaths = $configPaths;
        }

        if (isset($cachePath)) {
            self::$cachePath = $cachePath;
        }

        $locator                = new FileLocator(self::getConfigDirPaths());
        $yamlFileLoader         = new YamlLoader($locator);
        $loaderResolver         = new LoaderResolver([$yamlFileLoader]);
        $this->delegatingLoader = new DelegatingLoader($loaderResolver);
    }

    /**
     * Loads configs from files or from cache file and return this configs.
     *
     * @return array
     */
    public function loadConfigs()
    {
        $cachePath   = self::getCachePath();
        $userMatcher = new ConfigCache($cachePath, DEBUG);


        // not to get an error, because this is coming from required file
        $configArray = [];
        $resources = [];

        foreach (self::getConfigDirPaths() as $path) {
            $this->loadConfigsFromPath($configArray, $resources, $path);
        }


        // variable $flatConfig will come from require
        return $configArray;
    }

    /**
     * Load config from given path
     *
     * @param array $configArray
     * @param FileResource[] $resources
     * @param $path
     *
     * @return array
     */
    private function loadConfigsFromPath(&$configArray, &$resources, $path)
    {
        if ($handle = opendir($path)) {

            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {

                    if ($this->delegatingLoader->supports($entry)) {
                        $config = $this->delegatingLoader->import($entry);
                        $configArray += $config['parameters'];
                        $resources[] = new FileResource($entry);
                    }
                }
            }
            closedir($handle);
        }
        return $configArray;
    }

    /**
     * Loop through config array and replace constants and environment variables
     *
     * @param $data
     *
     * @return mixed
     */
    protected function postParser(&$data)
    {
        foreach ($data as &$configValue) {
            if (is_array($configValue)) {
                $configValue = $this->postParser($configValue);
            } else {
                if (is_string($configValue)) {
                    if (preg_match(self::PARAMETER_CONST_REGEX, $configValue, $matches)) {
                        if (defined($matches[2])) {
                            $configValue = str_replace($matches[1], constant($matches[2]), $configValue);
                        }
                    } elseif (preg_match(self::PARAMETER_REGEX, $configValue, $matches)) {
                        $param = getenv($matches[1]);
                        if ($param) {
                            $configValue = $param;
                        } else {
                            // Strip the parameter special chars from config name
                            $configValue = str_replace('%', '', $configValue);
                        }
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Returns path to config directories.
     *
     * @return array
     */
    public static function getConfigDirPaths()
    {
        if (is_null(self::$configDirPaths)) {
            // default
            self::$configDirPaths = [PATH_ROOT . '/app/config'];
        }
        return self::$configDirPaths;
    }

    /**
     * Returns path to cache file.
     *
     * @return string
     */
    public static function getCachePath()
    {
        if (is_null(self::$cachePath)) {
            // default value
            self::$cachePath = PATH_ROOT . '/app/cache/appUserMatcher.php';
        }

        return self::$cachePath;
    }

}

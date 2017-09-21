<?php

namespace Config;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Yaml;

class YamlLoader extends FileLoader
{
    /**
     * Loads a resource.
     *
     * @param mixed       $resource The resource
     * @param string|null $type     The resource type or null if unknown
     *
     * @throws \Exception If something went wrong
     *
     * @return array
     */
    public function load($resource, $type = null)
    {
        $configFile = $this->locator->locate($resource);
        $configValues  = Yaml::parse(file_get_contents($configFile), true);

        if (null === $configValues) {
            throw new \Exception(sprintf('Could not load config %s', $configFile));
        }

        return $configValues;
    }

    /**
     * Returns whether this class supports the given resource.
     *
     * @param mixed       $resource A resource
     * @param string|null $type     The resource type or null if unknown
     *
     * @return bool True if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource)
            && 'yml' === pathinfo($resource, PATHINFO_EXTENSION)
            && (!isset($type) || 'yaml' === $type);
    }
} 

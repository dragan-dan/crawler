<?php

namespace Config;

class Config
{

    /** @var  array */
    protected $config;

    public function __construct()
    {
        $configLoader = new ConfigLoader();
        $this->config = $configLoader->loadConfigs();
    }

    /**
     * Gets a config value
     *
     * @param $key
     *
     * @return string
     */
    public function get($key)
    {
        // Find the value
        $keyArr = explode('.', $key);

        $configValue = &$this->config;
        foreach ($keyArr as $keyPath) {
            $configValue = &$configValue[$keyPath];
        }

        return $configValue;
    }


}

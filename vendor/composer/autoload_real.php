<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit06c775433a83ed276f0a1d8ac25f93ba_crmⓥ5_5_2_alpha
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        require __DIR__ . '/platform_check.php';

        spl_autoload_register(array('ComposerAutoloaderInit06c775433a83ed276f0a1d8ac25f93ba_crmⓥ5_5_2_alpha', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit06c775433a83ed276f0a1d8ac25f93ba_crmⓥ5_5_2_alpha', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInit06c775433a83ed276f0a1d8ac25f93ba_crmⓥ5_5_2_alpha::getInitializer($loader));

        $loader->setClassMapAuthoritative(true);
        $loader->register(true);

        return $loader;
    }
}

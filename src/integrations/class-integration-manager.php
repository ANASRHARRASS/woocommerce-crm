<?php
/**
 * IntegrationManager
 *
 * Simple registry to lazily register and obtain integration instances.
 */
class IntegrationManager {
    private static $instances = [];
    private static $factories = [];

    public static function register(string $key, callable $factory) {
        self::$factories[$key] = $factory;
    }

    public static function get(string $key) {
        if (isset(self::$instances[$key])) {
            return self::$instances[$key];
        }
        if (isset(self::$factories[$key])) {
            $inst = call_user_func(self::$factories[$key]);
            self::$instances[$key] = $inst;
            return $inst;
        }
        return null;
    }

    public static function all(): array {
        return array_keys(self::$factories);
    }
}

?>

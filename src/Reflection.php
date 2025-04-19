<?php

namespace Cesys\Utils;

/**
 * Helper pro různé Reflection - vše cachuje takže nevytváříme zbytečně znovu
 */
class Reflection
{

    private static array $cache = [];

    /**
     * @param string $className
     * @return \ReflectionClass
     * @throws \ReflectionException
     */
    public static function getReflectionClass(string $className): \ReflectionClass
    {
        if (isset(self::$cache[__METHOD__][$className])) {
            return self::$cache[__METHOD__][$className];
        }
        return self::$cache[__METHOD__][$className] = new \ReflectionClass($className);
    }


    /**
	 * Vrací všechny \ReflectionProperty properties definované ve třídě $className
	 * Bez private properties v předcích
     * @param string $className
     * @return \ReflectionProperty[]
     * @throws \ReflectionException
     */
    public static function &getReflectionPropertiesOfClass(string $className): array
    {
        if (isset(self::$cache[__METHOD__][$className])) {
            return self::$cache[__METHOD__][$className];
        }

		self::$cache[__METHOD__][$className] = self::getReflectionClass($className)->getProperties();
        return self::$cache[__METHOD__][$className];
    }

    /**
	 * Vrací všechny \ReflectionProperty properties definované ve třídě $className
	 * I s private properties v předcích
     * @param string $className
     * @return \ReflectionProperty[]
     * @throws \ReflectionException
     */
    public static function &getAllReflectionPropertiesOfClass(string $className): array
    {
        if (isset(self::$cache[__METHOD__][$className])) {
            return self::$cache[__METHOD__][$className];
        }

        $properties = [];
        $rc = self::getReflectionClass($className);
        do {
            $properties = array_merge($properties,  $rc->getProperties());
        } while ($rc = $rc->getParentClass());
		self::$cache[__METHOD__][$className] = $properties;
        return self::$cache[__METHOD__][$className];
    }

	/**
	 * @param string $className
	 * @return \ReflectionMethod[]
	 * @throws \ReflectionException
	 */
	public static function &getReflectionMethodsOfClass(string $className): array
	{
		if (isset(self::$cache[__METHOD__][$className])) {
			return self::$cache[__METHOD__][$className];
		}

		self::$cache[__METHOD__][$className] = self::getReflectionClass($className)->getMethods();
		return self::$cache[__METHOD__][$className];
	}

	/**
	 * Klíče jsou názvy metod
	 * @param string $className
	 * @return \ReflectionMethod[]
	 * @throws \ReflectionException
	 */
	public static function &getReflectionMethodsByNameOfClass(string $className): array
	{
		if (isset(self::$cache[__METHOD__][$className])) {
			return self::$cache[__METHOD__][$className];
		}

		$result = [];
		foreach (self::getReflectionClass($className)->getMethods() as $method) {
			$result[$method->getName()] = $method;
		}
		self::$cache[__METHOD__][$className] = $result;
		return self::$cache[__METHOD__][$className];
	}

    /**
     * Vrací 'magic' properties (podobně jako a zkopírováno z Nette/SmartObject) z anotací nad třídou
	 * a z anotací předků / trait
     * $type je část za "@property". tj. "" pro "@property", "-read" pro "@property-read" atd.
     * @param string $className
     * @return array of [$type, $propertyType, $propertyName]
     * @throws \ReflectionException
     */
    public static function getMagicProperties(string $className): array
    {
        static $cache;
        $matches = &$cache[$className];
        if ($matches !== null) {
            return $matches;
        }
        $matches = [];
        $reflectionClass = self::getReflectionClass($className);
        preg_match_all(
            '~^  [ \t*]*  @property(|-read|-write|-deprecated)  [ \t]+  ([?\w|]+)  [ \t]+  \$  (\w+)  ()~mx',
            (string)$reflectionClass->getDocComment(),
            $matches,
            PREG_SET_ORDER,
        );

        foreach ($matches as &$match) {
            $match = array_slice($match, 1, 3);
        }

        foreach ($reflectionClass->getTraits() as $trait) {
            $matches = array_merge(self::getMagicProperties($trait->name), $matches);
        }

        if ($parent = get_parent_class($className)) {
            $matches = array_merge(self::getMagicProperties($parent), $matches);
        }
        return $matches;
    }

}
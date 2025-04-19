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
	 * @param string $class
	 * @return array
	 */
	public static function getUsedTraits(string $class): array
	{
		$results = [];

		foreach (array_reverse(class_parents($class)) + [$class => $class] as $class) {
			$results += static::traitUsesRecursive($class);
		}

		return array_unique($results);
	}

	private static function traitUsesRecursive(string $trait)
	{
		$traits = class_uses($trait);

		foreach ($traits as $trait) {
			$traits += static::traitUsesRecursive($trait);
		}

		return $traits;
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


	/**
	 * @param \ReflectionProperty $ref
	 * @param string $name
	 * @return string|null
	 */
	public static function parseAnnotation(\ReflectionProperty $ref, string $name): ?string
	{
		$re = '#[\s*]@' . preg_quote($name, '#') . '(?=\s|$)[ \t]+(.+)?#';
		if ($ref->getDocComment() && preg_match($re, trim($ref->getDocComment(), '/*'), $m)) {
			return $m[1] ?? '';
		}

		return null;
	}

	public static function expandClassName(string $name, \ReflectionClass $context): string
	{
		return \Nette\Utils\Reflection::expandClassName($name, $context);
	}
}
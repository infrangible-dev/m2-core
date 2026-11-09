<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\ObjectManagerInterface;
use ReflectionException;
use RuntimeException;

class Instances
{
    /** @var ObjectManagerInterface */
    protected ObjectManagerInterface $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Creates a new instance of the class.
     *
     * @param string $className
     * @param array $arguments
     *
     * @phpstan-param array<string, mixed> $arguments
     *
     * @return object|null
     */
    public function getInstance(string $className, array $arguments = []): ?object
    {
        $instance = $this->objectManager->create($className, $arguments);

        return is_object($instance) ? $instance : null;
    }

    /**
     * Returns the shared instance of the class.
     *
     * @param string $className
     *
     * @return object|null
     */
    public function getSingleton(string $className): ?object
    {
        $singleton = $this->objectManager->get($className);

        return is_object($singleton) ? $singleton : null;
    }

    /**
     * Creates a new instance of the model class, or returns null if the class is no model.
     *
     * @param string $className
     *
     * @return AbstractModel|null
     */
    public function getModelInstance(string $className): ?AbstractModel
    {
        $instance = $this->getInstance($className);

        if ($instance instanceof AbstractModel) {
            return $instance;
        }

        return null;
    }

    /**
     * Returns the shared instance of the model class, or null if the class is no model.
     *
     * @param string $className
     *
     * @return AbstractModel|null
     */
    public function getModelSingleton(string $className): ?AbstractModel
    {
        $singleton = $this->getSingleton($className);

        if ($singleton instanceof AbstractModel) {
            return $singleton;
        }

        return null;
    }

    /**
     * Creates a new instance of the class.
     *
     * Unlike constructor injection, the class does not have to exist until this method is called, so it can be used for
     * classes of optional modules.
     *
     * The object manager throws a ReflectionException for classes that do not exist, but does not declare it.
     *
     * @template T of object
     *
     * @param string $className
     * @param array  $arguments
     *
     * @phpstan-param class-string<T>      $className
     * @phpstan-param array<string, mixed> $arguments
     *
     * @return T
     * @throws RuntimeException if the class does not exist or the object manager returns an object of another class
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function createInstance(string $className, array $arguments = []): object
    {
        try {
            $instance = $this->objectManager->create($className, $arguments);
        } catch (ReflectionException $exception) {
            throw new RuntimeException($exception->getMessage(), 0, $exception);
        }

        return $this->checkInstance($instance, $className);
    }

    /**
     * Returns the shared instance of the class.
     *
     * Unlike constructor injection, the class does not have to exist until this method is called, so it can be used for
     * classes of optional modules.
     *
     * The object manager throws a ReflectionException for classes that do not exist, but does not declare it.
     *
     * @template T of object
     *
     * @param string $className
     *
     * @phpstan-param class-string<T> $className
     *
     * @return T
     * @throws RuntimeException if the class does not exist or the object manager returns an object of another class
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function getSharedInstance(string $className): object
    {
        try {
            $instance = $this->objectManager->get($className);
        } catch (ReflectionException $exception) {
            throw new RuntimeException($exception->getMessage(), 0, $exception);
        }

        return $this->checkInstance($instance, $className);
    }

    /**
     * Returns the instance if it is an object of the class, or throws an exception otherwise.
     *
     * @template T of object
     *
     * @param mixed  $instance
     * @param string $className
     *
     * @phpstan-param class-string<T> $className
     *
     * @return T
     * @throws RuntimeException
     */
    private function checkInstance(mixed $instance, string $className): object
    {
        if (! $instance instanceof $className) {
            throw new RuntimeException(sprintf('Could not create an instance of class: %s', $className));
        }

        return $instance;
    }
}

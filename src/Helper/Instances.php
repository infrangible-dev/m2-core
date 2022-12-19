<?php

namespace Infrangible\Core\Helper;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\ObjectManagerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Instances
{
    /** @var ObjectManagerInterface */
    protected $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param string $className
     * @param array  $arguments
     *
     * @return object
     */
    public function getInstance(string $className, array $arguments = [])
    {
        $instance = $this->objectManager->create($className, $arguments);

        if ($instance) {
            return $instance;
        }

        return null;
    }

    /**
     * @param string $className
     *
     * @return object
     */
    public function getSingleton(string $className)
    {
        $singleton = $this->objectManager->get($className);

        if ($singleton) {
            return $singleton;
        }

        return null;
    }

    /**
     * @param string $className
     *
     * @return AbstractModel
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
     * @param string $className
     *
     * @return AbstractModel
     */
    public function getModelSingleton(string $className): ?AbstractModel
    {
        $singleton = $this->getSingleton($className);

        if ($singleton instanceof AbstractModel) {
            return $singleton;
        }

        return null;
    }
}

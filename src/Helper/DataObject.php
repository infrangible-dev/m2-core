<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class DataObject extends AbstractHelper
{
    /**
     * Returns the value of the key in the data object; if it has none, the default value is set first.
     *
     * @param \Magento\Framework\DataObject $dataObject
     * @param string $key
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function getOrSetValue(\Magento\Framework\DataObject $dataObject, string $key, mixed $defaultValue): mixed
    {
        if (! $dataObject->hasData($key)) {
            $dataObject->setData(
                $key,
                $defaultValue
            );
        }

        return $dataObject->getData($key);
    }

    /**
     * Returns the value of the key in the data object; if it has none, the result of the callback is set first.
     *
     * @param \Magento\Framework\DataObject $dataObject
     * @param string $key
     * @param callable $closure
     *
     * @return mixed
     */
    public function getOrSetValueCallback(
        \Magento\Framework\DataObject $dataObject,
        string $key,
        callable $closure
    ): mixed {
        if (! $dataObject->hasData($key)) {
            $dataObject->setData(
                $key,
                $closure()
            );
        }

        return $dataObject->getData($key);
    }
}

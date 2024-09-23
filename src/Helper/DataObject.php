<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class DataObject extends AbstractHelper
{
    public function getOrSetValue(\Magento\Framework\DataObject $dataObject, string $key, $defaultValue)
    {
        if (! $dataObject->hasData($key)) {
            $dataObject->setData(
                $key,
                $defaultValue
            );
        }
        return $dataObject->getData($key);
    }

    public function getOrSetValueCallback(\Magento\Framework\DataObject $dataObject, string $key, callable $closure)
    {
        if (! $dataObject->hasData($key)) {
            $dataObject->setData(
                $key,
                $closure()
            );
        }
        return $dataObject->getData($key);
    }
}

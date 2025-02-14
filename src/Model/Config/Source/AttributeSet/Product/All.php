<?php

declare(strict_types=1);

namespace Infrangible\Core\Model\Config\Source\AttributeSet\Product;

use Infrangible\Core\Model\Config\Source\AttributeSet\Product;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class All extends Product
{
    public function toOptionArray(): array
    {
        $this->setAddAllSelect(true);

        return parent::toOptionArray();
    }

    public function toOptions(): array
    {
        $this->setAddAllSelect(true);

        return parent::toOptions();
    }
}

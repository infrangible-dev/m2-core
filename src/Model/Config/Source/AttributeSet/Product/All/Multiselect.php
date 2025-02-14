<?php

declare(strict_types=1);

namespace Infrangible\Core\Model\Config\Source\AttributeSet\Product\All;

use Infrangible\Core\Model\Config\Source\AttributeSet\Product\All;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Multiselect extends All
{
    public function toOptionArray(): array
    {
        $this->setAddPleaseSelect(false);

        return parent::toOptionArray();
    }
}

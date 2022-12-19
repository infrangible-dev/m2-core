<?php

namespace Infrangible\Core\Helper;

use Magento\Framework\Model\AbstractModel;
use Tofex\Help\Variables;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Models
{
    /** @var Variables */
    protected $variables;

    /**
     * @param Variables $variables
     */
    public function __construct(Variables $variables)
    {
        $this->variables = $variables;
    }

    /**
     * @param AbstractModel $object
     *
     * @return array
     */
    public function getChangedAttributeCodes(AbstractModel $object): array
    {
        $oldData = $object->getOrigData();
        $newData = $object->getData();

        return is_array($oldData) && is_array($newData) ? $this->variables->getChangedData($oldData, $newData) : [];
    }
}

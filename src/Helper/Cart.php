<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use FeWeDev\Base\Arrays;
use Magento\Catalog\Model\Product\Option\Type\File;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Cart
{
    /** @var Json */
    protected $serializer;

    /** @var Arrays */
    protected $arrays;

    /** @var RequestInterface */
    protected $request;

    public function __construct(Json $serializer, Arrays $arrays, RequestInterface $request)
    {
        $this->serializer = $serializer;
        $this->arrays = $arrays;
        $this->request = $request;
    }

    /**
     * @throws LocalizedException
     */
    public function addItemCustomOptions(
        \Magento\Checkout\Model\Cart $cart,
        array $cartData
    ): \Magento\Checkout\Model\Cart {
        foreach ($cartData as $itemId => $itemInfo) {
            $item = $cart->getQuote()->getItemById($itemId);

            if (! $item) {
                continue;
            }

            $fileNames = [];

            $requestParams = $this->request->getParams();

            foreach ($requestParams as $requestParamName => $requestParamValue) {
                if (preg_match(
                    sprintf(
                        '/cart_%s_options_([0-9]+)_file_action/',
                        $item->getId()
                    ),
                    $requestParamName,
                    $matches
                )) {
                    $optionId = $matches[ 1 ];

                    $fileNames[ $optionId ] = $requestParamName;
                    $itemInfo[ $requestParamName ] = $requestParamValue;
                }
            }

            $buyRequestOption = $item->getOptionByCode('info_buyRequest');

            $buyRequestData = $buyRequestOption ? $this->serializer->unserialize($buyRequestOption->getValue()) : [];

            $options = $this->arrays->getValue(
                $buyRequestData,
                'options',
                []
            );

            if (array_key_exists(
                'options',
                $itemInfo
            )) {
                foreach ($itemInfo[ 'options' ] as $optionId => $optionValue) {
                    $options[ $optionId ] = $optionValue;
                }
            }

            $buyRequestData[ 'options' ] = $options;

            $buyRequestOption->setValue($this->serializer->serialize($buyRequestData));

            $optionsOption = $item->getOptionByCode('option_ids');

            $optionsOptionValue = $optionsOption && $optionsOption->getValue() ? explode(
                ',',
                $optionsOption->getValue()
            ) : [];

            $product = $item->getProduct();
            $buyRequest = $item->getBuyRequest();

            foreach ($fileNames as $fileName) {
                $buyRequest->setData(
                    $fileName,
                    $this->request->getParam($fileName)
                );
            }

            $optionIds = array_merge(
                array_key_exists(
                    'options',
                    $itemInfo
                ) ? array_keys($itemInfo[ 'options' ]) : [],
                array_keys($fileNames)
            );

            $options = $buyRequest->getData('options');

            foreach ($optionIds as $optionId) {
                $option = $product->getOptionById($optionId);

                $group = $option->groupFactory($option->getType());

                if ($group instanceof File) {
                    $options[ 'files_prefix' ] = sprintf(
                        'cart_%d_',
                        $itemId
                    );
                }

                $group->setOption($option);
                $group->setProduct($product);
                $group->setData(
                    'request',
                    $buyRequest
                );
                $group->setData(
                    'process_mode',
                    AbstractType::PROCESS_MODE_FULL
                );

                $group->validateUserValue($options);

                $preparedValue = $group->prepareForCart();

                if ($preparedValue === null) {
                    continue;
                }

                $item->addOption(
                    [
                        'code'  => sprintf(
                            'option_%d',
                            $optionId
                        ),
                        'value' => $preparedValue
                    ]
                );

                $optionOption = $item->getOptionByCode(
                    sprintf(
                        'option_%d',
                        $optionId
                    )
                );

                $optionOption->setProduct($product);

                if (! in_array(
                    $optionId,
                    $optionsOptionValue
                )) {
                    $optionsOptionValue[] = $optionId;
                }
            }

            $optionsOptionValue = array_unique($optionsOptionValue);
            sort(
                $optionsOptionValue,
                SORT_NUMERIC
            );

            if ($optionsOption) {
                $optionsOption->setValue(
                    implode(
                        ',',
                        $optionsOptionValue
                    )
                );
            } else {
                $item->addOption(
                    [
                        'code'  => 'option_ids',
                        'value' => implode(
                            ',',
                            $optionsOptionValue
                        )
                    ]
                );

                $optionsOption = $item->getOptionByCode('option_ids');

                $optionsOption->setProduct($product);
            }

            $buyRequestOption->setValue($this->serializer->serialize($buyRequest->getData()));
        }

        return $cart;
    }
}

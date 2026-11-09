<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Test\Unit\Model\Config\Source\Payment;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Payment;
use Infrangible\Core\Model\Config\Source\Payment\ActiveMethods;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\MethodInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests the options of the active payment methods.
 */
class ActiveMethodsTest extends TestCase
{
    /**
     * Returns the source with the methods checkmo (no group), and paypal_express, paypal_billing_agreement and
     * braintree in the groups "PayPal" and "Braintree"; checkmo, paypal_express and braintree are active.
     *
     * @return ActiveMethods
     */
    private function createSource(): ActiveMethods
    {
        $paymentData = $this->createStub(Data::class);
        $paymentData->method('getPaymentMethodList')->willReturn([
            'checkmo'   => ['value' => 'checkmo', 'label' => 'Check / Money order'],
            'paypal'    => [
                'label' => 'PayPal',
                'value' => [
                    'paypal_express'           => ['value' => 'paypal_express', 'label' => 'Express Checkout'],
                    'paypal_billing_agreement' => ['value' => 'paypal_billing_agreement', 'label' => 'Agreement']
                ]
            ],
            'braintree' => [
                'label' => 'Braintree',
                'value' => ['braintree' => ['value' => 'braintree', 'label' => 'Credit Card']]
            ],
            'free'      => ['label' => 'Unused', 'value' => ['free' => ['value' => 'free', 'label' => 'Free']]]
        ]);

        $activeMethods = [];

        foreach (['checkmo', 'paypal_express', 'braintree'] as $code) {
            $activeMethods[] = $this->createConfiguredStub(MethodInterface::class, ['getCode' => $code]);
        }

        $paymentHelper = $this->createStub(Payment::class);
        $paymentHelper->method('getActiveMethods')->willReturn($activeMethods);

        $variables = new Variables();

        return new ActiveMethods($paymentData, $paymentHelper, new Arrays($variables), $variables);
    }

    /**
     * The labels of the groups are added only to the methods of their group.
     *
     * @return void
     */
    public function testToOptions(): void
    {
        $this->assertSame(
            [
                'checkmo'        => 'Check / Money order',
                'paypal_express' => 'PayPal - Express Checkout',
                'braintree'      => 'Braintree - Credit Card'
            ],
            $this->createSource()->toOptions()
        );
    }

    /**
     * Inactive methods and groups without active methods are removed from the options for a select field.
     *
     * @return void
     */
    public function testToOptionArray(): void
    {
        $options = $this->createSource()->toOptionArray();

        $emptyOption = $options[ 0 ];
        $this->assertIsArray($emptyOption);
        $this->assertSame('', $emptyOption[ 'value' ]);
        $this->assertSame(
            [
                ['value' => 'checkmo', 'label' => 'Check / Money order'],
                [
                    'label' => 'PayPal',
                    'value' => ['paypal_express' => ['value' => 'paypal_express', 'label' => 'Express Checkout']]
                ],
                [
                    'label' => 'Braintree',
                    'value' => ['braintree' => ['value' => 'braintree', 'label' => 'Credit Card']]
                ]
            ],
            array_slice($options, 1)
        );
    }
}

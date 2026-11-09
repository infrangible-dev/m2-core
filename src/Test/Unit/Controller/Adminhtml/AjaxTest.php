<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Test\Unit\Controller\Adminhtml;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Json;
use FeWeDev\Base\Variables;
use Infrangible\Core\Controller\Adminhtml\Ajax;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Validator\Locale as LocaleValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Tests the JSON responses of Ajax admin controllers and that the admin login and ACL are checked.
 */
class AjaxTest extends TestCase
{
    /** @var array{code: int, body: string} */
    private array $response = ['code' => 200, 'body' => ''];

    /**
     * Returns a controller whose execute() reports success, or throws the given exception.
     *
     * @param bool $isAjax
     * @param bool $isLoggedIn
     * @param bool $isAllowed
     * @param Exception|null $exception
     *
     * @return Ajax
     */
    private function createController(
        bool $isAjax,
        bool $isLoggedIn = true,
        bool $isAllowed = true,
        ?Exception $exception = null
    ): Ajax {
        $request = $this->createStub(HttpRequest::class);
        $request->method('isAjax')->willReturn($isAjax);
        $request->method('isDispatched')->willReturn(true);
        $request->method('getActionName')->willReturn('index');
        $request->method('getFullActionName')->willReturn('test_ajax_index');

        $response = $this->createStub(HttpResponse::class);
        $response->method('setHttpResponseCode')->willReturnCallback(
            function (int $code) use ($response): HttpResponse {
                $this->response[ 'code' ] = $code;

                return $response;
            }
        );
        $response->method('setStatusHeader')->willReturnCallback(
            function (int $code) use ($response): HttpResponse {
                $this->response[ 'code' ] = $code;

                return $response;
            }
        );
        $response->method('getHttpResponseCode')->willReturnCallback(fn(): int => $this->response[ 'code' ]);
        $response->method('setBody')->willReturnCallback(
            function (string $body) use ($response): HttpResponse {
                $this->response[ 'body' ] = $body;

                return $response;
            }
        );

        $authorization = $this->createStub(AuthorizationInterface::class);
        $authorization->method('isAllowed')->willReturn($isAllowed);

        $auth = $this->createStub(Auth::class);
        $auth->method('isLoggedIn')->willReturn($isLoggedIn);
        $auth->method('getAuthStorage')->willReturn($this->createStub(Session::class));

        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getResponse')->willReturn($response);
        $context->method('getAuthorization')->willReturn($authorization);
        $context->method('getAuth')->willReturn($auth);
        $context->method('getActionFlag')->willReturn($this->createStub(ActionFlag::class));
        // deprecated classes are not imported, because PhpStorm does not apply the suppression to a use statement;
        // the view is deprecated, but the backend action of Magento still renders the denied page with it
        /** @noinspection PhpDeprecationInspection, PhpFullyQualifiedNameUsageInspection */
        $context->method('getView')->willReturn($this->createStub(\Magento\Framework\App\ViewInterface::class));
        // deprecated, but the backend action of Magento still builds the login redirect URL with it
        /** @noinspection PhpDeprecationInspection, PhpFullyQualifiedNameUsageInspection */
        $context->method('getHelper')->willReturn($this->createStub(\Magento\Backend\Helper\Data::class));
        // the admin session only stores flags like "is_url_notice" and the locale with magic methods
        $context->method('getSession')->willReturn(new DataObject());
        // the locale settings of the admin user: no locale forced by the request, the default locale of the resolver
        $localeValidator = $this->createStub(LocaleValidator::class);
        $localeValidator->method('isValid')->willReturn(false);
        $objectManager = $this->createStub(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturnMap([[LocaleValidator::class, $localeValidator]]);
        $context->method('getObjectManager')->willReturn($objectManager);
        $localeResolver = $this->createStub(ResolverInterface::class);
        $localeResolver->method('getLocale')->willReturn('en_US');
        $context->method('getLocaleResolver')->willReturn($localeResolver);

        $variables = new Variables();

        return new class(new Arrays($variables), new Json(), $context, new NullLogger(), $exception) extends Ajax {
            /** @var Exception|null */
            private ?Exception $exception;

            /**
             * @param Arrays $arrays
             * @param Json $json
             * @param Context $context
             * @param NullLogger $logging
             * @param Exception|null $exception
             */
            public function __construct(
                Arrays $arrays,
                Json $json,
                Context $context,
                NullLogger $logging,
                ?Exception $exception
            ) {
                parent::__construct($arrays, $json, $context, $logging);

                $this->exception = $exception;
            }

            /**
             * Reports success, or throws the exception.
             *
             * @return ResponseInterface
             * @throws Exception
             */
            public function execute(): ResponseInterface
            {
                if ($this->exception !== null) {
                    throw $this->exception;
                }

                $this->setSuccessResponse('Done');
                $this->addResponseValue('data:count', 2);

                return $this->getResponse();
            }
        };
    }

    /**
     * Dispatches the request; the tests check the response that the controller wrote.
     *
     * @param Ajax $controller
     *
     * @return void
     * @throws NotFoundException
     */
    private function dispatch(Ajax $controller): void
    {
        $controller->dispatch($controller->getRequest());
    }

    /**
     * The response values of an Ajax request are sent as JSON together with the result.
     *
     * @return void
     * @throws NotFoundException
     */
    public function testAjaxRequest(): void
    {
        $this->dispatch($this->createController(true));

        $this->assertSame(200, $this->response[ 'code' ]);
        $this->assertSame(
            ['success' => true, 'message' => 'Done', 'data' => ['count' => 2]],
            json_decode($this->response[ 'body' ], true)
        );
    }

    /**
     * An exception in execute() is sent as error message.
     *
     * @return void
     * @throws NotFoundException
     */
    public function testAjaxRequestWithException(): void
    {
        $this->dispatch($this->createController(true, true, true, new RuntimeException('Failed')));

        $this->assertSame(
            ['success' => false, 'message' => 'Failed'],
            json_decode($this->response[ 'body' ], true)
        );
    }

    /**
     * Without permission for the ACL resource, the controller is not executed and no success is sent.
     *
     * @return void
     * @throws NotFoundException
     */
    public function testAjaxRequestWithoutPermission(): void
    {
        $this->dispatch($this->createController(true, true, false));

        $this->assertSame(403, $this->response[ 'code' ]);
        $this->assertStringNotContainsString('success', $this->response[ 'body' ]);
    }

    /**
     * Without login, the controller is not executed and no success is sent.
     *
     * @return void
     * @throws NotFoundException
     */
    public function testAjaxRequestWithoutLogin(): void
    {
        $this->dispatch($this->createController(true, false, false));

        $this->assertSame(403, $this->response[ 'code' ]);
        $this->assertStringNotContainsString('success', $this->response[ 'body' ]);
    }

    /**
     * Requests that are not Ajax requests are dispatched like other admin requests, without JSON response.
     *
     * @return void
     * @throws NotFoundException
     */
    public function testNonAjaxRequest(): void
    {
        $this->dispatch($this->createController(false));

        $this->assertSame('', $this->response[ 'body' ]);
    }
}

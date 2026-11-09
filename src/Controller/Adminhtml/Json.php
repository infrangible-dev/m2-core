<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Controller\Adminhtml;

use FeWeDev\Base\Arrays;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Base class of admin controllers that answer Ajax requests with JSON. The values added with addResponseValue() in
 * execute() are sent as JSON object. Access is checked like in every admin controller: the user has to be logged in
 * and allowed to use the ACL resource in the constant ADMIN_RESOURCE.
 */
abstract class Json extends Action
{
    /** @var int */
    private int $responseCode = 200;

    /**
     * @var array
     * @phpstan-var array<int|string, mixed>
     */
    private array $responseValues = [];

    /**
     * @param Arrays $arrays
     * @param \FeWeDev\Base\Json $json
     * @param Context $context
     * @param LoggerInterface $logging
     */
    public function __construct(
        protected Arrays $arrays,
        protected \FeWeDev\Base\Json $json,
        Context $context,
        protected LoggerInterface $logging
    ) {
        parent::__construct($context);
    }

    /**
     * Executes the action and sends the response values as JSON, if it is an Ajax request.
     *
     * Requests that the admin does not execute (no login, no permission) get the response of the admin.
     *
     * Magento\Backend\App\AbstractAction::dispatch() does not declare the NotFoundException of the dispatch method
     * of Magento\Framework\App\Action\Action that it calls, so PhpStorm does not see it.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     * @throws NotFoundException
     * @noinspection PhpDocRedundantThrowsInspection
     */
    public function dispatch(RequestInterface $request): ResponseInterface
    {
        $httpRequest = $this->getRequest();

        if (! $httpRequest instanceof Http || ! $httpRequest->isAjax()) {
            return $this->checkResult(parent::dispatch($request));
        }

        try {
            $result = parent::dispatch($request);

            $response = $this->getResponse();

            if (! $httpRequest->isDispatched() ||
                ($response instanceof HttpInterface && $response->getHttpResponseCode() === 403)) {
                return $this->checkResult($result);
            }
        } catch (\Exception $exception) {
            $this->handleException($exception);
        }

        return $this->processResponse($this->getResponseValues());
    }

    /**
     * Returns the response of the result of the parent dispatch method.
     *
     * @param mixed $result
     *
     * @return ResponseInterface
     */
    private function checkResult(mixed $result): ResponseInterface
    {
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        if ($result instanceof ResultInterface) {
            $result->renderResult($this->getResponse());
        }

        return $this->getResponse();
    }

    /**
     * Logs the exception and sets the response code 500.
     *
     * @param \Exception $exception
     *
     * @return void
     */
    protected function handleException(\Throwable $exception): void
    {
        $this->logging->error($exception);

        $this->setResponseCode(500);
    }

    /**
     * Sends the response data as JSON.
     *
     * @param array $responseData
     *
     * @phpstan-param array<int|string, mixed> $responseData
     *
     * @return HttpInterface
     */
    protected function processResponse(array $responseData): HttpInterface
    {
        /** @var \Magento\Framework\App\Response\Http $response */
        $response = $this->getResponse();

        $response->setHttpResponseCode($this->getResponseCode());
        $response->setHeader(
            'Content-type',
            'application/json'
        );

        $response->setBody((string)$this->json->encode($responseData));

        return $response;
    }

    /**
     * Returns the HTTP response code.
     *
     * @return int
     */
    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    /**
     * Sets the HTTP response code.
     *
     * @param int $responseCode
     *
     * @return void
     */
    public function setResponseCode(int $responseCode): void
    {
        $this->responseCode = $responseCode;
    }

    /**
     * Returns the values sent as JSON.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function getResponseValues(): array
    {
        return $this->responseValues;
    }

    /**
     * Returns a value sent as JSON, or null if it was not set.
     *
     * @param string $key
     *
     * @return mixed
     */
    protected function getResponseValue(string $key): mixed
    {
        return $this->responseValues[ $key ] ?? null;
    }

    /**
     * Replaces the values sent as JSON.
     *
     * @param array $responseValues
     *
     * @phpstan-param array<int|string, mixed> $responseValues
     *
     * @return void
     */
    public function setResponseValues(array $responseValues): void
    {
        $this->responseValues = $responseValues;
    }

    /**
     * Adds values sent as JSON.
     *
     * @param array $values
     *
     * @phpstan-param array<int|string, mixed> $values
     *
     * @return void
     */
    protected function addResponseValues(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->addResponseValue(
                (string)$key,
                $value
            );
        }
    }

    /**
     * Adds a value sent as JSON. Keys with colons add the value in nested objects, for example "item:name".
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    protected function addResponseValue(string $key, mixed $value): void
    {
        $keys = explode(
            ':',
            $key
        );

        $this->responseValues = $this->arrays->addDeepValue(
            $this->responseValues,
            $keys,
            $value
        );
    }

    /**
     * Resets the response values.
     *
     * @return void
     */
    protected function resetResponseValues(): void
    {
        $this->responseValues = [];
    }
}

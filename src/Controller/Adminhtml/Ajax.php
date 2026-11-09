<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Controller\Adminhtml;

use Magento\Framework\App\Response\HttpInterface;

/**
 * Base class of admin controllers that answer Ajax requests with a JSON object with the keys "success" and "message".
 */
abstract class Ajax extends Json
{
    /** @var bool */
    private bool $responseResult = true;

    /**
     * Answers with success and the message.
     *
     * @param string $message
     *
     * @return void
     */
    protected function setSuccessResponse(string $message): void
    {
        $this->responseResult = true;
        $this->addResponseValue(
            'message',
            $message
        );
    }

    /**
     * Answers with failure and the message.
     *
     * @param string $message
     *
     * @return void
     */
    protected function setErrorResponse(string $message): void
    {
        $this->responseResult = false;
        $this->addResponseValue(
            'message',
            $message
        );
    }

    /**
     * Answers with failure, the message and the response code 500.
     *
     * @param string $message
     *
     * @return void
     */
    protected function setFatalResponse(string $message): void
    {
        $this->setErrorResponse($message);
        $this->setResponseCode(500);
    }

    /**
     * Logs the exception and answers with failure and the message of the exception.
     *
     * @param \Exception $exception
     *
     * @return void
     */
    protected function handleException(\Exception $exception): void
    {
        $this->logging->error($exception);
        $this->setErrorResponse($exception->getMessage());
    }

    /**
     * Adds the success flag to the response data and sends it as JSON.
     *
     * @param array $responseData
     *
     * @phpstan-param array<int|string, mixed> $responseData
     *
     * @return HttpInterface
     */
    protected function processResponse(array $responseData): HttpInterface
    {
        $responseData = $this->arrays->mergeArrays(
            ['success' => $this->responseResult],
            $responseData
        );

        return parent::processResponse($responseData);
    }
}

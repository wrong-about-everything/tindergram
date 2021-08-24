<?php

declare(strict_types=1);

namespace TG\Infrastructure\Http\Transport;

use Meringue\FormattedInterval\TotalSecondsWithMilliseconds;
use Meringue\ISO8601Interval\WithFixedStartDateTime\FromRange;
use Meringue\Timeline\Point\Now;
use TG\Infrastructure\Http\Request\Outbound\Request;
use TG\Infrastructure\Http\Response\Inbound\Response;
use TG\Infrastructure\Logging\LogItem\InformationMessageWithData;
use TG\Infrastructure\Logging\Logs;

class WithLogging implements HttpTransport
{
    private $transport;
    private $logs;

    public function __construct(HttpTransport $transport, Logs $logs)
    {
        $this->transport = $transport;
        $this->logs = $logs;
    }

    public function response(Request $request): Response
    {
        $this->logs
            ->receive(
                new InformationMessageWithData(
                    'Sending external request to ' . $request->url()->value(),
                    [
                        'method' => $request->method()->value(),
                        'headers' => $request->headers(),
                        'body' => $request->body(),
                    ]
                )
            );

        $everyNow = new Now();
        $response = $this->transport->response($request);

        $andThen = new Now();
        if ($response->isAvailable()) {
            $this->logs
                ->receive(
                    new InformationMessageWithData(
                        'Response from external service',
                        [
                            'duration' =>
                                sprintf(
                                    '%s seconds',
                                    (new TotalSecondsWithMilliseconds(new FromRange($everyNow, $andThen)))->value()
                                ),
                            'code' => $response->code(),
                            'headers' => $response->headers(),
                            'body' => $response->body(),
                        ]
                    )
                );
        } else {
            $this->logs
                ->receive(
                    new InformationMessageWithData(
                        'Response from external service was not obtained',
                        [
                            'duration' =>
                                sprintf(
                                    '%s seconds',
                                    (new TotalSecondsWithMilliseconds(new FromRange($everyNow, $andThen)))->value()
                                ),
                        ]
                    )
                );
        }

        return $response;
    }
}

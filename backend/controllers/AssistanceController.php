<?php

declare(strict_types=1);

namespace Cidb\Backend\Controllers;

use Cidb\Backend\Services\AssistanceRequestService;

final class AssistanceController extends AbstractController
{
    public function __construct(
        private readonly AssistanceRequestService $assistanceRequestService
    ) {
    }

    public function submit(array $request): array
    {
        $payload = $this->payload($request);
        $result = $this->assistanceRequestService->submit($payload);

        return $this->success($result, 'Assistance request submitted.', 201);
    }
}

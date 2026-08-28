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
        $assistanceRequest = $this->assistanceRequestService->submit($payload);

        return $this->success(
            $this->present($assistanceRequest),
            'Assistance request submitted.',
            201
        );
    }

    public function show(array $request): array
    {
        $id = $this->routeParam($request, 'id');
        $assistanceRequest = $this->assistanceRequestService->status($id);

        return $this->success($this->present($assistanceRequest), 'Assistance request retrieved.');
    }

    public function retry(array $request): array
    {
        $id = $this->routeParam($request, 'id');
        $assistanceRequest = $this->assistanceRequestService->retry($id);

        return $this->success($this->present($assistanceRequest), 'Assistance request retry completed.');
    }

    /**
     * @param array<string, mixed> $assistanceRequest
     * @return array<string, mixed>
     */
    private function present(array $assistanceRequest): array
    {
        return [
            'assistance_request' => $assistanceRequest,
            'rpa_status' => $assistanceRequest['rpa_status'] ?? null,
            'case_reference_no' => $assistanceRequest['case_reference_no'] ?? null,
            'display_message' => $assistanceRequest['rpa_display_message'] ?? null,
            'next_action' => $assistanceRequest['next_action'] ?? 'done',
        ];
    }
}

<?php

declare(strict_types=1);

use Cidb\Backend\Controllers\DocumentController;
use Cidb\Backend\Controllers\SessionController;
use Cidb\Backend\Controllers\SignatureController;
use Cidb\Backend\Controllers\SubmissionController;

return [
    [
        'method' => 'POST',
        'path' => '/session/start',
        'controller' => SessionController::class,
        'action' => 'start',
    ],
    [
        'method' => 'POST',
        'path' => '/session/language',
        'controller' => SessionController::class,
        'action' => 'language',
    ],
    [
        'method' => 'POST',
        'path' => '/session/service',
        'controller' => SessionController::class,
        'action' => 'service',
    ],
    [
        'method' => 'POST',
        'path' => '/session/state',
        'controller' => SessionController::class,
        'action' => 'state',
    ],
    [
        'method' => 'POST',
        'path' => '/session/name',
        'controller' => SessionController::class,
        'action' => 'name',
    ],
    [
        'method' => 'POST',
        'path' => '/session/identity',
        'controller' => SessionController::class,
        'action' => 'identity',
    ],
    [
        'method' => 'POST',
        'path' => '/session/mobile',
        'controller' => SessionController::class,
        'action' => 'mobile',
    ],
    [
        'method' => 'POST',
        'path' => '/session/email',
        'controller' => SessionController::class,
        'action' => 'email',
    ],
    [
        'method' => 'POST',
        'path' => '/session/company-ppk',
        'controller' => SessionController::class,
        'action' => 'companyPpk',
    ],
    [
        'method' => 'POST',
        'path' => '/session/company-name',
        'controller' => SessionController::class,
        'action' => 'companyName',
    ],
    [
        'method' => 'POST',
        'path' => '/session/company-email',
        'controller' => SessionController::class,
        'action' => 'companyEmail',
    ],
    [
        'method' => 'POST',
        'path' => '/session/company-category',
        'controller' => SessionController::class,
        'action' => 'companyCategory',
    ],
    [
        'method' => 'POST',
        'path' => '/session/company-director-name',
        'controller' => SessionController::class,
        'action' => 'companyDirectorName',
    ],
    [
        'method' => 'POST',
        'path' => '/session/company-director-identity',
        'controller' => SessionController::class,
        'action' => 'companyDirectorIdentity',
    ],
    [
        'method' => 'POST',
        'path' => '/session/company-reason',
        'controller' => SessionController::class,
        'action' => 'companyReason',
    ],
    [
        'method' => 'GET',
        'path' => '/session/{id}',
        'controller' => SessionController::class,
        'action' => 'show',
    ],
    [
        'method' => 'POST',
        'path' => '/documents/upload',
        'controller' => DocumentController::class,
        'action' => 'upload',
    ],
    [
        'method' => 'POST',
        'path' => '/signature/upload',
        'controller' => SignatureController::class,
        'action' => 'upload',
    ],
    [
        'method' => 'POST',
        'path' => '/submission',
        'controller' => SubmissionController::class,
        'action' => 'submit',
    ],
    [
        'method' => 'GET',
        'path' => '/submission/{id}',
        'controller' => SubmissionController::class,
        'action' => 'show',
    ],
];

<?php

declare(strict_types=1);

use Cidb\Backend\Controllers\AssistanceController;
use Cidb\Backend\Controllers\DocumentController;
use Cidb\Backend\Controllers\FaqController;
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
        'path' => '/session/identity-edit',
        'controller' => SessionController::class,
        'action' => 'identityEdit',
    ],
    [
        'method' => 'POST',
        'path' => '/session/retry-edit',
        'controller' => SessionController::class,
        'action' => 'retryEdit',
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
        'path' => '/session/company-contact',
        'controller' => SessionController::class,
        'action' => 'companyContact',
    ],
    [
        'method' => 'POST',
        'path' => '/session/company-state',
        'controller' => SessionController::class,
        'action' => 'companyState',
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
        'method' => 'POST',
        'path' => '/session/faq-topic',
        'controller' => SessionController::class,
        'action' => 'faqTopic',
    ],
    [
        'method' => 'POST',
        'path' => '/session/faq-subtopic',
        'controller' => SessionController::class,
        'action' => 'faqSubtopic',
    ],
    [
        'method' => 'GET',
        'path' => '/session/{id}',
        'controller' => SessionController::class,
        'action' => 'show',
    ],
    [
        'method' => 'GET',
        'path' => '/faq/topics',
        'controller' => FaqController::class,
        'action' => 'topics',
    ],
    [
        'method' => 'GET',
        'path' => '/faq/topics/{topicCode}/subtopics',
        'controller' => FaqController::class,
        'action' => 'subtopics',
    ],
    [
        'method' => 'GET',
        'path' => '/faq/subtopics/{subtopicCode}/questions',
        'controller' => FaqController::class,
        'action' => 'questions',
    ],
    [
        'method' => 'GET',
        'path' => '/faq/search',
        'controller' => FaqController::class,
        'action' => 'search',
    ],
    [
        'method' => 'POST',
        'path' => '/documents/upload',
        'controller' => DocumentController::class,
        'action' => 'upload',
    ],
    [
        'method' => 'POST',
        'path' => '/assistance/submit',
        'controller' => AssistanceController::class,
        'action' => 'submit',
    ],
    [
        'method' => 'GET',
        'path' => '/assistance/{id}',
        'controller' => AssistanceController::class,
        'action' => 'show',
    ],
    [
        'method' => 'POST',
        'path' => '/assistance/{id}/retry',
        'controller' => AssistanceController::class,
        'action' => 'retry',
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
        'method' => 'POST',
        'path' => '/submission/{id}/retry',
        'controller' => SubmissionController::class,
        'action' => 'retry',
    ],
    [
        'method' => 'GET',
        'path' => '/submission/{id}',
        'controller' => SubmissionController::class,
        'action' => 'show',
    ],
];

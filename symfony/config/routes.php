<?php

use ItkDev\TidyFeedbackBundle\Controller\TidyFeedbackController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->add('tidy_feedback_index', '/')
        ->controller([TidyFeedbackController::class, 'index'])
        ->methods([Request::METHOD_GET]);

    $routes->add('tidy_feedback_create', '/')
        ->controller([TidyFeedbackController::class, 'post'])
        ->methods([Request::METHOD_POST]);

    $routes->add('tidy_feedback_asset', '/asset/{asset}')
        ->defaults(['resource' => null])
        ->controller([TidyFeedbackController::class, 'asset'])
        ->methods([Request::METHOD_GET]);

    $routes->add('tidy_feedback_show', '/{id}')
        ->controller([TidyFeedbackController::class, 'show'])
        ->methods([Request::METHOD_GET]);

    $routes->add('tidy_feedback_image', '/{id}/image')
        ->controller([TidyFeedbackController::class, 'image'])
        ->methods([Request::METHOD_GET]);
};

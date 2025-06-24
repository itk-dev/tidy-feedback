<?php


use ItkDev\TidyFeedbackBundle\Controller\TidyFeedbackApiController;
use ItkDev\TidyFeedbackBundle\Controller\TidyFeedbackController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->add('tidy_feedback_api_create', '/api/')
        // the controller value has the format [controller_class, method_name]
        ->controller([TidyFeedbackApiController::class, 'post'])
        ->methods([Request::METHOD_POST]);

    $routes->add('tidy_feedback_index', '/')
        ->controller([TidyFeedbackController::class, 'index'])
        ->methods([Request::METHOD_GET]);

    $routes->add('tidy_feedback_new', '/new')
        ->controller([TidyFeedbackController::class, 'new'])
        ->methods([Request::METHOD_GET, Request::METHOD_POST]);

    $routes->add('tidy_feedback_widget', '/widget/{resource}')
        ->defaults(['resource' => null])
        ->controller([TidyFeedbackController::class, 'widget'])
        ->methods([Request::METHOD_GET]);

    $routes->add('tidy_feedback_create', '/')
        ->controller([TidyFeedbackController::class, 'create'])
        ->methods([Request::METHOD_POST]);

    $routes->add('tidy_feedback_show', '/{id}')
        ->controller([TidyFeedbackController::class, 'show'])
        ->methods([Request::METHOD_GET]);

    $routes->add('tidy_feedback_show_image', '/{id}/image')
        ->controller([TidyFeedbackController::class, 'showImage'])
        ->methods([Request::METHOD_GET]);
};

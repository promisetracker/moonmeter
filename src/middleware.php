<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);

$app->add(function ($request, $response, $next) {
    try {
        $response = $next($request, $response);
    }
    catch(Slim\Exception\NotFoundException $e) {
        $notFoundHandler = $this->get('notFoundHandler');
        return $notFoundHandler($request->withAttribute('message', $e->getMessage()), $response);
    }
    return $response;
});
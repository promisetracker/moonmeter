<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\Twig;

// Routes
$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
	$name = !empty($args['name']) ? $args['name'] : '';
	$this->logger->notice($name);
    // Render index view
    return $this->view->render($response, 'index.twig', $args);
});

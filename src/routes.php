<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\Twig;

// 초기 화면
$app->get('/', function (Request $request, Response $response, array $args) {

    // Render index view
    $visions = $this->db->table('p_4vision')->get();
    $promises = $this->db->table('p_12promise')->get();
    $categories = $this->db->table('p_12promise_category')->get();
    $activePromises = $this->db->table('sub_promise')->where('promise_level', '<>', '0')->orderBy('promise_level', 'desc')->get();

    $status = $this->common->status;

    $args += [
    	'visions' => $visions,
    	'promises' => $promises,
    	'categories' => $categories,
    	'activePromises' => $activePromises,
    	'status' => $status
    ];
    return $this->view->render($response, 'index.twig', $args);
})->setName('front');

// 소개
$app->get('/about', function (Request $request, Response $response, array$args) {
	return $this->view->render($response, 'about.twig', $args);
})->setName('about');

// 공약
$app->get('/promises', function (Request $request, Response $response, array$args) {
    $groups = $this->common->get_promise_status();
    $full_promises = $this->common->get_full_promises();
    $full_promises_array = $this->common->array_group_by($full_promises, 'pv_title', 'pp_title', 'ppk_title', 'mp_title');
    $this->logger->info(print_r($full_promises_array, true));
	$args = [
        'total_promises_count' => $this->common->total_promises_count,
        'groups' => $groups,
        'full_promises' => $full_promises_array
	];
	return $this->view->render($response, 'promises.twig', $args);
})->setName('promises');

// 도와주세요?
$app->get('/help', function (Request $request, Response $response, array$args) {
	return $this->view->render($response, 'help.twig', $args);
})->setName('help');

// 공약 상세
$app->get('/promise/{id}', function (Request $request, Response $response, array$args) {

	$id = $args['id'];
	$status = $this->common->status;
	$priomise = [];

	try {
    	$promise = $this->common->get_the_promise($id);
    	if (empty($promise)) {
    		throw new \Slim\Exception\NotFoundException($request, $response);
    	}
    	$note = $this->common->get_promise_note($id);
    	$articles = $this->common->get_promise_related_articles($id)->all();
    }
    catch(Slim\Exception\NotFoundException $e) {
    	throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $groups = $this->common->get_promise_status();
    
    $args = [
        'total_promises_count' => $this->common->total_promises_count,
		'promise' => $promise,
		'status' => $status,
		'note' => $note,
		'articles' => $articles,
        'groups' => $groups
	];

	return $this->view->render($response, 'promise.twig', $args);
})->setName('promises');


<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\Twig;

// 초기 화면
$app->get('/', function (Request $request, Response $response, array $args) {

    // Render index view
    $activePromises = $this->db->table('sub_promise')->where('promise_level', '<>', '0')->orderBy('promise_level', 'desc')->offset(0)->limit(5)->get();

    $status = $this->common->status;
    $groups = $this->common->get_promise_status();
    $notices = $this->common->get_recent_notices();

    $args += [
    	'activePromises' => $activePromises,
    	'status' => $status,
        'groups' => $groups,
        'notices' => $notices
    ];
    return $this->view->render($response, 'index.twig', $args);
})->setName('front');

// 소개
$app->get('/about', function (Request $request, Response $response, array $args) {
	return $this->view->render($response, 'about.twig', $args);
})->setName('about');

// 문재인 공약
$app->get('/promises', function (Request $request, Response $response, array $args) {
    $groups = $this->common->get_promise_status();
    $full_promises = $this->common->get_full_promises();
    $full_promises_array = $this->common->array_group_by($full_promises, 'pv_title', 'pp_title', 'ppk_title', 'mp_title');
    $notices = $this->common->get_recent_notices();
	$args = [
        'total_promises_count' => $this->common->total_promises_count,
        'groups' => $groups,
        'full_promises' => $full_promises_array,
        'notices' => $notices
	];
	return $this->view->render($response, 'promises.twig', $args);
})->setName('promises');

// 분류별 공약
$app->get('/promises/t/{type}[/{term_id}]', function (Request $request, Response $response, array $args) {
    
    $taxonomy_types = $this->common->taxonomy_types;
    $status = $this->common->status;

    $types = array_keys($taxonomy_types);
    $type = $args['type'] ?? 'category';

    $term_id = $args['term_id'] ?? '';
    $current_taxonomy_type = '';
    
    $terms = [];
    $term_ids = [];
    $promises = [];

    if (!in_array($type, $types)) {
        throw new \Slim\Exception\NotFoundException($request, $response);
    } else {
        $current_taxonomy_type = $taxonomy_types[$type];
        $terms = $this->common->get_current_taxonomy_terms($type);
        $term_ids = $this->common->get_current_taxonomy_terms($type, true);
        if (!in_array($term_id, $term_ids)) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        } else {
            $promises = $this->common->get_promises_by_type_term($type, $term_id);
        }
    }

    $groups = $this->common->get_promise_status();
    $notices = $this->common->get_recent_notices();
    $args = [
        'type' => $type,
        'term_id' => $term_id,
        'current_taxonomy_type' => $current_taxonomy_type,
        'terms' => $terms,
        'promises' => $promises,
        'status' => $status,
        'groups' => $groups,
        'notices' => $notices
    ];
    return $this->view->render($response, 'promises-by-type.twig', $args);
})->setName('promises_by_type');


// 문재인 공약 비전 상세
$app->get('/promises/v/{vision_id}', function (Request $request, Response $response, array $args) {

    $vision_id = $args['vision_id'];
    $groups = $this->common->get_promise_status();
    $notices = $this->common->get_recent_notices();

    try {
        $promises = $this->common->get_full_promises($vision_id);
        $full_promises_array = $this->common->array_group_by($promises, 'pv_title', 'pp_title', 'ppk_title', 'mp_title');
        if (empty($promises)) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }
    }
    catch(Slim\Exception\NotFoundException $e) {
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $args = [
        'promises' => $full_promises_array,
        'vision_id' => $vision_id,
        'groups' => $groups,
        'notices' => $notices
    ];
    return $this->view->render($response, 'promises-by-vision.twig', $args);
});

// 도와주세요?
$app->get('/help', function (Request $request, Response $response, array $args) {
	return $this->view->render($response, 'help.twig', $args);
})->setName('help');

// 공약 상세
$app->get('/promise/{id}', function (Request $request, Response $response, array $args) {

	$id = $args['id'];
	$status = $this->common->status;
    $groups = $this->common->get_promise_status();
    $notices = $this->common->get_recent_notices();
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
    
    $args = [
		'promise' => $promise,
		'status' => $status,
		'note' => $note,
		'articles' => $articles,
        'groups' => $groups,
        'notices' => $notices
	];

	return $this->view->render($response, 'promise.twig', $args);
})->setName('promises');


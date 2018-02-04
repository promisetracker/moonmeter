<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\Twig;

// Routes
$app->get('/', function (Request $request, Response $response, array $args) {
	$name = !empty($args['name']) ? $args['name'] : '';
	$this->logger->notice($name);
    // Render index view
    $visions = $this->db->table('p_4vision')->get();
    $promises = $this->db->table('p_12promise')->get();
    $categories = $this->db->table('p_12promise_category')->get();
    $test = $promises->groupBY('pv_no');
    $activePromises = $this->db->table('sub_promise')->where('promise_level', '<>', '0')->orderBy('promise_level', 'desc')->get();

    $status = [
    	"평가안됨(준비안됨)",
    	"시작안함",
    	"진행중",
    	"변경(공약)",
    	"지체(정체)",
    	"파기",
    	"공약 이행(완료)",
    ];
    
    $this->logger->debug($activePromises);
    $args += [
    	'visions' => $visions,
    	'promises' => $promises,
    	'categories' => $categories,
    	'activePromises' => $activePromises,
    	'status' => $status
    ];
    return $this->view->render($response, 'index.twig', $args);
});

$app->get('/about', function (Request $request, Response $response, array$args) {
	return $this->view->render($response, 'about.twig', $args);
});
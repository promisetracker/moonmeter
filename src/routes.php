<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\Twig;

// 초기 화면
$app->get('/', function (Request $request, Response $response, array $args) {

    $this->common->sync_disqus_popular_posts();

    $hot_promises = $this->common->get_disqus_popular_posts();
    $popular_promises = $this->common->get_hot_promises();

    $status = $this->common->status;
    $groups = $this->common->get_promise_status();
    $notices = $this->common->get_recent_notices();

    $args += [
    	'hot_promises' => $hot_promises,
        'popular_promises' => $popular_promises,
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
        $this->common->increase_promise_view_count($id);
    }
    catch(Slim\Exception\NotFoundException $e) {
    	throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $args = [
        'id' => $id,
		'promise' => $promise,
		'status' => $status,
		'note' => $note,
		'articles' => $articles,
        'groups' => $groups,
        'notices' => $notices
	];

	return $this->view->render($response, 'promise.twig', $args);
})->setName('promises');

// 공지 상세
$app->get('/notice/{id}', function (Request $request, Response $response, array $args) {

    $id = $args['id'];
    $status = $this->common->status;
    $groups = $this->common->get_promise_status();
    $notices = $this->common->get_recent_notices();
    $notice = [];

    try {
        $notice = $this->common->get_the_notice($id);
        if (empty($notice)) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }
    }
    catch(Slim\Exception\NotFoundException $e) {
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $args = [
        'status' => $status,
        'groups' => $groups,
        'notices' => $notices,
        'notice' => $notice,
    ];

    return $this->view->render($response, 'notice.twig', $args);
})->setName('promises');

// 검색
$app->get('/search', function (Request $request, Response $response, array $args) {

    $groups = $this->common->get_promise_status();
    $notices = $this->common->get_recent_notices();

    $hierarchy = $this->common->hierarchy;

    $keyword = $request->getQueryParam('keyword') ?? '';
    $type = $request->getQueryParam('hierarchy') ?? 'all';
    $currentPage = $request->getQueryParam('page') ?? 1;
    $all_params = $request->getQueryParams();
    unset($all_params['page']);
    $current_url = '/search?' . http_build_query($all_params);

    \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
        return $currentPage;
    });

    $results = $this->db->table('full_promise');
    if (in_array($type, $hierarchy)) {
        $results = $results->where(function($query) use ($type, $keyword) {
            $query->where($type, 'LIKE', '%' . $keyword . '%');
        })->paginate(15);
    } else {
        $results = $results->where(function($query) use ($keyword) {
            $query->where('pv_title', 'LIKE', '%' . $keyword . '%')
            ->orWhere('pp_title', 'LIKE', '%' . $keyword . '%')
            ->orWhere('ppk_title', 'LIKE', '%' . $keyword . '%')
            ->orWhere('mp_title', 'LIKE', '%' . $keyword . '%')
            ->orWhere('sp_title', 'LIKE', '%' . $keyword . '%');
        })->paginate(15);
    }
    $results->setPath($current_url);

    $view = $this->view;
    $view->getEnvironment()->addTest(new Twig_SimpleTest('string', function($value) {
        return is_string($value);
    }));

    \Illuminate\Pagination\Paginator::viewFactoryResolver(function() use ($view) {
        return new class($view) {
            private $view, $template, $data;

            public function __construct(\Slim\Views\Twig $view) {
                $this->view = $view;
            }

            public function make(string $template, $data = null) {
                $this->template = $template;
                $this->data = $data;
                return $this;
            }

            public function render() {
                return $this->view->fetch($this->template, $this->data);
            }
        };
    });

    $args = [
        'groups' => $groups,
        'notices' => $notices,
        'hierarchy' => $hierarchy,
        'current_type' => $type,
        'keyword' => $keyword,
        'results' => $results,
    ];
    return $this->view->render($response, 'search.twig', $args);
})->setName('search');



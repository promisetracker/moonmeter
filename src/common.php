<?php

use Interop\Container\ContainerInterface;
use Illuminate\Database\Capsule\Manager as DB;

class Common {
    protected $ci;
    // 공약 상태 코드
    public $status = [
        '평가안됨',
        '시작안함',
        '진행중',
        '변경',
        '지체',
        '파기',
        '완료'
    ];
    public $total_promises_count = 888;
    // 공약 분류 형식
    public $taxonomy_types = [
        'category' => '분류',
        'theme' => '테마',
        'hierarchy' => '수혜 계층'
    ];
    // 공약 위계
    public $hierarchy = [
        '4대 비전' => 'pv_title',
        '12대 공약' => 'pp_title',
        '주제' => 'ppk_title',
        '메인 약속' => 'mp_title',
        '세부 약속' => 'sp_title'
    ];
  
    //Constructor
    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    public function get_the_promise($id) {
        return $this->ci->db->table('full_promise')->where('sp_no', $id)->get()->first();
    }

    public function get_promise_note($id) {
        return $this->ci->db->table('factcheck_desc')->where('sp_no', $id)->get()->first();
    }

    public function get_promise_related_articles($id) {
        return $this->ci->db->table('related_news')->where('sp_no', $id)->get();
    }

    public function get_promise_status() {
        $results = $this->ci->db->table('sub_promise')
                ->select('promise_level', DB::raw('count(*) as total'))
                ->groupBy('promise_level')
                ->get()->toArray();
        $status = $this->status;
        $groups = [];
        foreach ($status as $key => $item) {
            $match = array_search($key, array_column($results, 'promise_level'));
            if ($match !== false) {
                $groups[] = [
                    'title' => $item,
                    'total' => $results[$match]->total,
                    'percent' => ($results[$match]->total / $this->total_promises_count) * 100
                ];
            } else {
                $groups[] = [
                    'title' => $item,
                    'total' => 0,
                    'percent' => 0
                ];
            }
        }
        return $groups;
    }

    public function get_full_promises($vision_id = '') {
        $promises = [];
        if (empty($vision_id)) {
            $promises = $this->ci->db->table('full_promise')->get()->toArray();
        } else {
            $promises = $this->ci->db->table('full_promise')->where('pv_no', $vision_id)->get()->toArray();
        }
        return $promises;
    }

    public function array_group_by(array $array, $key) {
        if (!is_string($key) && !is_int($key) && !is_float($key) && !is_callable($key) ) {
            trigger_error('array_group_by(): The key should be a string, an integer, or a callback', E_USER_ERROR);
            return null;
        }

        $func = (!is_string($key) && is_callable($key) ? $key : null);
        $_key = $key;

        // Load the new array, splitting by the target key
        $grouped = [];
        foreach ($array as $value) {
            $key = null;

            if (is_callable($func)) {
                $key = call_user_func($func, $value);
            } elseif (is_object($value) && isset($value->{$_key})) {
                $key = $value->{$_key};
            } elseif (isset($value[$_key])) {
                $key = $value[$_key];
            }

            if ($key === null) {
                continue;
            }

            $grouped[$key][] = $value;
        }

        // Recursively build a nested grouping if more parameters are supplied
        // Each grouped array value is grouped according to the next sequential key
        if (func_num_args() > 2) {
            $args = func_get_args();

            foreach ($grouped as $key => $value) {
                $params = array_merge([ $value ], array_slice($args, 2, func_num_args()));
                $grouped[$key] = call_user_func_array(array($this, 'array_group_by'), $params);
            }
        }

        return $grouped;
    }

    public function get_recent_notices() {
        return $this->ci->db->table('notice')->orderBy('regdate', 'desc')->limit(5)->get()->toArray();
    }

    public function get_current_taxonomy_terms($taxonomy, $only_id = '') {
        $taxonomy_table = $taxonomy . '_sub_promise';
        $field_name = $taxonomy[0] . 'sp_no';
        $results = $this->ci->db->table($taxonomy_table)->get()->toArray();
        if ($only_id) {
            $results = $this->ci->db->table($taxonomy_table)->pluck($field_name)->toArray();
        }
        return $results;
    }

    public function get_promises_by_type_term($type, $term_id) {
        $table = 'sub_promise_' . $type;
        $ids = $this->ci->db->table($table)->where($type[0] . 'sp_no', $term_id)->pluck('sp_no')->toArray();
        return $this->ci->db->table('full_promise')->whereIn('sp_no', $ids)->get()->toArray();
    }

    public function get_the_notice($id) {
        return $this->ci->db->table('notice')->where('n_no', $id)->get()->first();
    }

    public function increase_promise_view_count($sp_no) {
        $this->ci->db->table('sub_promise')->where('sp_no', $sp_no)->increment('view_count');
    }

    public function get_hot_promises() {
        return $this->ci->db->table('sub_promise')
            ->where('sub_promise.promise_level', '<>', '0')->orderBy('view_count', 'desc')->offset(0)->limit(5)->get();
    }

    public function sync_disqus_popular_posts() {
        $disqus = $this->ci->get('settings')['disqus'];
        $disqus['api_key'] = getenv('DISQUS_API_KEY');
        
        $query = http_build_query($disqus);
        $disqus_popular_url = getenv('DISQUS_POPULAR_URL');
        $disqus_api_request = $disqus_popular_url . '?' . $query;

        $this->ci->logger->notice($disqus_api_request);

        $disqus_cache_file = $this->ci->get('settings')['view']['twig']['cache'] . '/disqus_popular_posts.json';
        $disqus_cache = '';

        if (!file_exists($disqus_cache_file)) {
            file_put_contents($disqus_cache_file, file_get_contents($disqus_api_request));
        } else {
            $disqus_cache = file_get_contents($disqus_cache_file);
            $disqus_api_response = file_get_contents($disqus_api_request);
            if ($disqus_cache == $disqus_api_response) {
                $this->ci->logger->info('[disqus sync] Same... ');
                return;
            } else {
                file_put_contents($disqus_cache_file, $disqus_api_response);
            }
        }

        $disqus_cache = file_get_contents($disqus_cache_file);
        $results = json_decode($disqus_cache);
        $posts = $results->response;
        $this->ci->logger->info(print_r($posts, true));

        if (count($posts) > 0) {
            $item = '';
            $this->ci->db->table('disqus_popular_posts')->truncate();
            foreach ($posts as $key => $post) {
                $path = parse_url($post->link, PHP_URL_PATH);
                $pathes = explode('/', $path);
                $id = end($pathes);
                $item = [
                    'sp_no' => $id,
                    'count' => $post->posts
                ];
                $this->ci->db->table('disqus_popular_posts')->insert($item);
            }
        }
    }

    public function get_disqus_popular_posts() {
        return $this->ci->db->table('sub_promise')
            ->select('sub_promise.*', 'disqus_popular_posts.count')
            ->join('disqus_popular_posts', 'disqus_popular_posts.sp_no', '=', 'sub_promise.sp_no')
            ->orderBy('disqus_popular_posts.count', 'desc')->offset(0)->limit(5)->get();
    }
    
}
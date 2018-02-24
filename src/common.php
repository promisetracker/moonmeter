<?php

use Interop\Container\ContainerInterface;
use Illuminate\Database\Capsule\Manager as DB;

class Common {
    protected $ci;
    public $status = [
        "평가안됨",
        "시작안함",
        "진행중",
        "변경",
        "지체",
        "파기",
        "완료"
    ];
    public $total_promises_count = 888;
  
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
                $this->ci->logger->notice($match);
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

    public function get_full_promises() {
        return $this->ci->db->table('full_promise')->get()->toArray();
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

}
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
        // return $results;
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

}
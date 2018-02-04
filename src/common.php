<?php

use Interop\Container\ContainerInterface;

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
  
    //Constructor
    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    public function get_the_promise($id) {
        return $this->ci->db->table('full_promise')->where('sp_no', $id)->get()->first();
    }

}
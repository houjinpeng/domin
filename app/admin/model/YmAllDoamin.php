<?php

namespace app\admin\model;


use app\common\model\TimeModel;

class YmAllDoamin extends TimeModel
{


    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->name = 'all_domain_' . date('Ym');
    }

    public function setMonth($month)
    {
        $this->name = 'all_domain_' . $month;
        return $this;
    }

}
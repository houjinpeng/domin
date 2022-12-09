<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class Mongo extends TimeModel
{

    protected $connection = 'mongo';

    protected $name = "tasks";


}
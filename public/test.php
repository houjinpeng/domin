<?php


$a = [array('age'=>1),array('age'=>11),array('age'=>4),array('age'=>3)];
foreach ($a as $k=>$v){
    echo $v['age'].'</br>';
}


echo '==================='.'</br>';
krsort($a);

foreach ($a as $k=>$v){
    echo $v['age'].'</br>';
}


echo '==================='.'</br>';


$last_ages =array_column($a,'age');
array_multisort($last_ages ,SORT_DESC,$a);

foreach ($a as $k=>$v){
    echo $v['age'].'</br>';
}


<?php

include_once '../dist/Tpl-dist.php';


$a = ['b'=>['c'=>'d']];

echo Tpl::get('error4.html', ['a'=>$a]);
<?php
include '../dist/Tpl-dist.php';
const MY_CONST = 111;
const MY_CONST_ARRAY= ['a'=>1, 'b'=> 2, 'c'=>3];

//echo function_exists('count');
//echo function_exists('isset');

$me = 'I\'m global';


$countries = [
	[
		'name'=>'South Korea',
		'pop'=> 50,
		'cities'=>[
			[
				'name'=> 'Seoul',
				'pop' => 10,
			],
			[
				'name'=> 'Sejong',
				'pop' => 0.3,

			]
		]
	],
	[
		'name'=>'Republic of Maldives',
		'pop'=> 0.4,
		'cities'=>[
			[
				'name'=> 'Male',
				'pop' => 0.15,
			]
		]
	]
];
class xxx {
	public function bar() {
		return 'return from method.';
	}
}
class zzz {
	public function baz() {
		return new xxx();
	}
}
function bar() {
	return 'good good';
}
class Product {
	private $name;
	private $code;
	private $price;
	function __construct($name, $code, $price) {	
		$this->name = $name;
		$this->code = $code;
		$this->price = $price;
	}
	function __call($method, $args=[]) { 
		return $this->{$method} ?? ''; 
	}
	function price($currency='$') {
		return $currency.number_format($this->price,2); 
	}
}

class Foo {
	const ITEMS_PER_PAGE = 30;
	public static function bar() {
		return 'from static method.';
	}
}

$products = [
	new Product('vitamin', '001', 100), 
	new Product('shoes', '002', 123)
];

include 'hello.nsBar.php';
include 'hello.nsWidget.php';

$sub = Tpl::get('sub.html', ['fooo'=>[8,9,10,11,12,13]]);

$var = [
	'header-title' => 'this is header file',
 ];

$path=[
	'upload'=>'/upload',
];

$user2 = new stdClass();
$user2->name	= "Kitty";
$user2->age		= 25;
$user2->hobbies	= ["first"=>"running", "second"=>"music"];

$vals = [
	'path' => $path,
	'var' => $var,
	'content' => 'hello world',
	'a' => 10,
	'b' => 20,
	'foo'	=> 'hello~',
	'bar'	=> 'bbb',
	'caz'	=> 'zzz',
	'countries' => $countries,
	'foo1'	=>[1, 2],
	'bar1'	=>[3, 4, 5],
	'foo2'	=>true,
	'foo3'	=>null,
	'bar2'	=>'Tplus if',
	'bar3'	=>'ternary operator',
	'fooo'	=>[1,2,3,4,5],
	'user'	=>["name"=>"Cratee", "age"=>56, "hobbies"=>["first"=>"Piano", "second"=>"running"]],
	'user2'	=>$user2,
	'xx'=> new xxx(),
	'yy'=> ['baz'=>'from array'],
	'zz'=> new zzz(),
	'product' => $products,
	'sub'=>'sub.html',
	'sub2'=>$sub,
	'pants'=>'jeans',
	'fruit'=>'lemon',
	'article' => "a < b \n b > c",
];

$totalStart = microtime(true);
echo Tpl::get('index.html', $vals);
$totalEnd = microtime(true);
$elapsedMs = round(($totalEnd - $totalStart) * 1000, 2);

echo '<div id="tpl-bench-marker" data-time="'.$elapsedMs.'" style="display:none;"></div></body></html>';
//echo Tpl::get('subdir/file.html', $vals);

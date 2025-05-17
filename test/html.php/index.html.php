<?php /* Tplus 1.0.5 2025-05-18 03:23:54 D:\Work\Tplus\test\html\index.html 000013180 */ ?>
<html>
<head>
    <title>welcome!</title>
	<style>
		td {border-top:1px solid gray}
	</style>
</head>
<body>

<table>

<tr>
	<th>
		문서 항목 번호
	</th>
	<th>
		실행 결과
	</th>
	<th>
		결과 코드
	</th>
</tr>
<tr>
	<td>7.</td>
	<td><?=$V['foo'] /*{"line":25,"code":"[=foo]"}*/?>
 <?=bar() /*{"line":25,"code":"[=bar()]"}*/?>
</td>
	<td>hello~ good good</td>
</tr>
<tr>
	<td>9.1</td>
	<td>
	<?php $L1=range(1,2);if (is_array($L1) and !empty($L1)) {$L1s=count($L1);$L1i=-1;foreach($L1 as $L1k=>$L1v) { ++$L1i; /*{"line":31,"code":"[@ range(1,2)]"}*/?>

		<?php $L2=range(3,5);if (is_array($L2) and !empty($L2)) {$L2s=count($L2);$L2i=-1;foreach($L2 as $L2k=>$L2v) { ++$L2i; /*{"line":32,"code":"[@ range(3,5)]"}*/?>

			   <?=$L1v /*{"line":33,"code":"[=.v]"}*/?>
 x <?=$L2v /*{"line":33,"code":"[=..v]"}*/?>
 = <?=$L1v*$L2v /*{"line":33,"code":"[=.v *..v]"}*/?>
 <br/>
		<?php }} /*{"line":34,"code":"[/]"}*/?>

	<?php }} /*{"line":35,"code":"[/]"}*/?>

	</td>
	
	<td>
		1 x 3 = 3 <br/>
		1 x 4 = 4 <br/>
		1 x 5 = 5 <br/>
					2 x 3 = 6 <br/>
		2 x 4 = 8 <br/>
		2 x 5 = 10 <br/>
	</td>
</tr>

<tr>
	<td>9.2.1.</td>
	<td>
		<ul>
			<?php $L1=$V['country'];if (is_array($L1) and !empty($L1)) {$L1s=count($L1);$L1i=-1;foreach($L1 as $L1k=>$L1v) { ++$L1i; /*{"line":52,"code":"[@ country]"}*/?>

			<li>
				<?=$L1v["name"] /*{"line":54,"code":"[=.name]"}*/?>
 <?=$L1v["pop"] /*{"line":54,"code":"[=.pop]"}*/?>
 million
				<ul>
				<?php $L2=$L1v["city"];if (is_array($L2) and !empty($L2)) {$L2s=count($L2);$L2i=-1;foreach($L2 as $L2k=>$L2v) { ++$L2i; /*{"line":56,"code":"[@ .city]"}*/?>

				<li>
					<?=$L2v["name"] /*{"line":58,"code":"[=..name]"}*/?>
 <?=$L2v["pop"] /*{"line":58,"code":"[=..pop]"}*/?>
 million (<?=$L2v["pop"]/$L1v["pop"]*100 /*{"line":58,"code":"[= ..pop / .pop * 100]"}*/?>
%)
				</li>
				<?php }} /*{"line":60,"code":"[/]"}*/?>

				</ul>
			</li>
			<?php }} /*{"line":63,"code":"[/]"}*/?>

		</ul>
			
	</td>
	
	<td>
		<ul>
			<li>
	South Korea 50 million
	<ul>
					<li>
		Seoul	10 million (20%)
	</li>
					<li>
		Sejong	0.3 million (0.6%)
	</li>
					</ul>
</li>
			<li>
	Republic of Maldives 0.4 million
	<ul>
					<li>
		Male	0.15 million (37.5%)
	</li>
					</ul>
</li>
		</ul>
	</td>
</tr>
<tr>
	<td>9.2.2.</td>
	<td>
	<?php $L1=$V['foo1'];if (is_array($L1) and !empty($L1)) {$L1s=count($L1);$L1i=-1;foreach($L1 as $L1k=>$L1v) { ++$L1i; /*{"line":95,"code":"[@ foo1]"}*/?>

		<?php $L2=$V['bar1'];if (is_array($L2) and !empty($L2)) {$L2s=count($L2);$L2i=-1;foreach($L2 as $L2k=>$L2v) { ++$L2i; /*{"line":96,"code":"[@ bar1]"}*/?>
		
			<?=$L1v /*{"line":97,"code":"[=.v]"}*/?>
 x <?=$L2v /*{"line":97,"code":"[=..v]"}*/?>
 = <?=$L1v*$L2v /*{"line":97,"code":"[=.v *..v]"}*/?>
 <br/>
		<?php }} /*{"line":98,"code":"[/]"}*/?>

	<?php }} /*{"line":99,"code":"[/]"}*/?>

	</td>
	<td>
					
		1 x 3 = 3 <br/>
				
		1 x 4 = 4 <br/>
			
		1 x 5 = 5 <br/>
						
		2 x 3 = 6 <br/>
			
		2 x 4 = 8 <br/>
			
		2 x 5 = 10 <br/>
	</td>
</tr>
<tr>
	<td>10.1.</td>
	<td>
	<?php $L1=['a'=>'apple','b'=>'banana','c'=>'cherry'];if (is_array($L1) and !empty($L1)) {$L1s=count($L1);$L1i=-1;foreach($L1 as $L1k=>$L1v) { ++$L1i; /*{"line":119,"code":"[@ {'a':'apple', 'b':'banana', 'c':'cherry'}]"}*/?>

		<?=$L1i +1 /*{"line":120,"code":"[=.i+1]"}*/?>
/<?=$L1s /*{"line":120,"code":"[=.s]"}*/?>
. <?=$L1k /*{"line":120,"code":"[=.k]"}*/?>
: <?=$L1v /*{"line":120,"code":"[=.v]"}*/?>
 <br/>
	<?php }} /*{"line":121,"code":"[/]"}*/?>

	</td>
	<td>
		1/3. a: apple <br/>
		2/3. b: banana <br/>
		3/3. c: cherry <br/>
	</td>
</tr>

<tr>
	<td>10.2.1.</td>
	<td>
	<?php $L1=['apple','banana',123=>'cherry'];if (is_array($L1) and !empty($L1)) {$L1s=count($L1);$L1i=-1;foreach($L1 as $L1k=>$L1v) { ++$L1i; /*{"line":133,"code":"[@ ['apple', 'banana', 123:'cherry']]"}*/?>

		<?=$L1s-$L1i /*{"line":134,"code":"[=.s - .i]"}*/?>
. <?=$L1k /*{"line":134,"code":"[=.k]"}*/?>
: <?=$L1v /*{"line":134,"code":"[=.v]"}*/?>
 <br/>
	<?php }} /*{"line":135,"code":"[/]"}*/?>

	</td>
	<td>
		3. 0: apple <br/>
		2. 1: banana <br/>
		1. 123: cherry <br/>
	</td>
</tr>
<tr>
	<td>10.2.2.</td>
	<td>
	<?php $L1=[1,2];if (is_array($L1) and !empty($L1)) {$L1s=count($L1);$L1i=-1;foreach($L1 as $L1k=>$L1v) { ++$L1i; /*{"line":146,"code":"[@ {1, 2}]"}*/?>

		<?php $L2=[3,4,5];if (is_array($L2) and !empty($L2)) {$L2s=count($L2);$L2i=-1;foreach($L2 as $L2k=>$L2v) { ++$L2i; /*{"line":147,"code":"[@ {3, 4, 5}]"}*/?>

			   <?=$L1v /*{"line":148,"code":"[=.v]"}*/?>
 x <?=$L2v /*{"line":148,"code":"[=..v]"}*/?>
 = <?=$L1v*$L2v /*{"line":148,"code":"[=.v *..v]"}*/?>
 <br/>
		<?php }} /*{"line":149,"code":"[/]"}*/?>

	<?php }} /*{"line":150,"code":"[/]"}*/?>

	
	</td>
	<td>
		1 x 3 = 3 <br/>
		1 x 4 = 4 <br/>
		1 x 5 = 5 <br/>
		2 x 3 = 6 <br/>
		2 x 4 = 8 <br/>
		2 x 5 = 10 <br/>
	</td>
</tr>
<tr>
	<td>11.</td>
	<td>
		<?=$V['fooo'][3] /*{"line":165,"code":"[=fooo[3]]"}*/?>
 <?=$V['fooo'][3] /*{"line":165,"code":"[=fooo{3}]"}*/?>

	</td>
	<td>
		4 4	
	</td>
</tr>
<tr>
	<td>12.1.</td>
	<td>
	<?=$V['xx']->bar() /*{"line":174,"code":"[=xx.bar()]"}*/?>
<br>
	<?=$V['yy']['baz'] /*{"line":175,"code":"[=yy.baz]"}*/?>

	</td>
	<td>
	return from method.<br>
	from array	
	</td>
</tr>
<tr>
	<td>12.2.</td>
	<td>
	<?=\TplValWrapper::_o($V['zz']->baz())->bar() /*{"line":185,"code":"[=zz.baz().bar()]"}*/?>

	</td>
	<td>
	return from method.	
	</td>
</tr>
<tr>
	<td>12.3.</td>
	<td>
	
	<?php $L1=$V['product'];if (is_array($L1) and !empty($L1)) {$L1s=count($L1);$L1i=-1;foreach($L1 as $L1k=>$L1v) { ++$L1i; /*{"line":195,"code":"[@ product]"}*/?>

		<?=$L1v->code() /*{"line":196,"code":"[=.code()]"}*/?>
 <?=$L1v->name() /*{"line":196,"code":"[=.name()]"}*/?>
 <?=$L1v->price() /*{"line":196,"code":"[=.v.price()]"}*/?>
<br/>
	<?php }} /*{"line":197,"code":"[/]"}*/?>


		
	</td>
	<td>
	001 vitamin $100.00<br/>
	002 shoes $123.00<br/>
	</td>
</tr>
<tr>
	<td>13.1.1.</td>
	<td>
		<?=\MY_CONST /*{"line":209,"code":"[=MY_CONST]"}*/?>
 
	</td>
	<td>
		111
	</td>
</tr>
<tr>
	<td>13.1.2.</td>
	<td>
	<?php $L1=\MY_CONST_ARRAY;if (is_array($L1) and !empty($L1)) {$L1s=count($L1);$L1i=-1;foreach($L1 as $L1k=>$L1v) { ++$L1i; /*{"line":218,"code":"[@ MY_CONST_ARRAY]"}*/?>
<?=$L1k /*{"line":218,"code":"[=.k]"}*/?>
:<?=$L1v /*{"line":218,"code":"[=.v]"}*/?>
<br/><?php }} /*{"line":218,"code":"[/]"}*/?>

	</td>
	<td>
	
	a:1<br/>b:2<br/>c:3<br/>	

	</td>
</tr>
<tr>
	<td>13.2.</td>
	<td>
	<?=\Foo::bar() /*{"line":229,"code":"[=Foo.bar()]"}*/?>
<br/>
	<?=\Foo::ITEMS_PER_PAGE /*{"line":230,"code":"[=Foo.ITEMS_PER_PAGE]"}*/?>
<br/>
	<?=\Bar\baz\bar() /*{"line":231,"code":"[=Bar.baz.bar()]"}*/?>
<br/>
	<?=\Bar\baz\ITEMS_PER_PAGE /*{"line":232,"code":"[=Bar.baz.ITEMS_PER_PAGE]"}*/?>
<br/>
	</td>
	<td>
		from static method.<br/>
		30<br/>
		from namespace function<br/>
		50<br/>	
	</td>
</tr>

<tr>
	<td>13.3.</td>
	<td>
	<?=\Widget\Calender::draw() /*{"line":245,"code":"[= Widget.Calender.draw()]"}*/?>
<br/>
	<?=\Widget\Calender::MONTH['march'] /*{"line":246,"code":"[= Widget.Calender.MONTH.march]"}*/?>
<br/>
	</td>
	<td>
	달력위젯이 그림<br/>
	3<br/>
	</td>
</tr>

<tr>
	<td>14.1.1.</td>
	<td>
	<?=$this->fetch('sub.html') /*{"line":257,"code":"[=this.fetch('sub.html')]"}*/?>

	</td>
	<td>
	<div>4 4</div>	

	</td>
</tr>
<tr>
	<td>14.1.2.</td>
	<td>
	<?=\Tpl::get('sub.html',['fooo'=>[3,4,5,6,7,8]]) /*{"line":267,"code":"[=Tpl.get( 'sub.html', {'fooo':{3,4,5,6,7,8}} )]"}*/?>


	</td>
	<td>
	<div>6 6</div>
	</td>
</tr>
<tr>
	<td>14.2.1</td>
	<td>

	<?=$this->fetch($V['sub']) /*{"line":278,"code":"[=this.fetch(sub)]"}*/?>

	</td>
	<td>
	<div>4 4</div>
	</td>
</tr>
<tr>
	<td>14.2.2</td>
	<td>
	<?=$V['sub2'] /*{"line":287,"code":"[=sub2]"}*/?>

	</td>
	<td>
	<div>11 11</div>
	</td>
</tr>


<tr>
	<td>15.</td>
	<td>
	<?php if ($V['foo2']) { /*{"line":298,"code":"[?foo2]"}*/?>
<?=$V['bar2'] /*{"line":298,"code":"[=bar2]"}*/?>
<?php } else { /*{"line":298,"code":"[:]"}*/?>
baz<?php } /*{"line":298,"code":"[/]"}*/?>
<br/>
	<?=$V['foo2']?$V['bar3']:"baz" /*{"line":299,"code":"[= foo2 ? bar3 : \"baz\"]"}*/?>
<br/>
	<?=$V['foo']?:"bar" /*{"line":300,"code":"[= foo ?: \"bar\"]"}*/?>
<br/>
	<?=$V['foo3']??"bar" /*{"line":301,"code":"[= foo3 ?? \"bar\"]"}*/?>
<br/>
	<?=$V['foo3']?:bar()?:"baz" /*{"line":302,"code":"[= foo3 ?: bar() ?: \"baz\"]"}*/?>
<br/>
	<?=$V['foo']?($V['bar']?'foobar':'foo'):'no' /*{"line":303,"code":"[= foo ? (bar ? 'foobar' : 'foo') : 'no']"}*/?>

	</td>
	<td>
	Tplus if	<br/>
	ternary operator	<br/>
	hello~	<br/>
	bar<br/>
	good good	<br/>
	foobar
	</td>
</tr>




<tr>
	<td>17. </td>
	<td>
		<?=$this->assign(['foo'=>123]) /*{"line":332,"code":"[=this.assign({'foo':123})]"}*/?>
					
		<?=$this->assign(['foo'=>456,'bar'=>'bbb']) /*{"line":333,"code":"[=this.assign({'foo':456,'bar':'bbb'})]"}*/?>
	
	
		<?=$V['foo'] /*{"line":335,"code":"[=foo]"}*/?>
 <?=$V['bar'] /*{"line":335,"code":"[=bar]"}*/?>

	
	</td>
	<td>
		456 bbb
	</td>
</tr>

<tr>
	<td>19.</td>
	<td>

		<?=ucfirst($V['bar']."baz".$V['caz']) /*{"line":347,"code":"[=ucfirst( bar + \"baz\" + caz )]"}*/?>

	</td>
	<td>
		Bbbbazzzz

	</td>
</tr>

<tr>
	<td>20</td>
	<td>
	<?php $L1=[];if (is_array($L1) and !empty($L1)) {$L1s=count($L1);$L1i=-1;foreach($L1 as $L1k=>$L1v) { ++$L1i; /*{"line":358,"code":"[@ {}]"}*/?>

		<?=$L1v /*{"line":359,"code":"[=.v]"}*/?>

	<?php }} else { /*{"line":360,"code":"[:]"}*/?>

		foo empty
	<?php } /*{"line":362,"code":"[/]"}*/?>

<br/>
<?php if ($V['fruit']=='apple'||$V['fruit']=='cherry') { /*{"line":364,"code":"[? fruit == 'apple' || fruit == 'cherry']"}*/?>

	red
<?php } else if ($V['fruit']=='blueberry'||$V['pants']=='jeans') { /*{"line":366,"code":"[: fruit == 'blueberry' || pants=='jeans']"}*/?>

	blue
<?php } else { /*{"line":368,"code":"[:]"}*/?>

	unkown
<?php } /*{"line":370,"code":"[/]"}*/?>


	</td>
	<td>
		foo empty
		<br/>
		blue
	</td>
</tr>
<tr>
	<td>21.</td>
	
	
	
	<td>
		<?=\TplValWrapper::_o("abcde")->toUpper() /*{"line":385,"code":"[= \"abcde\".toUpper() ]"}*/?>


	<?=\TplValWrapper::_o([2,5,8])->average() /*{"line":387,"code":"[= {2,5,8}.average() ]"}*/?>
 <br/>

	<?=\TplValWrapper::_o(\TplValWrapper::_o("abcde")->toUpper())->substr(1,3) /*{"line":389,"code":"[= \"abcde\".toUpper().substr(1,3)]"}*/?>
 <br/>

	<?=\TplValWrapper::_o(\TplValWrapper::_o($V['article'])->esc())->nl2br() /*{"line":391,"code":"[= article.esc().nl2br()]"}*/?>
 <br/>

<?php $L1=$V['product'];if (is_array($L1) and !empty($L1)) {$L1s=count($L1);$L1i=-1;foreach($L1 as $L1k=>$L1v) { ++$L1i; /*{"line":393,"code":"[@ product]"}*/?>

	<?=$L1v->code() /*{"line":394,"code":"[=.code()]"}*/?>
 <?=\TplValWrapper::_o(\TplValWrapper::_o($L1v->name())->substr(0,3))->ucfirst() /*{"line":394,"code":"[=.name().substr(0,3).ucfirst()]"}*/?>
 <?=$L1v->price() /*{"line":394,"code":"[=.price()]"}*/?>
<br/>
<?php }} /*{"line":395,"code":"[/]"}*/?>



	</td>
	<td>
		ABCDE
	5 <br/>

	BCD <br/>

	a &lt; b <br />
 b &gt; c <br/>

	001 Vit $100.00<br/>
	002 Sho $123.00<br/>
	</td>
</tr>
<tr>
	<td>22.</td>
	
	<td>
	<?php $L1=['apple','banana','cherry'];if (is_array($L1) and !empty($L1)) {$L1s=count($L1);$L1i=-1;foreach($L1 as $L1k=>$L1v) { ++$L1i; /*{"line":416,"code":"[@ {'apple', 'banana', 'cherry'}]"}*/?>

		<?=$L1i /*{"line":417,"code":"[=.i]"}*/?>
: <?=$L1v /*{"line":417,"code":"[=.v]"}*/?>
:
		<?=\TplLoopHelper::_o($L1i,$L1s,$L1k,$L1v)->isEven()?"even":"odd" /*{"line":418,"code":"[=.h.isEven() ? \"even\" : \"odd\"]"}*/?>
 <?php if (\TplLoopHelper::_o($L1i,$L1s,$L1k,$L1v)->isLast()) { /*{"line":418,"code":"[?.h.isLast()]"}*/?>
--Last<?php } /*{"line":418,"code":"[/]"}*/?>
<br/>
	<?php }} /*{"line":419,"code":"[/]"}*/?>


	</td>
	<td>
		0: apple:
		odd <br/>
			1: banana:
		even <br/>
			2: cherry:
		odd --Last<br/>
	</td>
</tr>
</table>

<br/>
<br/>
[=SERVER.PHP_SELF]: <?=$_SERVER["PHP_SELF"] /*{"line":435,"code":"[=SERVER.PHP_SELF]"}*/?>
<br/>
[=SERVER.PHP_SELF.toUpper()]:  <?=\TplValWrapper::_o($_SERVER["PHP_SELF"])->toUpper() /*{"line":436,"code":"[=SERVER.PHP_SELF.toUpper()]"}*/?>
 
<br/>
<br/>
[=GLOBALS.me]:  <?=$GLOBALS["me"] /*{"line":439,"code":"[=GLOBALS.me]"}*/?>
 <br/>
[=GLOBALS.me.substr(0,5)]:  <?=\TplValWrapper::_o($GLOBALS["me"])->substr(0,5) /*{"line":440,"code":"[=GLOBALS.me.substr(0,5)]"}*/?>
 
<br/>
<br/>
<?php if (isset($_GET["keyword"])) { /*{"line":443,"code":"[? isset(GET.keyword)]"}*/?>

	<?=$_GET["keyword"] /*{"line":444,"code":"[=GET.keyword]"}*/?>
<?=\TplValWrapper::_o($_GET["keyword"])->esc() /*{"line":444,"code":"[=GET.keyword.esc()]"}*/?>

<?php } /*{"line":445,"code":"[/]"}*/?>

<br/>
[/][:]
<br/>
[:CSS][/]
<?php if (isset($V['view'])) { /*{"line":450,"code":"[? isset(view)]"}*/?>

	<p><?=$V['view']['seq'] /*{"line":451,"code":"[=view.seq]"}*/?>
</p>
<?php } else { /*{"line":452,"code":"[:]"}*/?>

	<p>isset() empty()</p>
<?php } /*{"line":454,"code":"[/]"}*/?>
</br>

<?=\TplValWrapper::_o(\Widget\Calender::MONTH['march'])->double() /*{"line":456,"code":"[= Widget.Calender.MONTH.march.double()]"}*/?>



<br/><br/>

<?=$V['qqqqq'] /*{"line":461,"code":"[= qqqqq  ]"}*/?>


<?=$V['asfd']->ewwew() /*{"line":463,"code":"[=asfd.ewwew()]"}*/?>





</br>


</body>
</html>



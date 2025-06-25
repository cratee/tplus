<?php /* Tplus 1.1.0 2025-05-20 05:45:34 D:\Work\Tplus\test\html\error1.html 000000391 */ ?>
<html>
    <head>
        <title>
            Runtime Error Display
        </title>
    </head>
<body>

    
<?=$V['unassignedVar'] /*{"line":10,"code":"[= unassignedVar]"}*/?>


<?=$V['unassignedObject']->someMethod() /*{"line":12,"code":"[= unassignedObject.someMethod()]"}*/?>


</body>
</html>
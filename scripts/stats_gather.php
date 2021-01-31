#!/usr/bin/php

<?

function f1() {
    echo ("Hi\n");
}

function f2() {
    echo ("There\n");
}

$tasks = array('f1', 'invalid', 'f2',);

foreach ($tasks as $t) {
    try {
    call_user_func($t);
    } catch (exception $e) {
        echo("Call to $t failed");
    }
}

?>
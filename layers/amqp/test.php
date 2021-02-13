<?php

if (!class_exists($class = AMQPConnection::class)) {
    echo sprintf('FAIL: Class "%s" does not exist.', $class).PHP_EOL;
    exit(1);
}
// foo2
exit(0);

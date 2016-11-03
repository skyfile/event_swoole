<?php
namespace Event;

interface EventInterface
{
    function trigger($type, $data);
}

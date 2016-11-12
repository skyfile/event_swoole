<?php
namespace Sys;

interface EventInterface
{
    function trigger($type, $data);
}

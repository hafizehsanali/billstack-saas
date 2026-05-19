<?php

function tenant()
{
    return auth()->user()?->tenant;
}
<?php

namespace Bob;

interface TaskLibraryInterface
{
    function register(Application $app);
    function boot(Application $app);
}


<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:cleanup-meetings')->everyFiveMinutes();

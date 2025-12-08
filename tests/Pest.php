<?php

declare(strict_types=1);

use Tests\Helpers\AdminTestHelper;
use Tests\Helpers\ApiTestHelper;
use Tests\Helpers\CourseTestHelper;
use Tests\Helpers\MakesTestUsers;
use Tests\TestCase;

uses(TestCase::class, MakesTestUsers::class, AdminTestHelper::class, ApiTestHelper::class, CourseTestHelper::class)->in('Feature');
uses(MakesTestUsers::class)->in('Unit');

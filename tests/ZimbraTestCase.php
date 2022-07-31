<?php declare(strict_types=1);

namespace Zimbra\Tests;

use Faker\Factory as FakerFactory;
use PHPUnit\Framework\TestCase;

/**
 * Base class for all Zimbra test cases.
 */
abstract class ZimbraTestCase extends TestCase
{
    protected $faker;

    protected function setUp(): void
    {
        $this->faker = FakerFactory::create();
    }
}

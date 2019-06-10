<?php declare(strict_types=1);

namespace Rector\DeadCode\Tests\Rector\Instanceof_\RemoveDuplicatedInstanceOfRector;

use Rector\DeadCode\Rector\Instanceof_\RemoveDuplicatedInstanceOfRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class RemoveDuplicatedInstanceOfRectorTest extends AbstractRectorTestCase
{
    public function test(): void
    {
        $this->doTestFiles([
            __DIR__ . '/Fixture/fixture.php.inc',
            __DIR__ . '/Fixture/jump_around_one.php.inc',
            __DIR__ . '/Fixture/nested.php.inc',
            __DIR__ . '/Fixture/skip_multiple_variables.php.inc',
        ]);
    }

    protected function getRectorClass(): string
    {
        return RemoveDuplicatedInstanceOfRector::class;
    }
}

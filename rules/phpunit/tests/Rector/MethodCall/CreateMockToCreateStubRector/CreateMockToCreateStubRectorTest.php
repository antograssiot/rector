<?php

declare(strict_types=1);

namespace Rector\PHPUnit\Tests\Rector\MethodCall\CreateMockToCreateStubRector;

use Iterator;
use Rector\Core\Testing\PHPUnit\AbstractRectorTestCase;
use Rector\PHPUnit\Rector\MethodCall\CreateMockToCreateStubRector;
use Symplify\SmartFileSystem\SmartFileInfo;

final class CreateMockToCreateStubRectorTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideData()
     */
    public function test(SmartFileInfo $fileInfo): void
    {
        $this->doTestFileInfo($fileInfo);
    }

    public function provideData(): Iterator
    {
        return $this->yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    protected function getRectorClass(): string
    {
        return CreateMockToCreateStubRector::class;
    }
}

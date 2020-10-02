<?php

namespace Rector\SOLID\Tests\Rector\If_\ChangeAndIfToEarlyReturnRector\Fixture;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

class ClosureClass
{
    private function refactorStmts(array $nodes): array
    {
        $this->traverseNodesWithCallable($nodes, function (Node $node): ?Node {
            if ($node instanceof Name && $node instanceof Identifier) {
                return $this->processNameOrIdentifier($node);
            }

            return null;
        });

        return $nodes;
    }
}

?>
-----
<?php

namespace Rector\SOLID\Tests\Rector\If_\ChangeAndIfToEarlyReturnRector\Fixture;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

class ClosureClass
{
    private function refactorStmts(array $nodes): array
    {
        $this->traverseNodesWithCallable($nodes, function (Node $node): ?Node {
            if (!$node instanceof Name) {
                return null;
            }
            if (!$node instanceof Identifier) {
                return null;
            }
            return $this->processNameOrIdentifier($node);
        });

        return $nodes;
    }
}

?>

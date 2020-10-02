<?php

declare(strict_types=1);

namespace Rector\SOLID\Rector\If_;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeTraverser;
use Rector\Core\PhpParser\Node\Manipulator\IfManipulator;
use Rector\Core\PhpParser\Node\Manipulator\StmtsManipulator;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\RectorDefinition\CodeSample;
use Rector\Core\RectorDefinition\RectorDefinition;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\SOLID\NodeTransformer\ConditionInverter;

/**
 * @see \Rector\SOLID\Tests\Rector\If_\ChangeAndIfToEarlyReturnRector\ChangeAndIfToEarlyReturnRectorTest
 */
final class ChangeAndIfToEarlyReturnRector extends AbstractRector
{
    /**
     * @var IfManipulator
     */
    private $ifManipulator;

    /**
     * @var ConditionInverter
     */
    private $conditionInverter;

    /**
     * @var StmtsManipulator
     */
    private $stmtsManipulator;

    public function __construct(
        ConditionInverter $conditionInverter,
        IfManipulator $ifManipulator,
        StmtsManipulator $stmtsManipulator
    ) {
        $this->ifManipulator = $ifManipulator;
        $this->conditionInverter = $conditionInverter;
        $this->stmtsManipulator = $stmtsManipulator;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Changes if && to early return', [
            new CodeSample(
                <<<'CODE_SAMPLE'
class SomeClass
{
    public function canDrive(Car $car)
    {
        if ($car->hasWheels && $car->hasFuel) {
            return true;
        }

        return false;
    }
}
CODE_SAMPLE

                ,
                <<<'CODE_SAMPLE'
class SomeClass
{
    public function canDrive(Car $car)
    {
        if (!$car->hasWheels) {
            return false;
        }

        if (!$car->hasFuel) {
            return false;
        }

        return true;
    }
}
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [If_::class];
    }

    /**
     * @param If_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->ifManipulator->isIfWithOnlyReturn($node)) {
            return null;
        }

        $nextNode = $node->getAttribute(AttributeKey::NEXT_NODE);
        if (! $nextNode instanceof Return_) {
            return null;
        }

        $foreach = $this->betterNodeFinder->findFirstParentInstanceOf($node, Foreach_::class);
        if ($foreach !== null) {
            return null;
        }

        // skip non and ifs
        if (! $node->cond instanceof BooleanAnd) {
            return null;
        }

        if ($this->hasMoreThanTwoConditions($node)) {
            return null;
        }

        $ifReturn = $this->stmtsManipulator->getUnwrappedLastStmt($node->stmts);
        if (! $ifReturn instanceof Return_) {
            return null;
        }

        /** @var FunctionLike|null $functionLike */
        $functionLike = $this->betterNodeFinder->findFirstParentInstanceOf($node, FunctionLike::class);
        if ($functionLike === null || $functionLike->getStmts() === null) {
            return null;
        }
        $this->changeFunctionLikeReturn($functionLike, $ifReturn);

        $invertedLeftCondition = $this->conditionInverter->createInvertedCondition($node->cond->left);
        $invertedRightCondition = $this->conditionInverter->createInvertedCondition($node->cond->right);

        $functionLikeReturn = $this->getOriginalFunctionLikeReturn($functionLike);
        if ($functionLikeReturn === null) {
            return null;
        }

        $firstIf = new If_($invertedLeftCondition);
        $firstIf->stmts = [$functionLikeReturn];
        $secondIf = new If_($invertedRightCondition);
        $secondIf->stmts = [$functionLikeReturn];

        $this->addNodeAfterNode($firstIf, $node);
        $this->addNodeAfterNode($secondIf, $node);

        $this->removeNode($node);

        return null;
    }

    private function hasMoreThanTwoConditions(If_ $if): bool
    {
        $binaryOps = $this->betterNodeFinder->findInstanceOf($if->cond, BooleanAnd::class);
        return count($binaryOps) >= 2;
    }

    private function changeFunctionLikeReturn(FunctionLike $functionLike, Return_ $ifReturn): void
    {
        if ($functionLike->getStmts() === null) {
            return;
        }

        $this->traverseNodesWithCallable($functionLike->getStmts(), function (Node $node) use ($ifReturn): int {
            $parent = $node->getAttribute(AttributeKey::PARENT_NODE);
            if (! $parent instanceof FunctionLike) {
                return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }

            if (! $node instanceof Return_) {
                return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }

            $node->expr = $ifReturn->expr;
            return NodeTraverser::STOP_TRAVERSAL;
        });
    }

    private function getOriginalFunctionLikeReturn(FunctionLike $functionLike): ?Stmt
    {
        $originalFunctionLike = $functionLike->getAttribute(AttributeKey::ORIGINAL_NODE);
        if ($originalFunctionLike === null || $originalFunctionLike->stmts === null) {
            return null;
        }

        $return = $this->stmtsManipulator->getUnwrappedLastStmt($originalFunctionLike->stmts);
        if (! $return instanceof Stmt) {
            return null;
        }

        return clone $return;
    }
}

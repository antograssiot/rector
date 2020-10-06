<?php

declare(strict_types=1);

namespace Rector\Core\Exclusion\Check;

use Nette\Utils\Strings;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\PropertyProperty;
use Rector\Core\Contract\Exclusion\ExclusionCheckInterface;
use Rector\Core\Contract\Rector\PhpRectorInterface;
use Rector\NodeTypeResolver\Node\AttributeKey;

/**
 * @see \Rector\Core\Tests\Exclusion\Check\ExcludeByDocBlockExclusionCheckTest
 */
final class ExcludeByDocBlockExclusionCheck implements ExclusionCheckInterface
{
    /**
     * @var string
     */
    private const NO_RECTORE_ANNOTATION_WITH_CLASS_REGEX = '#\@noRector(\s)+[^\w\\\\]#i';

    public function isNodeSkippedByRector(PhpRectorInterface $phpRector, Node $node): bool
    {
        if ($node instanceof PropertyProperty || $node instanceof Const_) {
            $node = $node->getAttribute(AttributeKey::PARENT_NODE);
            if ($node === null) {
                return false;
            }
        }

        $doc = $node->getDocComment();
        if ($doc !== null && $this->hasNoRectorComment($phpRector, $doc)) {
            return true;
        }

        // recurse up until a Stmt node is found since it might contain a noRector
        $parentNode = $node->getAttribute(AttributeKey::PARENT_NODE);
        if ($node instanceof Stmt) {
            return false;
        }
        if ($parentNode === null) {
            return false;
        }

        return $this->isNodeSkippedByRector($phpRector, $parentNode);
    }

    private function hasNoRectorComment(PhpRectorInterface $phpRector, Doc $doc): bool
    {
        // bare @noRector ignored all rules
        if (Strings::match($doc->getText(), self::NO_RECTORE_ANNOTATION_WITH_CLASS_REGEX)) {
            return true;
        }

        $regex = '#@noRector\s*\\\\?' . preg_quote(get_class($phpRector), '/') . '#i';
        return (bool) Strings::match($doc->getText(), $regex);
    }
}

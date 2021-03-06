<?php declare(strict_types=1);

namespace Rector\Rector\Namespace_;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use Rector\Builder\StatementGlue;
use Rector\Node\NodeFactory;
use Rector\NodeTypeResolver\Node\Attribute;
use Rector\Rector\AbstractRector;
use Rector\RectorDefinition\ConfiguredCodeSample;
use Rector\RectorDefinition\RectorDefinition;

final class PseudoNamespaceToNamespaceRector extends AbstractRector
{
    /**
     * @var string[]
     */
    private $pseudoNamespacePrefixes = [];

    /**
     * @var string|null
     */
    private $newNamespace;

    /**
     * @var NodeFactory
     */
    private $nodeFactory;

    /**
     * @var StatementGlue
     */
    private $statementGlue;

    /**
     * @var string[]
     */
    private $excludedClasses = [];

    /**
     * @param string[] $pseudoNamespacePrefixes
     * @param string[] $excludedClasses
     */
    public function __construct(
        NodeFactory $nodeFactory,
        StatementGlue $statementGlue,
        array $pseudoNamespacePrefixes,
        array $excludedClasses = []
    ) {
        $this->nodeFactory = $nodeFactory;
        $this->statementGlue = $statementGlue;
        $this->pseudoNamespacePrefixes = $pseudoNamespacePrefixes;
        $this->excludedClasses = $excludedClasses;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Replaces defined Pseudo_Namespaces by Namespace\Ones.', [
            new ConfiguredCodeSample(
                '$someService = Some_Object;',
                '$someService = Some\Object;',
                [
                    '$pseudoNamespacePrefixes' => ['Some_'],
                    '$excludedClasses' => [],
                ]
            ),
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [Name::class, Identifier::class];
    }

    /**
     * @param Name|Identifier $node
     */
    public function refactor(Node $node): ?Node
    {
        $name = $node->toString();
        if (! $name) {
            return null;
        }

        if (in_array($name, $this->excludedClasses, true)) {
            return null;
        }

        if (! $this->isNamespaceMatch($name)) {
            return null;
        }

        $oldName = $node->toString();

        $newNameParts = explode('_', $oldName);
        $parentNode = $node->getAttribute(Attribute::PARENT_NODE);
        $lastNewNamePart = $newNameParts[count($newNameParts) - 1];

        if ($node instanceof Name) {
            $node->parts = $newNameParts;

            return $node;
        }

        if ($node instanceof Identifier && $parentNode instanceof Class_) {
            $namespaceParts = $newNameParts;
            array_pop($namespaceParts);

            $this->newNamespace = implode('\\', $namespaceParts);

            $node->name = $lastNewNamePart;

            return $node;
        }

        return null;
    }

    /**
     * @param Stmt[] $nodes
     * @return Node[]
     */
    public function afterTraverse(array $nodes): array
    {
        if ($this->newNamespace) {
            $namespaceNode = $this->nodeFactory->createNamespace($this->newNamespace);

            foreach ($nodes as $key => $node) {
                if ($node instanceof Class_) {
                    $nodes = $this->statementGlue->insertBeforeAndFollowWithNewline($nodes, $namespaceNode, $key);

                    break;
                }
            }
        }

        $this->newNamespace = null;

        return $nodes;
    }

    private function isNamespaceMatch(string $name): bool
    {
        foreach ($this->pseudoNamespacePrefixes as $pseudoNamespacePrefix) {
            if (Strings::startsWith($name, $pseudoNamespacePrefix)) {
                return true;
            }
        }

        return false;
    }
}

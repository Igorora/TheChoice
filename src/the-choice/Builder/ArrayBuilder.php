<?php

namespace TheChoice\Builder;

use TheChoice\Factory\NodeConditionFactory;
use TheChoice\Factory\NodeCollectionFactory;
use TheChoice\Factory\NodeContextFactory;
use TheChoice\Factory\NodeRequireFactory;
use TheChoice\Factory\NodeTreeFactory;
use TheChoice\Factory\NodeValueFactory;

use TheChoice\Contract\OperatorFactoryInterface;
use TheChoice\Contract\BuilderInterface;

class ArrayBuilder implements BuilderInterface
{
    private $rootDir;

    private static $filesLoaded = [];

    private $_nodesCount = 0;

    private $_nodeTreeFactory;
    private $_nodeConditionFactory;
    private $_nodeCollectionFactory;
    private $_nodeContextFactory;
    private $_nodeValueFactory;
    private $_nodeRequireFactory;

    private $_tree;

    public function __construct(OperatorFactoryInterface $operatorFactory)
    {
        $this->_nodeTreeFactory = new NodeTreeFactory();
        $this->_nodeConditionFactory = new NodeConditionFactory();
        $this->_nodeCollectionFactory = new NodeCollectionFactory();
        $this->_nodeContextFactory = new NodeContextFactory($operatorFactory);
        $this->_nodeValueFactory = new NodeValueFactory();
        $this->_nodeRequireFactory = new NodeRequireFactory();
    }

    public function build(&$structure)
    {
        if (!array_key_exists('node', $structure)) {
            throw new \InvalidArgumentException('The "node" property is absent!');
        }

        $this->_nodesCount++;

        if ($structure['node'] === 'tree') {
            if ($this->_nodesCount !== 1) {
                throw new \LogicException('Node of type "Tree" must be a root node!');
            }

            $this->_tree = $this->_nodeTreeFactory->build($this, $structure);
            $this->_tree->setNodes($this->_nodeTreeFactory->buildNodes($this, $structure));
            return $this->_tree;
        }

        if ($structure['node'] === 'condition') {
            $node = $this->_nodeConditionFactory->build($this, $structure);
        } elseif ($structure['node'] === 'collection') {
            $node = $this->_nodeCollectionFactory->build($this, $structure);
        } elseif ($structure['node'] === 'context') {
            $node = $this->_nodeContextFactory->build($this, $structure);
        } elseif ($structure['node'] === 'value') {
            $node = $this->_nodeValueFactory->build($this, $structure);
        } elseif ($structure['node'] === 'require') {
            $node = $this->_nodeRequireFactory->build($this, $structure);
        } else {
            throw new \InvalidArgumentException(sprintf('Unknown node type "%s"', $structure['node']));
        }

        if (null !== $this->_tree) {
            $node->setTree($this->_tree);
        }

        return $node;
    }

    public function getRootDir(): string
    {
        return $this->rootDir;
    }

    public function setRootDir(string $rootDir)
    {
        $this->rootDir = $rootDir;
        return $this;
    }

    public function addLoadedFile(string $path)
    {
        if (in_array($path, self::$filesLoaded, true)) {
            throw new \RuntimeException(sprintf('Circular link detected while loading file: "%s"', $path));
        }

        self::$filesLoaded[] = $path;
    }
}

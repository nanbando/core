<?php

namespace Nanbando\Application\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\OutOfBoundsException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;

class CollectorCompilerPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $serviceId;

    /**
     * @var string
     */
    private $tagName;

    /**
     * @var int
     */
    private $argumentNumber;

    /**
     * @var string
     */
    private $aliasAttribute;

    /**
     * @param string $serviceId
     * @param string $tagName
     * @param string $aliasAttribute
     * @param int $argumentNumber
     */
    public function __construct($serviceId, $tagName, $aliasAttribute, $argumentNumber = 0)
    {
        $this->serviceId = $serviceId;
        $this->tagName = $tagName;
        $this->aliasAttribute = $aliasAttribute;
        $this->argumentNumber = $argumentNumber;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ServiceNotFoundException
     * @throws OutOfBoundsException
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->serviceId)) {
            return;
        }

        $references = [];
        foreach ($container->findTaggedServiceIds($this->tagName) as $id => $tags) {
            $reference = new Reference($id);

            foreach ($tags as $attributes) {
                $references[$attributes[$this->aliasAttribute]] = $reference;
            }
        }

        if (0 === count($references)) {
            return;
        }

        $container->getDefinition($this->serviceId)->replaceArgument($this->argumentNumber, $references);
    }
}

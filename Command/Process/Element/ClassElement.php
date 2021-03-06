<?php

namespace Patgod85\Phpdoc2rst\Command\Process\Element;

use Doctrine\Common\Annotations\AnnotationReader;
use Patgod85\Phpdoc2rst\Annotation\Exclude;
use Patgod85\Phpdoc2rst\Command\Process\CommentParser;
use TokenReflection\IReflectionClass;
use TokenReflection\ReflectionClass;


/**
 * Class element
 */
class ClassElement extends Element
{
    /**
     * @var ReflectionClass
     */
    protected $reflection;

    private $elementsForOutput;

    /** @var array  */
    protected $doctrineAnnotations;

    /** @var  array */
    protected $requiredGroups;

    function __construct(IReflectionClass $reflection, $groups)
    {
        parent::__construct($reflection);

        $reader = new AnnotationReader();
        $this->doctrineAnnotations = $reader->getClassAnnotations(new \ReflectionClass($this->reflection->getName()));

        $this->requiredGroups = array_filter(
            preg_split(
                '/[,;]/',
                preg_replace('/[\[\]\{\}]/', '', $groups)
            ),
            function($element){
                return (bool)$element;
            }
        );
    }

    public function getPath()
    {
        return $this->reflection->getShortName() . '.rst';
    }

    /**
     * @param string $basedir
     * @param string $elementsForOutput
     * @return void
     */
    public function build($basedir, $elementsForOutput)
    {
        $this->elementsForOutput = $elementsForOutput;

        $file = $basedir . DIRECTORY_SEPARATOR . $this->getPath();
        file_put_contents($file, $this->__toString());
    }

    public function getName()
    {
        return $this->reflection->getShortName();
    }

    public function getDescription()
    {
        $parser = $this->getParser();

        return $parser->getDescription();
    }

    /**
     * @see Element.Element::__toString()
     */
    public function __toString()
    {
        $string = "====\n".$this->reflection->getShortName()."\n====";

        $parser = $this->getParser();

        if ($description = $parser->getDescription())
        {
            $string .= "\n\n";
            $string .= $description;
        }

        /** @var Element $element */
        foreach ($this->getSubElements() as $element)
        {
            $e = $element->__toString();
            if ($e)
            {
                $string .= "\n\n";
                $string .= $e;
            }
        }

        $string .= "\n\n";

        // Finally, fix some whitespace errors
        $string = preg_replace('/^\s+$/m', '', $string);
        $string = preg_replace('/ +$/m', '', $string);

        return $string;
    }

    protected function getSubElements()
    {
        switch($this->elementsForOutput)
        {
            case 'methods':
                return $this->getMethods();
            case 'properties':
                return $this->getProperties();
        }

        return [];
    }

    protected function getConstants()
    {
        return array_map(function ($v) {
            return new ConstantElement($v);
        }, $this->reflection->getConstantReflections());
    }

    public function getProperties()
    {
        $propertiesReflections = $this->reflection->getProperties();

        $properties = array_map(
            function ($reflection){
                return new PropertyElement($reflection);
            },
            $propertiesReflections
        );

        $properties = array_filter(
            $properties,
            function (PropertyElement $property){
                if($this->requiredGroups)
                {
                    if(!$property->getGroups())
                    {
                        return false;
                    }

                    return (bool)array_intersect($this->requiredGroups, $property->getGroups());
                }

                return !$property->isExcluded();
            }
        );

        return array_merge(
            $properties,
            $this->getVirtualProperties()
        );
    }

    public function getMethods()
    {

        $methods = array_map(
            function ($v)
            {
                return new MethodElement($v);
            },
            $this->reflection->getOwnMethods()
        );

        return array_filter(
            $methods,
            function(MethodElement $element){
                return !$element->isExcluded();
            }
        );
    }

    public function getVirtualProperties()
    {

        $methods = array_map(
            function ($v)
            {
                return new MethodElement($v);
            },
            $this->reflection->getOwnMethods()
        );

        $array_filter = array_filter(
            $methods,
            function (MethodElement $element)
            {
                return !$element->isExcluded() && $element->isVirtualProperty() && (bool)array_intersect($this->requiredGroups, $element->getGroups());
            }
        );

        return $array_filter;
    }

    public function getNamespaceElement()
    {
        return '.. php:namespace: '
            . str_replace('\\', '\\\\', $this->reflection->getNamespaceName())
            . "\n\n";
    }

    protected function getParser()
    {
        return new CommentParser($this->reflection->getDocComment());
    }

    public function isExcluded()
    {
        $result = false;

        foreach($this->doctrineAnnotations as $a)
        {
            if($a instanceof Exclude)
            {
                $result = true;
                break;
            }
        }

        return $result;
    }
}
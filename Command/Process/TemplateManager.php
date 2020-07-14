<?php

namespace Patgod85\Phpdoc2rst\Command\Process;


use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TemplateManager
{
    /** @var \Twig_Environment */
    private $twig;

    function __construct()
    {
        $loader = new FilesystemLoader(__DIR__.'/../../Resources/views/');

        $this->twig = new Environment($loader, array(
            'cache' => __DIR__.'/../../var/twig',
            'auto_reload' => true,
        ));
    }

    public function render($templateName, $vars)
    {
        return $this->twig->render($templateName, $vars);
    }
}
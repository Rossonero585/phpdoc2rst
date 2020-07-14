<?php

namespace Patgod85\Phpdoc2rst\Tests\Command;

use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use Mockery as m;
use Patgod85\Phpdoc2rst\Command\Process\Services\ErrorsProvider;
use Patgod85\Phpdoc2rst\Service\TrainSystemConnector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Patgod85\Phpdoc2rst\Annotation\Exclude;
use Patgod85\Phpdoc2rst\Annotation\HttpMethod;
use Patgod85\Phpdoc2rst\Annotation\Result;

class CommandHelper extends TestCase
{
    const INPUT_RELATIVE_PATH = '../../Resources/test/input';
    const OUTPUT_RELATIVE_PATH = '../../Resources/test/output';
    const EXPECTED_RELATIVE_PATH = '../../Resources/test/expected';

    public function setUp()
    {
        new Exclude();
        new HttpMethod();
        new Result();
        new Groups();
        new VirtualProperty(['name' => 'value']);
        new SerializedName(['value' => 'initialization']);

        $Directory = new \RecursiveDirectoryIterator($this->getOutputPath());
        $Iterator = new \RecursiveIteratorIterator($Directory);
        $Regex = new \RegexIterator($Iterator, '/^.+\.rst$/i', \RecursiveRegexIterator::GET_MATCH);

        foreach($Regex as $item)
        {
            unlink($item[0]);
            print_r('A file removed before a test: '.$item[0]."\n");
        }
    }

    /**
     * @return Container
     */
    protected function getContainer()
    {
        $errorsProvider = new ErrorsProvider();

        $trainSystemConnector = new TrainSystemConnector($errorsProvider);

        $container = m::mock('Symfony\Component\DependencyInjection\Container');
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $container
            ->shouldReceive('get')
            ->once()
            ->with('phpdoc2rst_train_system_connector')
            ->andReturn($trainSystemConnector)
        ;

        return $container;
    }

    protected function getInputPath()
    {
        return __DIR__.'/'.self::INPUT_RELATIVE_PATH;
    }

    protected function getOutputPath()
    {
        return __DIR__.'/'.self::OUTPUT_RELATIVE_PATH;
    }
    protected function getExpectedPath()
    {
        return __DIR__.'/'.self::EXPECTED_RELATIVE_PATH;
    }

    protected function stripOutput($output)
    {
        return preg_replace("/\033[33m/", '', $output);
    }

    protected function replaceBreakLines($output)
	{
		return str_replace(array("\r\n", "\n", "\r"), '<br/>', $output);
	}
}
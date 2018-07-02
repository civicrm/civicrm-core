<?php
namespace MJS\TopSort\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BenchmarkCommand extends Command
{
    /**
     * @var Table
     */
    protected $table;

    /**
     * @var ProgressBar
     */
    protected $process;

    protected function configure()
    {
        $this
            ->setName('benchmark')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->testSimpleCount($output);
        $this->testGroupCount($output);
    }

    protected function testGroupCount($output)
    {
        $this->test(['GroupedArraySort', 'GroupedStringSort'], $output);
    }

    protected function testSimpleCount($output)
    {
        $this->test(['FixedArraySort', 'ArraySort', 'StringSort'], $output);
    }

    protected function test($classes, OutputInterface $output)
    {
        $this->table = new Table($output);
        $this->table->setHeaders(array('Count', 'Implementation', 'Memory', 'Duration'));

        $counts = array(50, 1000, 10000, 100000, 1000000);

        $this->process = new ProgressBar($output, count($counts));
        $this->process->start();
        foreach ($counts as $count) {

            foreach ($classes as $class) {
                if ($count === 1000000 && $class === 'GroupedArraySort') {
                    continue;
                }

                $path = __DIR__ . '/../../bin/test.php';

                $result = `php $path $class $count`;
                $data = json_decode($result, true);
                if (!$data) {
                    echo $result;
                }

                $this->table->addRow(
                    array(
                        number_format($count),
                        $class,
                        sprintf('%11sb', number_format($data['memory'])),
                        sprintf('%6.4fs', $data['time'])
                    )
                );

            }

            $this->process->advance();
        }

        $this->process->finish();
        $output->writeln('');
        $this->table->render($output);
    }
}
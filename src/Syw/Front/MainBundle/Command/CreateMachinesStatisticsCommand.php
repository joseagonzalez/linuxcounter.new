<?php

namespace Syw\Front\MainBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use FOS\UserBundle\Model\User;
use Syw\Front\MainBundle\Entity\StatsMachines;

/**
 *
 */
class CreateMachinesStatisticsCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('syw:statistics:machines')
            ->setDescription('Creates the statistics for machines')
            ->setDefinition(array())
            ->setHelp(<<<EOT
The <info>syw:statistics:machines</info> command Creates the statistics for machines.

EOT
            );
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $db = $this->getContainer()->get('doctrine')->getManager();
        $qb = $db->createQueryBuilder();
        $qb->select('count(m.id)');
        $qb->from('SywFrontMainBundle:Machines', 'm');
        $mCount = $qb->getQuery()->getSingleScalarResult();
        $ipl   = 1000;
        for ($start = 0; $start <  $mCount; $start+=$ipl) {
            $machines = $db->getRepository('SywFrontMainBundle:Machines')->findBy(
                array(),
                array('createdAt' => 'ASC'),
                $ipl,
                $start
            );
            $range[0] = new \DateTime("1970-1-1 00:00:00");
            $range[1] = new \DateTime("1970-1-1 00:00:00");
            foreach ($machines as $machine) {
                $createdAt = $machine->getCreatedAt();
                if ($createdAt <= new \DateTime('1993-01-01')) {
                    continue;
                }
                $rtmp = $this->monthRange($createdAt);
                $pexist = $db->getRepository('SywFrontMainBundle:StatsMachines')->findOneBy(array('month' => $rtmp[0]));
                if (true === isset($pexist) && true === is_object($pexist)) {
                    $statsReg = $pexist;
                    $range = $rtmp;
                    unset($pexist);
                }
                if (($createdAt >= $range[0]) && ($createdAt <= $range[1])) {
                    $statsReg->setNum($statsReg->getNum() + 1);
                    continue;
                }
                if (true === isset($statsReg) && true === is_object($statsReg)) {
                    $db->persist($statsReg);
                    $db->flush();
                }
                unset($statsReg);
                $range    = $this->monthRange($createdAt);
                $statsReg = new StatsMachines();
                $statsReg->setMonth($range[0]);
                $statsReg->setNum(1);
                $db->persist($statsReg);
                $db->flush();
            }
        }

        $output->writeln(sprintf('Machines statistics created', ''));
    }

    protected function monthRange($date)
    {
        //First day of month
        $date->modify('first day of this month');
        $firstday = $date->format('Y-m-d 00:00:00');
        //Last day of month
        $date->modify('last day of this month');
        $lastday = $date->format('Y-m-d 23:59:59');

        return array(new \DateTime($firstday), new \DateTime($lastday));
    }

    /**
     * @see Command
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {

    }
}

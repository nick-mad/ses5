<?php

namespace App\Command;

use App\Service\SubscriptionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-weather-updates',
    description: 'Отправляет обновления прогноза погоды всем активным подписчикам'
)]
class SendWeatherUpdatesCommand extends Command
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'force-all',
                null,
                InputOption::VALUE_NONE,
                'Отправить всем подтвержденным подпискам, независимо от времени последней отправки'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $forceAll = $input->getOption('force-all');

        $io->title('Отправка обновлений прогноза погоды');

        if ($forceAll) {
            $io->note('Режим принудительной отправки всем подписчикам');
        }

        try {
            $count = $this->subscriptionService->sendWeatherUpdates($forceAll);

            if ($count > 0) {
                $io->success(sprintf('Отправлено %d обновлений погоды', $count));
            } else {
                $io->info('Нет подписчиков для отправки обновлений в данный момент');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Ошибка при отправке обновлений: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
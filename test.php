<?php

use Illuminate\Support\Facades\Hash;

$hash = '$2y$10$Uv5gkcy9QyKsx1su6hlrW.ZbnG40kP1TI2zdm4xbZ3GlNvbXPFnSu';
$check = Hash::check('123456', $hash);
dd($check); // Должно быть true

// require 'vendor/autoload.php';

// use Symfony\Component\Console\Application;
// use Symfony\Component\Console\Command\Command;
// use Symfony\Component\Console\Input\InputInterface;
// use Symfony\Component\Console\Output\OutputInterface;

// $app = new Application('Test', 'v1');

// $app->add(new class('hello') extends Command {
//     protected function execute(InputInterface $input, OutputInterface $output): int {
//         $output->writeln('✅ Symfony Console работает!');
//         return 0;
//     }
// });

// $app->run();
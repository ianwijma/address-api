<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: 'address:version:activate', description: 'Switches the active version')]
class AddressVersionActivateCommand extends Command
{

}

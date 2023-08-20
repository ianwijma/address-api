<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: 'address:version:list', description: 'Lists the address versions')]
class AddressVersionListCommand extends Command
{

}

<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: 'address:import:file', description: 'Imports addresses using a file as input')]
class AddressImportGeojsonCommand extends Command
{

}

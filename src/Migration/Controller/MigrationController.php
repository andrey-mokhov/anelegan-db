<?php
/**
 * @author Andrey N. Mokhov aka Andi <github at mokhov.com>
 * @license https://opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 * @copyright Copyright (c) 2016 Andrey N. Mokhov
 * Date: 23.03.2016
 * Time: 20:06
 * Project: Migration for Zend Framework
 */

namespace Anelegan\Db\Migration\Controller;


use Anelegan\Db\Migration\Exception\StopMigrationException;
use Anelegan\Db\Migration\Service\MigrationService;
use Zend\Console\ColorInterface;
use Zend\Console\Prompt\Confirm;
use Zend\Mvc\Controller\AbstractConsoleController;

class MigrationController extends AbstractConsoleController
{
    /**
     * @var MigrationService
     */
    protected $migrationService;

    protected $interactive = true;

    /**
     * @param MigrationService $migrationService
     * @return static
     */
    public function setMigrationService(MigrationService $migrationService)
    {
        $this->migrationService = $migrationService;
        return $this;
    }

    protected function showMigrationList(array $migrationList)
    {
        if (empty($migrationList)) {
            $this->console->writeLine('    none', ColorInterface::WHITE);
            return false;
        }

        $maxLength = 0;
        foreach ($migrationList as $migration) {
            $maxLength = max($maxLength, strlen($migration['name']));
        }
        foreach ($migrationList as $migration) {
            if (empty($migration['depends'])) {
                $this->console->writeLine('    > ' . $migration['name'], ColorInterface::WHITE);
            } else {
                $paddingName = str_pad($migration['name'], $maxLength, ' ', STR_PAD_RIGHT);
                $this->console->write('    > ' . $paddingName . '    depends: ', ColorInterface::WHITE);
                $this->console->writeLine(implode(', ', $migration['depends']), ColorInterface::BLUE);
            }
        }
        return true;
    }

    public function listAction()
    {
        $this->console->writeLine(' Installed migration:', ColorInterface::LIGHT_WHITE);
        $this->showMigrationList($this->migrationService->getInstalledMigrations());

        $this->console->writeLine(' Available migrations:', ColorInterface::LIGHT_WHITE);
        $this->showMigrationList($this->migrationService->getAvailableMigrations());
    }

    public function upAction()
    {
        $available = $this->migrationService->getAvailableMigrations();
        $this->console->writeLine(' Available migrations:');
        if (!$this->showMigrationList($available)) {
            return;
        }
        try {
            if ($oneMigration = $this->getRequest()->getParam('migration', null)) {
                if (isset($available[$oneMigration])) {
                    $this->migrationService->installMigration($oneMigration, $this->interactive);
                } else {
                    $this->console->writeLine(sprintf(' "%s" migration not available.', $oneMigration), ColorInterface::RED);
                }
            } else {
                if (!$this->interactive || Confirm::prompt(' Install available migration? [y/n] ')) {
                    foreach ($available as $name => $migration) {
                        $this->migrationService->installMigration($name);
                    }
                } else {
                    $this->console->writeLine(' Migration process stopped by user.', ColorInterface::GREEN);
                }
            }
        } catch (StopMigrationException $e) {
            $this->console->writeLine($e->getMessage(), ColorInterface::RED);
        }
    }

    public function downAction()
    {
        $installed = $this->migrationService->getInstalledMigrations();
        $this->console->writeLine(' Installed migrations:');
        if (!$this->showMigrationList($installed)) {
            return;
        }
        try {
            if ($oneMigration = $this->getRequest()->getParam('migration', null)) {
                if (isset($installed[$oneMigration])) {
                    $this->migrationService->uninstallMigration($oneMigration, $this->interactive);
                } else {
                    $this->console->writeLine(sprintf(' "%s" migration not installed.', $oneMigration), ColorInterface::RED);
                }
            } else {
                foreach (array_reverse($installed) as $name => $migration) {
                    $this->migrationService->uninstallMigration($name, $this->interactive);
                }
            }
        } catch (StopMigrationException $e) {
            $this->console->writeLine($e->getMessage(), ColorInterface::RED);
        }
    }

    public function chooseAction()
    {
        $action = $this->getRequest()->getParam('subAction') ? : 'list';
        if (in_array($this->getRequest()->getParam('interactive', 'yes'), ['n', 'no', '0', 'false'])) {
            $this->interactive = false;
        }
        return call_user_func([$this, $action . 'Action']);
    }
}

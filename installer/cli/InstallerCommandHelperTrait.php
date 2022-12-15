<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA 02110-1301, USA
 */

namespace OrangeHRM\Installer\cli;

use InvalidArgumentException;
use OrangeHRM\Installer\Util\SystemCheck;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

trait InstallerCommandHelperTrait
{
    /**
     * @param string $label
     * @return string
     */
    private function getRequiredField(string $label): string
    {
        $question = new Question($label . ' ' . self::REQUIRED_TAG);
        $question->setNormalizer(static function ($value) {
            return $value !== null ? trim($value) : null;
        });
        $question->setValidator([$this, 'requiredValidator']);
        return $this->getIO()->askQuestion($question);
    }

    /**
     * @param string|null $answer
     * @return string|null
     */
    public function requiredValidator(?string $answer): ?string
    {
        if ($answer === null || strlen($answer) === 0) {
            throw new InvalidArgumentException(self::REQUIRED_WARNING);
        }
        return $answer;
    }

    /**
     * @param array $systemCheckResults
     */
    private function drawSystemCheckTable(array $systemCheckResults): void
    {
        $this->getIO()->title('System Check');
        $this->getIO()->block(
            'In order for your OrangeHRM installation to function properly, please ensure that all of the system check items listed below are green. If any are red, please take the necessary steps to fix them.'
        );
        foreach ($systemCheckResults as $category) {
            $rows = [];
            foreach ($category['checks'] as $check) {
                switch ($check['value']['status']) {
                    case SystemCheck::PASSED:
                        $style = '<fg=black;bg=green>%s</>';
                        break;
                    case SystemCheck::BLOCKER:
                        $style = '<fg=white;bg=red>%s</>';
                        break;
                    case SystemCheck::ACCEPTABLE:
                        $style = '<fg=black;bg=yellow>%s</>';
                        break;
                    default:
                        $style = '<fg=default;bg=default>%s</>';
                }
                $status = sprintf($style, $check['value']['message']);
                $rows[] = [$check['label'], $status];
            }
            $this->getIO()->table([$category['category']], $rows);
        }
    }

    /**
     * @param OutputInterface $output
     * @param string $step
     * @param string $suffix
     * @return ConsoleSectionOutput
     */
    private function startSection(OutputInterface $output, string $step, string $suffix = ''): ConsoleSectionOutput
    {
        /** @var ConsoleSectionOutput $section */
        $section = $output->section();
        $section->writeln("* $step$suffix");
        return $section;
    }

    /**
     * @param ConsoleSectionOutput $section
     * @param string $step
     * @param string $suffix
     */
    private function startStep(ConsoleSectionOutput $section, string $step, string $suffix = ''): void
    {
        $section->overwrite("* <comment>$step</comment>$suffix");
    }

    /**
     * @param ConsoleSectionOutput $section
     * @param string $step
     * @param string $suffix
     */
    private function completeStep(ConsoleSectionOutput $section, string $step, string $suffix = ''): void
    {
        $section->overwrite("<fg=green>* $step ✓</>$suffix");
    }
}

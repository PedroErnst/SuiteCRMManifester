<?php
/**
 *
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2013 SugarCRM Inc.
 *
 * SuiteCRM is an extension to SugarCRM Community Edition developed by SalesAgility Ltd.
 * Copyright (C) 2011 - 2018 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo and "Supercharged by SuiteCRM" logo. If the display of the logos is not
 * reasonably feasible for technical reasons, the Appropriate Legal Notices must
 * display the words "Powered by SugarCRM" and "Supercharged by SuiteCRM".
 */


namespace Manifester;


class Manifest
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $manifest = [];

    /**
     * @var array
     */
    private $installDefs = [];

    /**
     * @var resource
     */
    private $file;

    /**
     * @var int
     */
    private $indentation = 0;

    /**
     * Manifest constructor.
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * @param string $path
     * @return Manifest
     * @throws \Exception
     */
    public static function fromFile($path)
    {
        if (!file_exists($path)) {
            throw new \Exception('Unable to locate manifest at: ' . $path);
        }

        $manifestObj = new Manifest($path);

        $manifest = [];
        $installdefs = [];
        require $path;

        if (null !== $manifest && is_array($manifest)) {
            $manifestObj->setManifest($manifest);
        }

        if (null !== $installdefs && is_array($installdefs)) {
            $manifestObj->setInstallDefs($installdefs);
        }

        return $manifestObj;
    }

    /**
     * @param $arr
     */
    public function setManifest($arr)
    {
        $this->manifest = $arr;
    }

    /**
     * @return array
     */
    public function getManifest()
    {
        return $this->manifest;
    }

    /**
     * @return bool
     */
    public function validateManifest()
    {
        return count($this->manifest) > 0;
    }

    /**
     * @param $arr
     */
    public function setInstallDefs($arr)
    {
        $this->installDefs = $arr;
    }

    /**
     * @return array
     */
    public function getInstallDefs()
    {
        return $this->installDefs;
    }

    /**
     * @return bool
     */
    public function validateInstallDefs()
    {
        return count($this->installDefs) > 0;
    }

    /**
     * @param array $fileArr
     */
    public function parseChangedFiles($fileArr)
    {
        $langFiles = [];
        $copyFiles = [];
        foreach ($fileArr as $file) {
            if (strpos($file, 'en_us') !== false) {
                $langFiles[] = [
                    'from' => '<basepath>/' . $file,
                    'to_module' => $this->extractLangFileModuleName($file),
                    'language' => 'en_us',
                ];
                continue;
            }
            $copyFiles[] = [
                'from' => '<basepath>/' . $file,
                'to' => $file,
            ];
        }

        $this->installDefs['copy'] = $copyFiles;
        $this->installDefs['language'] = $langFiles;
    }

    /**
     * @param string $file
     * @return string
     */
    private function extractLangFileModuleName($file)
    {
        $module = 'application';
        if (strpos($file, 'custom/Extension/modules/') !== false) {
            $startPos = strpos($file, 'custom/Extension/modules/')
                + strlen('custom/Extension/modules/');
            $module = substr(
                $file,
                $startPos,
                strpos($file, '/', $startPos) - $startPos
            );

        }
        return $module;
    }

    /**
     * @param string $path
     * @throws \Exception
     */
    public function writeOut($path = '')
    {
        if ($path === '') {
            $path = $this->path;
        }
        $this->file = fopen($path, 'w');

        if (!$this->file) {
            throw new \Exception('Unable to open manifest file for writing: ' . $path);
        }

        $this->write('<?php');

        $this->endLine();
        $this->write('$manifest = ');
        $this->writeVariable($this->manifest);
        $this->write(';');

        $this->endLine();
        $this->write('$installdefs = ');
        $this->writeVariable($this->installDefs);
        $this->write(';');

        if (!fclose($this->file)) {
            throw new \Exception('Unable to close manifest file: ' . $path);
        }
    }

    /**
     * @param mixed $var
     */
    private function writeVariable($var)
    {
        if (is_array($var)) {
            $this->write('[');
            $this->indentation += 4;
            $this->endLine();
            foreach ($var as $key => $value) {
                $this->write("'" . $key . "' => ");
                $this->writeVariable($value);
                $this->write(',');
                $this->endLine();
            }
            $this->indentation -= 4;
            $this->write(']');
            return;
        }
        $this->write("'" . $var . "'");
    }

    /**
     * @param string $value
     */
    private function write($value)
    {
        fwrite($this->file, $value);
    }

    /**
     *
     */
    private function endLine()
    {
        $this->write(PHP_EOL);
        fwrite($this->file, str_repeat(' ', $this->indentation));
    }

    /**
     *
     */
    public function setPublishedToNow()
    {
        $dt = new \DateTime();
        $this->manifest['published_date'] = $dt->format('Y-m-d H:i:s');
    }

    /**
     *
     */
    public function updateAuthor($force = false)
    {
        if ($this->manifest['author'] === '') {
            $this->manifest['author'] = exec('git config user.name');
        }
    }

    /**
     *
     */
    public function incrementVersion()
    {
        if ($this->manifest['version'] !== '') {
            $this->manifest['version'] = (int)$this->manifest['version'] + 1;
        }
    }
}
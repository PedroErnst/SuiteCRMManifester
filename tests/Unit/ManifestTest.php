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

namespace App\Tests\Unit;

require_once __DIR__ . '/../../vendor/autoload.php';

use Manifester\Manifest;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Exception;

class ManifestTest extends TestCase
{
    /**
     * @var string
     */
    private $folder;

    /**
     *
     */
    public function setUp()
    {
        $this->folder = __DIR__ . 'manifest-folder';
    }

    /**
     *
     */
    public function testConstruct()
    {
        $manifest = new Manifest('dummyPath');

        $this->assertInstanceOf(Manifest::class, $manifest);
    }

    /**
     * @throws \Exception
     */
    public function testFromFile()
    {
        $fileSystem = vfsStream::setup($this->folder);

        vfsStream::newFile('manifest.php')->at($fileSystem)->setContent('<?php $manifest = [];');

        $manifest = Manifest::fromFile($fileSystem->url() . '/manifest.php');

        $this->assertInstanceOf(Manifest::class, $manifest);

        $manifest = Manifest::fromFile(__DIR__ . '/../../src/boilerplate/default-manifest.php');

        $this->assertInstanceOf(Manifest::class, $manifest);
    }

    /**
     * @throws \Exception
     */
    public function testValidateManifest()
    {
        $manifest = Manifest::fromFile(__DIR__ . '/../../src/boilerplate/default-manifest.php');

        $this->assertTrue($manifest->validateManifest());

        $manifest->setManifest([]);

        $this->assertFalse($manifest->validateManifest());
    }

    /**
     *
     */
    public function testParseChangedFiles()
    {
        $manifest = new Manifest('dummyPath');

        $manifest->parseChangedFiles([
            'changedFileA.php',
            'en_us.changedFileB.php',
            'custom/Extension/modules/Accounts/Ext/Language/en_us.changedFileB.php',
        ]);

        $installDefs = $manifest->getInstallDefs();

        $this->assertEquals('<basepath>/changedFileA.php', $installDefs['copy'][0]['from']);
        $this->assertEquals('changedFileA.php', $installDefs['copy'][0]['to']);
        
        $this->assertEquals('<basepath>/en_us.changedFileB.php', $installDefs['language'][0]['from']);
        $this->assertEquals('application', $installDefs['language'][0]['to_module']);
        $this->assertEquals('en_us', $installDefs['language'][0]['language']);

        $this->assertEquals('Accounts', $installDefs['language'][1]['to_module']);
    }

    /**
     * @throws \Exception
     */
    public function testWriteOut()
    {
        $manifest = Manifest::fromFile(__DIR__ . '/../../src/boilerplate/default-manifest.php');

        $fileSystem = vfsStream::setup($this->folder);

        $manifest->writeOut($fileSystem->url() . '/writeOut.php');

        $this->assertFileExists($fileSystem->url() . '/writeOut.php');

        $manifestLoaded = Manifest::fromFile($fileSystem->url() . '/writeOut.php');

        $this->assertEquals($manifest->getManifest(), $manifestLoaded->getManifest());
    }
}